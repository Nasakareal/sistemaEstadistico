<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadFoto;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Grua;
use App\Models\Hechos;
use App\Models\LicenciaPuntoInfraccion;
use App\Services\ImageThumbnailService;
use App\Services\IphPuestaDisposicionDocxService;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConduceLegalidadController extends Controller
{
    private const UNIDAD_SINIESTROS = 1;
    private const UNIDAD_DELEGACIONES = 2;
    private const UNIDAD_SEGURIDAD_VIAL = 3;
    private const UNIDAD_VIALIDADES_URBANAS = 5;
    private const NOMBRE_OPERATIVO = 'Operativo conduce con legalidad';
    private const ESTADOS = ['activo', 'cerrado', 'cancelado'];
    private const FUNDAMENTO_SIN_LICENCIA_CODIGO = 'OP_CL_SIN_LICENCIA_SIN_HABILITADO';
    private const NARRATIVA_SIN_LICENCIA = 'Se hace constar que la persona conductora no exhibe licencia vigente expedida por autoridad competente, por lo que, conforme al articulo 402, carece de habilitacion juridica para continuar conduciendo el vehiculo. Se le informa que la marcha no puede continuar bajo su mando. Al no encontrarse en el lugar persona legalmente habilitada que pueda hacerse cargo inmediato y seguro del vehiculo, la autoridad adopta la medida necesaria para retirar el vehiculo de la via y evitar la continuacion de la conducta. Se deja constancia de que la medida no se funda en una causal automatica de "sin licencia = deposito", sino en la imposibilidad de permitir que el vehiculo continue bajo el mando de persona no habilitada y en la falta de alternativa inmediata legalmente viable, tomando en cuenta el marco de retiro y remision previsto para supuestos expresos en los articulos 700 y 702.';
    private const FUNDAMENTOS_EXCLUIDOS_OPERATIVO = [
        'ART420_FIV_IA_B_TRANSPORTE_PUBLICO_ESCOLAR',
        'ART465_FXI_POLARIZADO_MAYOR_20',
        'ART519_FIV_IA_NO_MOVER_SINIESTRO_DANOS',
    ];
    private const TEXTOS_EXCLUIDOS_OPERATIVO = [
        'POLARIZADO',
        'TRANSPORTE PUBLICO',
        'SERVICIO PUBLICO',
        'SINIESTRO',
        'COMPETENCIAS DE VELOCIDAD',
        'PLACAS DE DEMOSTRACION',
        'SIN REGISTRO PREVIO EN REV',
        'PLACAS FORANEAS SIN REGISTRO PREVIO',
        'REGISTRO DE VISITA VENCIDO',
        'ESTACIONAR',
        'CERRAR U OBSTRUIR CIRCULACION',
        'OBSTRUIR CIRCULACION',
        'REPARACIONES A VEHICULOS',
        'REPARAR VEHICULO',
        'RESERVAR ESTACIONAMIENTO',
        'REQUERIMIENTO DE RETIRO',
        'ESPACIOS ESPECIALES DE ASCENSO',
        'ASCENSO DESCENSO TIEMPO EXCEDIDO',
    ];

    public function meta(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'ok' => true,
            'data' => [
                'operativo_nombre' => self::NOMBRE_OPERATIVO,
                'estados' => [
                    'activo' => 'Activo',
                    'cerrado' => 'Cerrado',
                    'cancelado' => 'Cancelado',
                ],
                'abilities' => $this->abilitiesPayload($user),
                'fundamentos_corralon' => $this->fundamentosCorralonPayload(),
                'fundamentos_persona' => $this->fundamentosPersonaPayload(),
            ],
        ]);
    }

    public function gruasSiniestros(Request $request)
    {
        abort_unless($request->user(), 403);

        $gruas = Grua::query()
            ->select(['id', 'nombre', 'direccion', 'ubicacion_corralon', 'telefono', 'email', 'created_at'])
            ->whereHas('unidades', function ($query) {
                $query->where('unidades.id', self::UNIDAD_SINIESTROS);
            })
            ->with('unidades:id,nombre,slug')
            ->with('delegaciones:id,clave,nombre,municipio')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $query = ConduceLegalidadOperativo::query()
            ->with('creador')
            ->withCount([
                'capturas',
                'capturas as mis_capturas_count' => function ($capturas) use ($user) {
                    $capturas->where('created_by', $user->id);
                },
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($request->filled('estado') && in_array($request->query('estado'), self::ESTADOS, true)) {
            $query->where('estado', $request->query('estado'));
        } elseif (!$this->canManage($user) || !$request->boolean('incluir_cerrados')) {
            $query->where('estado', 'activo');
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($sub) use ($buscar) {
                $sub->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('municipio', 'like', "%{$buscar}%")
                    ->orWhere('lugar', 'like', "%{$buscar}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 30), 100));
        $page = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload($user),
            'data' => $page->getCollection()
                ->map(fn (ConduceLegalidadOperativo $operativo) => $this->operativoPayload($operativo, $user))
                ->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function storeOperativo(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canCreateOperativo($user), 403);

        $validated = $request->validate($this->operativoRules());
        $now = now();

        $operativo = ConduceLegalidadOperativo::create([
            'client_uuid' => $this->nullableString($validated['client_uuid'] ?? null),
            'nombre' => self::NOMBRE_OPERATIVO,
            'fecha' => $validated['fecha'] ?? $now->toDateString(),
            'hora_inicio' => $validated['hora_inicio'] ?? $now->format('H:i:s'),
            'municipio' => $this->nullableString($validated['municipio'] ?? null),
            'lugar' => $this->nullableString($validated['lugar'] ?? null),
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'coordenadas_texto' => $this->nullableString($validated['coordenadas_texto'] ?? null),
            'estado' => $validated['estado'] ?? 'activo',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $operativo->load('creador');

        return response()->json([
            'ok' => true,
            'message' => 'Operativo creado correctamente.',
            'data' => $this->operativoPayload($operativo, $user, collect()),
        ], 201);
    }

    public function show(Request $request, ConduceLegalidadOperativo $operativo)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $operativo->loadMissing(['creador', 'actualizador', 'cerrador']);

        $capturasQuery = $operativo->capturas()
            ->with(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->scopeCapturas($capturasQuery, $user);
        $capturas = $capturasQuery->get();

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload($user),
            'data' => $this->operativoPayload($operativo, $user, $capturas),
        ]);
    }

    public function updateOperativo(Request $request, ConduceLegalidadOperativo $operativo)
    {
        $user = $request->user();
        abort_unless($this->canManage($user), 403);

        $validated = $request->validate($this->operativoRules($operativo));
        $oldEstado = $operativo->estado;

        $operativo->fill([
            'nombre' => self::NOMBRE_OPERATIVO,
            'fecha' => $validated['fecha'] ?? $operativo->fecha,
            'hora_inicio' => array_key_exists('hora_inicio', $validated) ? $validated['hora_inicio'] : $operativo->hora_inicio,
            'hora_cierre' => array_key_exists('hora_cierre', $validated) ? $validated['hora_cierre'] : $operativo->hora_cierre,
            'municipio' => array_key_exists('municipio', $validated) ? $this->nullableString($validated['municipio']) : $operativo->municipio,
            'lugar' => array_key_exists('lugar', $validated) ? $this->nullableString($validated['lugar']) : $operativo->lugar,
            'lat' => array_key_exists('lat', $validated) ? $validated['lat'] : $operativo->lat,
            'lng' => array_key_exists('lng', $validated) ? $validated['lng'] : $operativo->lng,
            'coordenadas_texto' => array_key_exists('coordenadas_texto', $validated) ? $this->nullableString($validated['coordenadas_texto']) : $operativo->coordenadas_texto,
            'estado' => $validated['estado'] ?? $operativo->estado,
            'updated_by' => $user->id,
        ]);

        if ($oldEstado === 'activo' && in_array($operativo->estado, ['cerrado', 'cancelado'], true)) {
            $operativo->closed_by = $user->id;
            $operativo->hora_cierre = $operativo->hora_cierre ?: now()->format('H:i:s');
        }

        $operativo->save();
        $operativo->load(['creador', 'actualizador', 'cerrador']);

        return response()->json([
            'ok' => true,
            'message' => 'Operativo actualizado correctamente.',
            'data' => $this->operativoPayload($operativo, $user),
        ]);
    }

    public function destroyOperativo(Request $request, ConduceLegalidadOperativo $operativo)
    {
        $user = $request->user();
        abort_unless($this->canDeleteOperativo($user), 403);

        $operativo->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Operativo eliminado correctamente.',
        ]);
    }

    public function storeCaptura(Request $request, ConduceLegalidadOperativo $operativo)
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($operativo->estado !== 'activo') {
            throw ValidationException::withMessages([
                'operativo' => 'El operativo no esta activo.',
            ]);
        }

        $validated = $request->validate($this->capturaRules());
        $clientUuid = $this->nullableString($validated['client_uuid'] ?? null);
        if ($clientUuid !== null) {
            $existing = $operativo->capturas()
                ->where('client_uuid', $clientUuid)
                ->with(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos'])
                ->first();

            if ($existing) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Captura guardada correctamente.',
                    'data' => $this->capturaPayload($existing, $user),
                ]);
            }
        }

        $this->assertCapturaHasContent($validated, $request);

        $captura = DB::transaction(function () use ($operativo, $user, $validated, $clientUuid, $request) {
            $now = now();
            $captura = $operativo->capturas()->create([
                'client_uuid' => $clientUuid,
                'created_by' => $user->id,
                'unidad_id' => $user->unidad_id,
                'delegacion_id' => $user->delegacion_id,
                'fecha' => $validated['fecha'] ?? $operativo->fecha ?? $now->toDateString(),
                'hora' => $validated['hora'] ?? $now->format('H:i:s'),
                'municipio' => $this->nullableString($validated['municipio'] ?? null),
                'lugar' => $this->nullableString($validated['lugar'] ?? null),
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'coordenadas_texto' => $this->nullableString($validated['coordenadas_texto'] ?? null),
                'narrativa' => $this->nullableString($validated['narrativa'] ?? null),
                'observaciones' => $this->nullableString($validated['observaciones'] ?? null),
                'rnd_data' => $this->canUseRnd($user) ? $this->normalizeRndData($validated['rnd_data'] ?? null) : null,
            ]);

            $this->replaceVehiculos($captura, $validated['vehiculos'] ?? []);
            $this->replacePersonas($captura, $validated['personas'] ?? []);
            $this->storeFotos($captura, $request, $user);

            return $captura;
        });

        $captura->load(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos']);

        return response()->json([
            'ok' => true,
            'message' => 'Captura guardada correctamente.',
            'data' => $this->capturaPayload($captura, $user),
        ], 201);
    }

    public function updateCaptura(Request $request, ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura)
    {
        $user = $request->user();
        abort_unless($captura->operativo_id === $operativo->id, 404);
        abort_unless($this->canEditCaptura($user, $captura), 403);

        $validated = $request->validate($this->capturaRules());
        $this->assertCapturaHasContent($validated, $request, $captura);

        DB::transaction(function () use ($captura, $validated, $request, $user) {
            $captura->fill([
                'fecha' => $validated['fecha'] ?? $captura->fecha,
                'hora' => $validated['hora'] ?? $captura->hora,
                'municipio' => array_key_exists('municipio', $validated) ? $this->nullableString($validated['municipio']) : $captura->municipio,
                'lugar' => array_key_exists('lugar', $validated) ? $this->nullableString($validated['lugar']) : $captura->lugar,
                'lat' => array_key_exists('lat', $validated) ? $validated['lat'] : $captura->lat,
                'lng' => array_key_exists('lng', $validated) ? $validated['lng'] : $captura->lng,
                'coordenadas_texto' => array_key_exists('coordenadas_texto', $validated) ? $this->nullableString($validated['coordenadas_texto']) : $captura->coordenadas_texto,
                'narrativa' => array_key_exists('narrativa', $validated) ? $this->nullableString($validated['narrativa']) : $captura->narrativa,
                'observaciones' => array_key_exists('observaciones', $validated) ? $this->nullableString($validated['observaciones']) : $captura->observaciones,
                'rnd_data' => ($this->canUseRnd($user) && array_key_exists('rnd_data', $validated))
                    ? $this->normalizeRndData($validated['rnd_data'])
                    : $captura->rnd_data,
            ]);
            $captura->save();

            $this->replaceVehiculos($captura, $validated['vehiculos'] ?? []);
            $this->replacePersonas($captura, $validated['personas'] ?? []);
            $this->storeFotos($captura, $request, $user);
        });

        $captura->load(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos']);

        return response()->json([
            'ok' => true,
            'message' => 'Captura actualizada correctamente.',
            'data' => $this->capturaPayload($captura, $user),
        ]);
    }

    public function destroyCaptura(Request $request, ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura)
    {
        $user = $request->user();
        abort_unless($captura->operativo_id === $operativo->id, 404);
        abort_unless($this->canDeleteCaptura($user), 403);

        $captura->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Captura eliminada correctamente.',
        ]);
    }

    public function sendRndChatbot(Request $request, ConduceLegalidadOperativo $operativo, WhatsAppCloudService $whatsApp)
    {
        $user = $request->user();
        abort_unless($this->canUseRnd($user), 403);

        $validated = $request->validate([
            'captura_id' => ['nullable', 'integer'],
            'rnd_data' => ['required', 'array'],
            'rnd_data.*' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:12000'],
            'solicitante_nombre' => ['nullable', 'string', 'max:255'],
            'solicitante_telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $rndData = $this->normalizeRndData($validated['rnd_data'] ?? []);
        if ($rndData === null) {
            throw ValidationException::withMessages([
                'rnd_data' => 'Captura al menos un dato RND.',
            ]);
        }

        $captura = null;
        $capturaId = (int) ($validated['captura_id'] ?? 0);
        if ($capturaId > 0) {
            $captura = $operativo->capturas()->whereKey($capturaId)->first();
            abort_unless($captura, 404);
            abort_unless($this->canEditCaptura($user, $captura), 403);

            $captura->rnd_data = $rndData;
            $captura->save();
        }

        $usuario = $this->nullableString($validated['solicitante_nombre'] ?? null)
            ?: $this->nombreUsuario($user);
        $telefono = $this->nullableString($validated['solicitante_telefono'] ?? null)
            ?: $this->nullableString(data_get($user, 'telefono'))
            ?: $this->nullableString(data_get($user, 'personal.telefono'))
            ?: 'SIN DATO';
        $referencia = 'Operativo ' . $operativo->id . ($captura ? ' / Captura ' . $captura->id : '');
        $message = $this->nullableString($validated['message'] ?? null)
            ?: $this->rndMessage($rndData, $usuario, $telefono, $referencia);

        $to = (string) config('services.whatsapp.conduce_legalidad.rnd_chatbot_to', '5214433163728');
        $template = (string) config('services.whatsapp.conduce_legalidad.rnd_chatbot_template', 'solicitud_rnd_faltas_administrativas');
        $language = (string) config('services.whatsapp.conduce_legalidad.rnd_chatbot_template_language', 'es_MX');

        $response = $whatsApp->sendTemplate($to, $template, [
            $usuario,
            $telefono,
            $referencia,
            $message,
        ], $language);

        if (!($response['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => 'Meta no acepto la solicitud RND. Revisa la plantilla/configuracion de WhatsApp.',
                'meta' => $response['body'] ?? null,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Solicitud RND enviada al chatbot.',
            'data' => [
                'to' => preg_replace('/\D+/', '', $to) ?: $to,
                'template' => $template,
                'language' => $language,
                'referencia' => $referencia,
            ],
        ]);
    }
    public function nativeShareOperativo(Request $request, ConduceLegalidadOperativo $operativo)
    {
        $user = $request->user();
        abort_unless($this->canManage($user), 403);

        $operativo->loadMissing(['creador']);

        $capturas = $operativo->capturas()
            ->with(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $texto = $this->tarjetaTotalesOperativo($operativo, $capturas, $user);

        return response()->json([
            'ok' => true,
            'data' => [
                'title' => 'Resumen Operativo Conduce con Legalidad',
                'texto' => trim($texto),
                'message' => trim($texto),
                'media' => [],
                'fotos' => [],
                'operativo_id' => $operativo->id,
                'tipo' => 'operativo_totales',
            ],
        ]);
    }

    public function nativeShareCaptura(Request $request, ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura)
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless($captura->operativo_id === $operativo->id, 404);

        $query = $operativo->capturas()->whereKey($captura->id);
        $this->scopeCapturas($query, $user);
        abort_unless($query->exists(), 404);

        $operativo->loadMissing(['creador']);
        $captura->loadMissing(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos']);

        $texto = $this->tarjetaCapturaOperativo($operativo, $captura, $user);
        $fotos = $captura->fotos
            ->map(fn (ConduceLegalidadFoto $foto) => $this->storageUrl($foto->foto_path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'data' => [
                'title' => 'Captura Operativo Conduce con Legalidad',
                'texto' => trim($texto),
                'message' => trim($texto),
                'foto' => $fotos[0] ?? null,
                'fotos' => $fotos,
                'media' => $fotos,
                'operativo_id' => $operativo->id,
                'captura_id' => $captura->id,
                'tipo' => 'captura_individual',
            ],
        ]);
    }

    public function descargarIphCaptura(
        Request $request,
        ConduceLegalidadOperativo $operativo,
        ConduceLegalidadCaptura $captura,
        IphPuestaDisposicionDocxService $docxService
    ) {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless($captura->operativo_id === $operativo->id, 404);

        $query = $operativo->capturas()->whereKey($captura->id);
        $this->scopeCapturas($query, $user);
        abort_unless($query->exists(), 404);

        $operativo->loadMissing(['creador']);
        $captura->loadMissing([
            'creador.personal.unidad',
            'creador.unidad',
            'unidad',
            'delegacion',
            'vehiculos.infraccion',
            'vehiculos.gruaRelacion',
            'vehiculos.corralonRelacion',
            'personas.infraccion',
            'fotos',
        ]);

        [$path, $filename] = $docxService->generar(
            $this->hechoTemporalIph($operativo, $captura),
            $this->mapearIphDesdeCaptura($operativo, $captura, $user)
        );

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function hechoTemporalIph(ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura): Hechos
    {
        $folio = $this->folioCaptura($operativo, $captura);
        $hecho = new Hechos();
        $hecho->forceFill([
            'id' => $captura->id,
            'folio_c5i' => $folio,
            'fecha' => $captura->fecha ?: $operativo->fecha,
            'hora' => $captura->hora ?: $operativo->hora_inicio,
            'tipo_hecho' => 'RETIRO DE VEHICULO DERIVADO DE OPERATIVO CONDUCE CON LEGALIDAD',
            'municipio' => $captura->municipio ?: $operativo->municipio,
            'calle' => $captura->lugar ?: $operativo->lugar,
            'ubicacion_formateada' => $captura->lugar ?: $operativo->lugar,
            'lat' => $captura->lat ?: $operativo->lat,
            'lng' => $captura->lng ?: $operativo->lng,
        ]);

        return $hecho;
    }

    private function mapearIphDesdeCaptura(ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura, $user): array
    {
        $folio = $this->folioCaptura($operativo, $captura);
        $fecha = optional($captura->fecha ?: $operativo->fecha)->toDateString();
        $hora = $this->horaCorta($captura->hora ?: $operativo->hora_inicio);
        $lugar = $this->nullableString($captura->lugar) ?: $this->nullableString($operativo->lugar);
        $municipio = $this->nullableString($captura->municipio) ?: $this->nullableString($operativo->municipio) ?: 'Morelia';
        $unidadNombre = $this->nullableString(optional($captura->unidad)->nombre)
            ?: $this->nullableString(optional($captura->creador)->unidad->nombre ?? null)
            ?: 'Coordinación del Agrupamiento de Seguridad Vial';
        $agenteNombre = $this->nullableString(optional($captura->creador)->name)
            ?: $this->nullableString($user->name ?? null)
            ?: 'Elemento actuante';
        $narrativaOperativa = $this->narrativaIphConduceLegalidad($operativo, $captura);
        $dinamica = $this->dinamicaIphConduceLegalidad($operativo, $captura, $narrativaOperativa);
        $fundamento = $this->fundamentosIphCaptura($captura);
        $primerFoto = $captura->fotos->first();

        return [
            'opciones' => [
                'incluir_parte_informativo' => false,
            ],
            'hecho' => [
                'id' => $captura->id,
                'folio_c5i' => $folio,
                'fecha' => $fecha,
                'hora' => $hora,
                'situacion' => 'Atendido',
                'perito' => $agenteNombre,
                'creador_nombre' => $agenteNombre,
                'unidad_numero_economico' => '',
                'unidad_org_id' => (int) ($captura->unidad_id ?: self::UNIDAD_SEGURIDAD_VIAL),
                'unidad_org_nombre' => $unidadNombre,
                'delegacion_id' => $captura->delegacion_id,
                'delegacion_nombre' => optional($captura->delegacion)->nombre,
                'oficio_mp' => $folio,
                'vehiculos_mp' => $captura->vehiculos->count(),
                'personas_mp' => $captura->personas->count(),
                'lesionados_count' => 0,
                'fallecidos_count' => 0,
                'tipo_hecho' => 'RETIRO DE VEHICULO DERIVADO DE OPERATIVO CONDUCE CON LEGALIDAD',
                'tiempo' => '',
                'clima' => '',
                'condiciones' => '',
                'causas' => $this->causaIphCaptura($captura),
                'colision_camino' => '',
                'dinamica_hecho' => $dinamica,
                'narrativa_operativa' => $narrativaOperativa,
                'conclusion_causa' => $this->conclusionCausaIphCaptura($captura, $fundamento),
                'conclusion_disposicion' => $this->conclusionDisposicionIphCaptura($captura, $fundamento),
                'ubicacion' => [
                    'calle' => $lugar,
                    'colonia' => '',
                    'entre_calles' => '',
                    'municipio' => $municipio,
                    'codigo_postal' => '',
                    'lat' => $captura->lat ?: $operativo->lat,
                    'lng' => $captura->lng ?: $operativo->lng,
                    'ubicacion_formateada' => $lugar,
                    'place_id' => null,
                ],
            ],
            'puesta_disposicion' => [
                'id' => null,
                'folio' => $folio,
                'numero_puesta' => $folio,
                'anio' => now('America/Mexico_City')->format('Y'),
                'tipo_puesta' => 'RETIRO Y RESGUARDO DE VEHICULO',
                'motivo' => $fundamento,
                'estatus' => 'Generado desde operativo',
                'nombre_policia' => $agenteNombre,
                'nombre_mp' => '',
                'autoridad_receptora' => 'AUTORIDAD COMPETENTE PARA LOS FINES LEGALES PROCEDENTES',
                'area' => $unidadNombre,
                'carpeta_investigacion' => '',
                'oficio' => $folio,
                'fecha_puesta' => $fecha,
                'hora_puesta' => $hora,
                'lugar_puesta' => $lugar,
                'narrativa' => $dinamica,
                'narrativa_operativa' => $narrativaOperativa,
                'conclusion_causa' => $this->conclusionCausaIphCaptura($captura, $fundamento),
                'conclusion_disposicion' => $this->conclusionDisposicionIphCaptura($captura, $fundamento),
                'observaciones' => $captura->observaciones,
                'unidad_id' => (int) ($captura->unidad_id ?: self::UNIDAD_SEGURIDAD_VIAL),
                'unidad_nombre' => $unidadNombre,
                'delegacion_id' => $captura->delegacion_id,
                'delegacion_nombre' => optional($captura->delegacion)->nombre,
                'destacamento_id' => null,
                'destacamento_nombre' => null,
            ],
            'vehiculos_hecho' => $this->vehiculosIphCaptura($captura),
            'conductores_hecho' => $this->conductoresIphCaptura($captura),
            'lesionados_hecho' => [],
            'personas' => $this->personasIphCaptura($captura),
            'vehiculos' => $this->vehiculosPuestaIphCaptura($captura),
            'objetos' => [],
            'anexos' => [
                'foto_lugar' => $primerFoto ? $primerFoto->foto_path : null,
                'foto_situacion' => null,
                'iph_delegaciones_path' => null,
                'archivo_puesta' => null,
                'croquis_preview' => null,
            ],
        ];
    }

    private function folioCaptura(ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura): string
    {
        return 'CL-' . $operativo->id . '-' . $captura->id;
    }

    private function vehiculosIphCaptura(ConduceLegalidadCaptura $captura): array
    {
        return $captura->vehiculos->values()->map(function (ConduceLegalidadVehiculo $vehiculo, int $index) use ($captura) {
            $persona = $this->personaPorIndice($captura, $index);
            $grua = $vehiculo->gruaRelacion;
            $corralon = $vehiculo->corralonRelacion;

            return [
                'id' => $vehiculo->id,
                'tipo' => $vehiculo->tipo ?: ($vehiculo->tipo_general ?: 'Vehiculo'),
                'marca' => $vehiculo->marca,
                'linea' => $vehiculo->linea,
                'modelo' => $vehiculo->modelo,
                'color' => $vehiculo->color,
                'placas' => $vehiculo->placas,
                'estado_placas' => $vehiculo->estado_placas,
                'serie' => $vehiculo->serie,
                'capacidad_personas' => $vehiculo->capacidad_personas,
                'tipo_servicio' => $vehiculo->tipo_servicio,
                'tarjeta_circulacion_nombre' => $vehiculo->tarjeta_circulacion_nombre,
                'foto' => null,
                'grua' => $vehiculo->grua ?: optional($grua)->nombre,
                'grua_id' => $vehiculo->grua_id,
                'grua_nombre' => optional($grua)->nombre ?: $vehiculo->grua,
                'grua_direccion' => optional($grua)->direccion ?: optional($corralon)->direccion,
                'grua_ubicacion_corralon' => optional($grua)->ubicacion_corralon ?: optional($corralon)->ubicacion_corralon,
                'corralon' => $vehiculo->corralon ?: optional($corralon)->nombre,
                'monto_danos' => $vehiculo->monto_danos,
                'partes_danadas' => $vehiculo->partes_danadas,
                'aseguradora' => $vehiculo->aseguradora,
                'antecedente_vehiculo' => (bool) $vehiculo->antecedente_vehiculo,
                'conductores' => $persona ? [$this->conductorIph($persona)] : [],
            ];
        })->values()->all();
    }

    private function conductoresIphCaptura(ConduceLegalidadCaptura $captura): array
    {
        return $captura->personas->values()->map(function (ConduceLegalidadPersona $persona, int $index) use ($captura) {
            $vehiculo = $captura->vehiculos->get($index) ?: $captura->vehiculos->first();
            $data = $this->conductorIph($persona);
            $data['vehiculo_id'] = optional($vehiculo)->id;
            $data['vehiculo_label'] = $vehiculo ? $this->vehiculoLabel($vehiculo) : '';

            return $data;
        })->values()->all();
    }

    private function personasIphCaptura(ConduceLegalidadCaptura $captura): array
    {
        return $captura->personas->values()->map(function (ConduceLegalidadPersona $persona, int $index) use ($captura) {
            $vehiculo = $captura->vehiculos->get($index) ?: $captura->vehiculos->first();

            return [
                'nombre_completo' => $persona->nombre,
                'alias' => null,
                'edad' => $persona->edad,
                'sexo' => $persona->sexo,
                'fecha_nacimiento' => null,
                'curp' => null,
                'rfc' => null,
                'domicilio' => $persona->domicilio,
                'calidad' => 'Persona conductora o interviniente',
                'delito_o_motivo' => $vehiculo ? $this->motivoIphVehiculo($vehiculo) : 'Operativo Conduce con Legalidad',
                'orden_aprehension' => false,
                'mandamiento_judicial' => null,
                'observaciones' => $persona->observaciones,
            ];
        })->values()->all();
    }

    private function vehiculosPuestaIphCaptura(ConduceLegalidadCaptura $captura): array
    {
        return $captura->vehiculos->values()->map(fn (ConduceLegalidadVehiculo $vehiculo) => [
            'vehiculo_id' => $vehiculo->id,
            'tipo' => $vehiculo->tipo ?: ($vehiculo->tipo_general ?: 'Vehiculo'),
            'marca' => $vehiculo->marca,
            'submarca' => $vehiculo->linea,
            'modelo' => $vehiculo->modelo,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas,
            'serie' => $vehiculo->serie,
            'calidad' => 'Vehiculo retirado o resguardado',
            'motivo_relacion' => $this->motivoIphVehiculo($vehiculo),
            'con_reporte_robo' => false,
            'numero_reporte_robo' => null,
            'observaciones' => $vehiculo->observaciones,
        ])->values()->all();
    }

    private function conductorIph(ConduceLegalidadPersona $persona): array
    {
        return [
            'nombre' => $persona->nombre,
            'edad' => $persona->edad,
            'sexo' => $persona->sexo,
            'domicilio' => $persona->domicilio,
            'ocupacion' => $persona->ocupacion,
            'numero_licencia' => $persona->numero_licencia,
            'tipo_licencia' => $persona->tipo_licencia,
            'estado_licencia' => $persona->estado_licencia,
            'vigencia_licencia' => optional($persona->vigencia_licencia)->toDateString(),
            'antecedentes' => false,
            'certificado_lesiones' => null,
            'certificado_alcoholemia' => null,
            'aliento_etilico' => null,
        ];
    }

    private function narrativaIphConduceLegalidad(ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura): string
    {
        $fundamento = $this->fundamentosIphCaptura($captura);
        $vehiculos = $captura->vehiculos->values()
            ->map(fn (ConduceLegalidadVehiculo $vehiculo) => $this->vehiculoLabel($vehiculo))
            ->filter()
            ->implode('; ');
        $personas = $captura->personas->pluck('nombre')->filter()->implode(', ');
        $lugar = $this->nullableString($captura->lugar) ?: $this->nullableString($operativo->lugar) ?: 'el punto del operativo';
        $capturada = $this->nullableString($captura->narrativa);

        $lineas = [
            'Durante el Operativo Conduce con Legalidad, en ' . $lugar . ', el personal actuante detecto el vehiculo o vehiculos registrados en el sistema' . ($vehiculos ? ': ' . $vehiculos : '') . '.',
            $personas ? 'Se relaciono como persona conductora o interviniente a: ' . $personas . '.' : null,
            $capturada ? 'Narrativa asentada por el elemento actuante: ' . $capturada : null,
            'La intervencion se documenta con base en el fundamento especifico capturado para la conducta observada: ' . $fundamento . '.',
            'La medida de retiro o resguardo se registra para impedir la continuacion de la conducta y preservar la seguridad vial. Cuando el supuesto operativo derive de falta de licencia o permiso vigente, se deja constancia de que no se asienta como causal automatica de deposito; se documenta la falta de habilitacion juridica para continuar conduciendo y la ausencia de alternativa inmediata legalmente viable para que una persona habilitada se haga cargo del vehiculo en condiciones seguras.',
            'El traslado, entrega, inventario y resguardo del vehiculo deberan quedar soportados con los datos de grua, corralon, fotografias, boleta de infraccion y demas constancias operativas disponibles.',
        ];

        return collect($lineas)->filter()->implode(' ');
    }

    private function dinamicaIphConduceLegalidad(ConduceLegalidadOperativo $operativo, ConduceLegalidadCaptura $captura, string $narrativaOperativa): string
    {
        $fecha = optional($captura->fecha ?: $operativo->fecha)->format('d-m-Y');
        $hora = $this->horaCorta($captura->hora ?: $operativo->hora_inicio);

        return 'El dia ' . ($fecha ?: 'no especificado') . ', aproximadamente a las ' . ($hora ?: 'hora no especificada') . ' horas, en el marco del Operativo Conduce con Legalidad, se realizo la intervencion preventiva y administrativa descrita. ' . $narrativaOperativa;
    }

    private function conclusionCausaIphCaptura(ConduceLegalidadCaptura $captura, string $fundamento): string
    {
        return 'UNICA.- La intervencion documentada corresponde a la deteccion de una conducta en materia de transito y seguridad vial dentro del Operativo Conduce con Legalidad, sustentada en el fundamento asentado en la captura: ' . $fundamento . '. La medida descrita se registra para hacer cesar la continuacion de la conducta y preservar la seguridad vial, sin prejuzgar responsabilidades diversas a las que determine la autoridad competente.';
    }

    private function conclusionDisposicionIphCaptura(ConduceLegalidadCaptura $captura, string $fundamento): string
    {
        $vehiculosTexto = $captura->vehiculos->count() === 1 ? 'el vehiculo registrado' : 'los vehiculos registrados';
        $destinos = $captura->vehiculos->map(function (ConduceLegalidadVehiculo $vehiculo) {
            return $this->nullableString($vehiculo->corralon)
                ?: $this->nullableString($vehiculo->grua)
                ?: $this->nullableString(optional($vehiculo->corralonRelacion)->nombre)
                ?: $this->nullableString(optional($vehiculo->gruaRelacion)->nombre);
        })->filter()->unique()->implode(' y ');

        $texto = 'Con base en el fundamento capturado para la intervencion (' . $fundamento . '), se deja constancia de la puesta a resguardo de ' . $vehiculosTexto;

        if ($destinos !== '') {
            $texto .= ' en ' . mb_strtoupper($destinos, 'UTF-8');
        }

        return $texto . ', para los fines administrativos y legales procedentes, quedando sujeto a la documentacion complementaria, inventario, boleta y cadena de resguardo que correspondan.';
    }

    private function causaIphCaptura(ConduceLegalidadCaptura $captura): string
    {
        $causa = $captura->vehiculos
            ->map(fn (ConduceLegalidadVehiculo $vehiculo) => $this->motivoIphVehiculo($vehiculo))
            ->filter()
            ->unique()
            ->implode('; ');

        return $causa ?: 'OPERATIVO CONDUCE CON LEGALIDAD';
    }

    private function fundamentosIphCaptura(ConduceLegalidadCaptura $captura): string
    {
        $fundamentos = $captura->vehiculos
            ->map(fn (ConduceLegalidadVehiculo $vehiculo) => $this->motivoIphVehiculo($vehiculo))
            ->filter()
            ->unique()
            ->values();

        if ($fundamentos->isNotEmpty()) {
            return $fundamentos->implode('; ');
        }

        return 'Ley y Reglamento de Transito y Seguridad Vial aplicables al Operativo Conduce con Legalidad';
    }

    private function motivoIphVehiculo(ConduceLegalidadVehiculo $vehiculo): string
    {
        $infraccion = $vehiculo->infraccion;

        return $this->joinText([
            $infraccion ? $this->nullableString($infraccion->referencia_legal_corta) : null,
            $this->nullableString($vehiculo->fundamento_legal),
            $infraccion ? $this->nullableString($infraccion->fundamento_legal) : null,
            $infraccion ? $this->nullableString($infraccion->resumen_sanciones) : null,
            $this->nullableString($vehiculo->motivo_retencion),
            $infraccion ? $this->textoOperativoInfraccion($infraccion) : null,
        ], ' - ') ?: 'Fundamento operativo capturado para retiro o resguardo del vehiculo';
    }

    private function personaPorIndice(ConduceLegalidadCaptura $captura, int $index): ?ConduceLegalidadPersona
    {
        if ($captura->personas->isEmpty()) {
            return null;
        }

        return $captura->personas->get($index) ?: $captura->personas->first();
    }

    private function vehiculoLabel(ConduceLegalidadVehiculo $vehiculo): string
    {
        return $this->joinText([
            $vehiculo->marca,
            $vehiculo->linea,
            $vehiculo->modelo,
            $vehiculo->tipo_general ?: $vehiculo->tipo,
            $vehiculo->color,
            $vehiculo->placas ? 'placas ' . $vehiculo->placas : null,
            $vehiculo->serie ? 'serie ' . $vehiculo->serie : null,
        ], ' ');
    }

    private function joinText(array $values, string $separator): string
    {
        return collect($values)
            ->map(fn ($value) => trim((string) ($value ?? '')))
            ->filter()
            ->unique(fn ($value) => mb_strtoupper($value, 'UTF-8'))
            ->implode($separator);
    }

    private function tarjetaTotalesOperativo(ConduceLegalidadOperativo $operativo, $capturas, $user): string
    {
        $pairs = [];
        foreach ($capturas as $captura) {
            foreach ($captura->vehiculos as $vehiculo) {
                $pairs[] = [$captura, $vehiculo];
            }
        }

        $resguardados = array_values(array_filter(
            $pairs,
            fn (array $pair) => $this->vehiculoResguardado($pair[1])
        ));

        $lines = $this->tarjetaHeaderLines((int) ($user->unidad_id ?? 0), $user->delegacion_id ?? null);
        $lines[] = 'TEMA: RESUMEN DE VEHICULOS RESGUARDADOS';
        $lines[] = self::NOMBRE_OPERATIVO;
        $lines[] = '';
        $lines[] = 'OPERATIVO ID: ' . $operativo->id;
        $lines[] = 'PUNTO: ' . $this->upper($operativo->lugar ?: 'SIN DATO');
        $lines[] = 'MUNICIPIO: ' . $this->upper($operativo->municipio ?: 'SIN DATO');
        $lines[] = 'FECHA: ' . $this->fechaHoraOperativo($operativo) . ' Hrs.';
        $lines[] = 'ESTADO: ' . $this->upper($operativo->estado ?: 'SIN DATO');
        $this->appendCoordenadas($lines, $operativo->lat, $operativo->lng, $operativo->coordenadas_texto);
        $lines[] = '';
        $lines[] = 'TOTAL GENERAL:';
        $lines[] = 'Capturas registradas: ' . $capturas->count();
        $lines[] = 'Vehiculos capturados: ' . count($pairs);
        $lines[] = 'Vehiculos resguardados: ' . count($resguardados);
        $lines[] = 'Personas registradas: ' . $capturas->sum(fn (ConduceLegalidadCaptura $captura) => $captura->personas->count());

        $this->appendDesglose($lines, $resguardados, 'CORRALON', fn (ConduceLegalidadVehiculo $vehiculo) => $this->valorCorralon($vehiculo));
        $this->appendDesglose($lines, $resguardados, 'GRUA', fn (ConduceLegalidadVehiculo $vehiculo) => $this->valorGrua($vehiculo));

        $lines[] = '';
        $lines[] = 'VEHICULOS RESGUARDADOS:';
        if (count($resguardados) === 0) {
            $lines[] = 'SIN VEHICULOS RESGUARDADOS REGISTRADOS.';
        } else {
            foreach ($resguardados as $index => [$captura, $vehiculo]) {
                $lines[] = '';
                $lines[] = 'VEHICULO ' . $this->letraVehiculo($index) . ') ' . $this->vehiculoResumen($vehiculo);
                $lines[] = 'Captura #' . $captura->id . ' - ' . $this->nombreUsuario($captura->creador);
                $this->appendVehiculoRetencion($lines, $vehiculo);
            }
        }

        $lines[] = '';
        $lines[] = 'INFORMA ' . $this->upper($this->unidadOperativaTexto((int) ($user->unidad_id ?? 0)));

        return implode("\n", $lines);
    }

    private function tarjetaCapturaOperativo(
        ConduceLegalidadOperativo $operativo,
        ConduceLegalidadCaptura $captura,
        $user
    ): string
    {
        $unidadId = (int) ($captura->unidad_id ?: ($user->unidad_id ?? 0));
        $delegacionId = $captura->delegacion_id ?: ($user->delegacion_id ?? null);

        $lines = $this->tarjetaHeaderLines($unidadId, $delegacionId);
        $lines[] = 'TEMA: CAPTURA INDIVIDUAL';
        $lines[] = self::NOMBRE_OPERATIVO;
        $lines[] = '';
        $lines[] = 'OPERATIVO ID: ' . $operativo->id;
        $lines[] = 'CAPTURA ID: ' . $captura->id;
        $lines[] = 'PUNTO OPERATIVO: ' . $this->upper($operativo->lugar ?: 'SIN DATO');
        $lines[] = 'LUGAR CAPTURA: ' . $this->upper($captura->lugar ?: $operativo->lugar ?: 'SIN DATO');
        $lines[] = 'MUNICIPIO: ' . $this->upper($captura->municipio ?: $operativo->municipio ?: 'SIN DATO');
        $lines[] = 'FECHA: ' . $this->fechaHoraCaptura($captura) . ' Hrs.';
        $lines[] = 'USUARIO: ' . $this->upper($this->nombreUsuario($captura->creador));
        $this->appendCoordenadas($lines, $captura->lat, $captura->lng, $captura->coordenadas_texto);

        if ($this->nullableString($captura->narrativa) !== null) {
            $lines[] = '';
            $lines[] = 'NARRATIVA:';
            $lines[] = trim((string) $captura->narrativa);
        }

        $lines[] = '';
        $lines[] = 'VEHICULOS:';
        if ($captura->vehiculos->count() === 0) {
            $lines[] = 'SIN VEHICULOS CAPTURADOS.';
        } else {
            foreach ($captura->vehiculos as $index => $vehiculo) {
                $lines[] = '';
                $lines[] = 'VEHICULO ' . $this->letraVehiculo($index) . ')';
                $lines[] = $this->vehiculoResumen($vehiculo);
                $this->appendVehiculoRetencion($lines, $vehiculo);
            }
        }

        $lines[] = '';
        $lines[] = 'PERSONAS:';
        if ($captura->personas->count() === 0) {
            $lines[] = 'SIN PERSONAS CAPTURADAS.';
        } else {
            foreach ($captura->personas as $persona) {
                $lines[] = '- ' . $this->personaResumen($persona);
            }
        }

        if ($captura->fotos->count() > 0) {
            $lines[] = '';
            $lines[] = 'FOTOS ADJUNTAS: ' . $captura->fotos->count();
        }

        $lines[] = '';
        $lines[] = 'INFORMA ' . $this->upper($this->unidadOperativaTexto($unidadId));

        return implode("\n", $lines);
    }

    private function tarjetaHeaderLines(int $unidadId, $delegacionId = null): array
    {
        $lines = [
            'GUARDIA CIVIL',
            '',
            'COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
            '',
            $this->upper($this->unidadOperativaTexto($unidadId)),
        ];

        $delegacion = $this->delegacionTexto($delegacionId);
        if ($delegacion !== null) {
            $lines[] = $delegacion;
        }

        $lines[] = '';

        return $lines;
    }

    private function unidadOperativaTexto(int $unidadId): string
    {
        switch ($unidadId) {
            case self::UNIDAD_SINIESTROS:
                return 'UNIDAD DE ATENCION A SINIESTROS';
            case 2:
                return 'UNIDAD DE DELEGACIONES';
            case self::UNIDAD_SEGURIDAD_VIAL:
                return 'UNIDAD DE SEGURIDAD VIAL';
            case 4:
                return 'UNIDAD DE PROTECCION A CARRETERAS';
            case self::UNIDAD_VIALIDADES_URBANAS:
                return 'UNIDAD DE VIALIDADES URBANAS';
            default:
                return DB::table('unidades')->where('id', $unidadId)->value('nombre') ?: 'SIN DATO';
        }
    }

    private function delegacionTexto($delegacionId): ?string
    {
        $id = (int) ($delegacionId ?? 0);
        if ($id <= 0) {
            return null;
        }

        $nombre = DB::table('delegaciones')->where('id', $id)->value('nombre');
        return $nombre ? 'DELEGACION ' . $this->upper($nombre) : null;
    }

    private function appendDesglose(array &$lines, array $pairs, string $label, callable $resolver): void
    {
        $totales = [];
        foreach ($pairs as [, $vehiculo]) {
            $key = $this->upper($resolver($vehiculo) ?: 'SIN ' . $label . ' REGISTRADA');
            $totales[$key] = ($totales[$key] ?? 0) + 1;
        }

        $lines[] = '';
        $lines[] = 'DESGLOSE POR ' . $label . ':';
        if (count($totales) === 0) {
            $lines[] = '- SIN DATOS.';
            return;
        }

        ksort($totales);
        foreach ($totales as $nombre => $total) {
            $lines[] = '- ' . $nombre . ': ' . $total;
        }
    }

    private function appendVehiculoRetencion(array &$lines, ConduceLegalidadVehiculo $vehiculo): void
    {
        $motivo = $this->nullableString($vehiculo->motivo_retencion);
        if ($motivo !== null) {
            $lines[] = 'Motivo de retencion: ' . $motivo . '.';
        }

        $referencia = $vehiculo->infraccion ? $vehiculo->infraccion->referencia_legal_corta : null;
        if ($this->nullableString($referencia) !== null) {
            $lines[] = 'Fundamento: ' . $referencia . '.';
        }

        $grua = $this->valorGrua($vehiculo);
        if ($grua !== null) {
            $lines[] = 'Grua: ' . $this->upper($grua) . '.';
        }

        $corralon = $this->valorCorralon($vehiculo);
        if ($corralon !== null) {
            $lines[] = 'Corralon: ' . $this->upper($corralon) . '.';
        }
    }

    private function appendCoordenadas(array &$lines, $lat, $lng, ?string $coordenadasTexto): void
    {
        if ($lat !== null && $lng !== null) {
            $lines[] = 'COORDENADAS: ' . $lat . ', ' . $lng;
            $lines[] = 'GOOGLE MAPS: https://www.google.com/maps?q=' . $lat . ',' . $lng;
            return;
        }

        $coordenadas = $this->nullableString($coordenadasTexto);
        if ($coordenadas !== null) {
            $lines[] = 'COORDENADAS: ' . $coordenadas;
        }
    }

    private function vehiculoResumen(ConduceLegalidadVehiculo $vehiculo): string
    {
        return 'Marca ' . $this->valorTexto($vehiculo->marca)
            . ', tipo ' . $this->valorTexto($vehiculo->tipo ?: $vehiculo->tipo_general)
            . ', linea ' . $this->valorTexto($vehiculo->linea)
            . ', color ' . $this->valorTexto($vehiculo->color)
            . ', placas ' . $this->valorTexto($vehiculo->placas ?: 'SIN PLACAS')
            . ', NIV ' . $this->valorTexto($vehiculo->serie) . '.';
    }

    private function personaResumen(ConduceLegalidadPersona $persona): string
    {
        $parts = [$this->valorTexto($persona->nombre)];

        if ($persona->edad !== null) {
            $parts[] = $persona->edad . ' anos';
        }

        if ($this->nullableString($persona->numero_licencia) !== null) {
            $parts[] = 'licencia ' . $persona->numero_licencia;
        }

        if ($this->nullableString($persona->estado_licencia) !== null) {
            $parts[] = 'estado licencia ' . $persona->estado_licencia;
        }

        return implode(', ', $parts) . '.';
    }

    private function vehiculoResguardado(ConduceLegalidadVehiculo $vehiculo): bool
    {
        return (bool) $vehiculo->retencion_vehiculo
            || $this->valorGrua($vehiculo) !== null
            || $this->valorCorralon($vehiculo) !== null;
    }

    private function valorGrua(ConduceLegalidadVehiculo $vehiculo): ?string
    {
        return $this->nullableString($vehiculo->grua)
            ?: ($vehiculo->grua_id ? 'GRUA #' . $vehiculo->grua_id : null);
    }

    private function valorCorralon(ConduceLegalidadVehiculo $vehiculo): ?string
    {
        return $this->nullableString($vehiculo->corralon)
            ?: ($vehiculo->corralon_id ? 'CORRALON #' . $vehiculo->corralon_id : null);
    }

    private function fechaHoraOperativo(ConduceLegalidadOperativo $operativo): string
    {
        return trim(implode(' ', array_filter([
            optional($operativo->fecha)->toDateString(),
            $this->horaCorta($operativo->hora_inicio),
        ]))) ?: 'SIN DATO';
    }

    private function fechaHoraCaptura(ConduceLegalidadCaptura $captura): string
    {
        return trim(implode(' ', array_filter([
            optional($captura->fecha)->toDateString(),
            $this->horaCorta($captura->hora),
        ]))) ?: 'SIN DATO';
    }

    private function horaCorta($hora): ?string
    {
        $text = $this->nullableString($hora);
        return $text === null ? null : substr($text, 0, 5);
    }

    private function letraVehiculo(int $index): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return $index >= 0 && $index < strlen($letters)
            ? $letters[$index]
            : (string) ($index + 1);
    }

    private function nombreUsuario($user): string
    {
        return trim((string) ($user->name ?? $user->nombre ?? 'Usuario'));
    }

    private function valorTexto($value): string
    {
        return $this->upper($this->nullableString($value) ?: 'SIN DATO');
    }

    private function upper($value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? 'SIN DATO' : Str::upper($text);
    }

    private function operativoRules(?ConduceLegalidadOperativo $operativo = null): array
    {
        $ignoreId = $operativo ? $operativo->id : null;

        return [
            'client_uuid' => ['nullable', 'string', 'max:80', Rule::unique('conduce_legalidad_operativos', 'client_uuid')->ignore($ignoreId)],
            'fecha' => ['nullable', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_cierre' => ['nullable', 'date_format:H:i'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'coordenadas_texto' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ];
    }

    private function capturaRules(): array
    {
        return [
            'client_uuid' => ['nullable', 'string', 'max:80'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'coordenadas_texto' => ['nullable', 'string', 'max:255'],
            'narrativa' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'rnd_data' => ['nullable', 'array'],
            'rnd_data.*' => ['nullable', 'string', 'max:2000'],
            'fotos' => ['nullable', 'array', 'max:25'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'vehiculos' => ['nullable', 'array', 'max:100'],
            'vehiculos.*' => ['array'],
            'vehiculos.*.marca' => ['nullable', 'string', 'max:80'],
            'vehiculos.*.modelo' => ['nullable', 'string', 'max:20'],
            'vehiculos.*.tipo_general' => ['nullable', 'string', 'max:80'],
            'vehiculos.*.tipo' => ['nullable', 'string', 'max:80'],
            'vehiculos.*.linea' => ['nullable', 'string', 'max:100'],
            'vehiculos.*.color' => ['nullable', 'string', 'max:50'],
            'vehiculos.*.placas' => ['nullable', 'string', 'max:20'],
            'vehiculos.*.estado_placas' => ['nullable', 'string', 'max:80'],
            'vehiculos.*.serie' => ['nullable', 'string', 'max:30'],
            'vehiculos.*.capacidad_personas' => ['nullable', 'integer', 'min:0', 'max:999'],
            'vehiculos.*.tipo_servicio' => ['nullable', 'string', 'max:80'],
            'vehiculos.*.tarjeta_circulacion_nombre' => ['nullable', 'string', 'max:255'],
            'vehiculos.*.grua_id' => ['nullable', 'integer', 'exists:gruas,id'],
            'vehiculos.*.corralon_id' => ['nullable', 'integer', 'exists:gruas,id'],
            'vehiculos.*.grua' => ['nullable', 'string', 'max:255'],
            'vehiculos.*.corralon' => ['nullable', 'string', 'max:255'],
            'vehiculos.*.aseguradora' => ['nullable', 'string', 'max:255'],
            'vehiculos.*.monto_danos' => ['nullable', 'numeric', 'min:0'],
            'vehiculos.*.partes_danadas' => ['nullable', 'string'],
            'vehiculos.*.antecedente_vehiculo' => ['nullable', 'boolean'],
            'vehiculos.*.raw_tarjeta_qr' => ['nullable', 'string'],
            'vehiculos.*.licencia_punto_infraccion_id' => ['nullable', 'integer', 'exists:licencia_punto_infracciones,id'],
            'vehiculos.*.motivo_retencion' => ['nullable', 'string'],
            'vehiculos.*.persona_habilitada_resguardo' => ['nullable', 'boolean'],
            'vehiculos.*.responsable_habilitado_presente' => ['nullable', 'boolean'],
            'vehiculos.*.observaciones' => ['nullable', 'string'],
            'personas' => ['nullable', 'array', 'max:100'],
            'personas.*' => ['array'],
            'personas.*.nombre' => ['nullable', 'string', 'max:255'],
            'personas.*.telefono' => ['nullable', 'string', 'max:30'],
            'personas.*.domicilio' => ['nullable', 'string', 'max:255'],
            'personas.*.sexo' => ['nullable', 'string', 'max:30'],
            'personas.*.ocupacion' => ['nullable', 'string', 'max:255'],
            'personas.*.edad' => ['nullable', 'integer', 'min:0', 'max:120'],
            'personas.*.tipo_licencia' => ['nullable', 'string', 'max:80'],
            'personas.*.estado_licencia' => ['nullable', 'string', 'max:120'],
            'personas.*.numero_licencia' => ['nullable', 'string', 'max:80'],
            'personas.*.vigencia_licencia' => ['nullable', 'date'],
            'personas.*.permanente' => ['nullable', 'boolean'],
            'personas.*.raw_licencia_qr' => ['nullable', 'string'],
            'personas.*.licencia_punto_infraccion_id' => ['nullable', 'integer', 'exists:licencia_punto_infracciones,id'],
            'personas.*.observaciones' => ['nullable', 'string'],
        ];
    }

    private function assertCapturaHasContent(
        array $validated,
        Request $request,
        ?ConduceLegalidadCaptura $captura = null
    ): void
    {
        $narrativa = $this->nullableString($validated['narrativa'] ?? null);
        $vehiculos = $validated['vehiculos'] ?? [];
        $personas = $validated['personas'] ?? [];
        $hasFotos = $request->hasFile('fotos')
            || ($captura !== null && $captura->fotos()->exists());

        if ($narrativa === null && count($vehiculos) === 0 && count($personas) === 0 && !$hasFotos) {
            throw ValidationException::withMessages([
                'narrativa' => 'Captura una narrativa o agrega al menos un vehiculo/persona/foto.',
            ]);
        }
    }

    private function storeFotos(ConduceLegalidadCaptura $captura, Request $request, $user): void
    {
        if (!$request->hasFile('fotos')) {
            return;
        }

        $files = $request->file('fotos', []);
        if (!is_array($files)) {
            $files = [$files];
        }

        $orden = (int) $captura->fotos()->max('orden');

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $orden++;
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'captura_' . $captura->id . '_' . Str::uuid() . '.' . $extension;
            $fotoPath = $file->storeAs('conduce_legalidad', $filename, 'public');
            $fotoHash = hash_file('sha256', $file->getRealPath());
            $thumbnailPath = $this->crearThumbnailSeguro(
                $fotoPath,
                'conduce_legalidad_thumbnails/operativo_' . $captura->operativo_id,
                'captura_' . $captura->id . '_foto_' . $orden
            );

            $captura->fotos()->create([
                'foto_path' => $fotoPath,
                'foto_thumbnail_path' => $thumbnailPath,
                'foto_nombre_original' => $file->getClientOriginalName(),
                'foto_hash' => $fotoHash,
                'orden' => $orden,
                'created_by' => $user->id ?? null,
                'updated_by' => $user->id ?? null,
            ]);
        }
    }

    private function crearThumbnailSeguro(string $fotoPath, string $directorio, string $prefijo): ?string
    {
        try {
            return app(ImageThumbnailService::class)->createPublicThumbnail($fotoPath, $directorio, $prefijo);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function replaceVehiculos(ConduceLegalidadCaptura $captura, array $vehiculos): void
    {
        $captura->vehiculos()->delete();

        foreach ($vehiculos as $row) {
            $infraccion = $this->retencionInfraccion($row['licencia_punto_infraccion_id'] ?? null);
            $personaHabilitadaResguardo = $this->booleanRow($row, 'persona_habilitada_resguardo', 'responsable_habilitado_presente');
            $retencionCondicional = $infraccion
                && (bool) $infraccion->deposito_si_sin_persona_habilitada
                && !$personaHabilitadaResguardo;
            $retencionVehiculo = $infraccion
                ? ((bool) $infraccion->retencion_vehiculo || $retencionCondicional)
                : false;
            $motivoRetencion = $this->nullableString($row['motivo_retencion'] ?? null)
                ?: ($retencionVehiculo && $infraccion ? $this->motivoRetencionPorInfraccion($infraccion, $retencionCondicional) : null);
            $tieneServicioGrua = $this->tieneServicioGrua($row);

            $captura->vehiculos()->create([
                'marca' => $this->nullableString($row['marca'] ?? null),
                'modelo' => $this->nullableString($row['modelo'] ?? null),
                'tipo_general' => $this->nullableString($row['tipo_general'] ?? null),
                'tipo' => $this->nullableString($row['tipo'] ?? null),
                'linea' => $this->nullableString($row['linea'] ?? null),
                'color' => $this->nullableString($row['color'] ?? null),
                'placas' => $this->nullableString($row['placas'] ?? null),
                'estado_placas' => $this->nullableString($row['estado_placas'] ?? null),
                'serie' => $this->nullableString($row['serie'] ?? null),
                'capacidad_personas' => (int) ($row['capacidad_personas'] ?? 0),
                'tipo_servicio' => $this->nullableString($row['tipo_servicio'] ?? null),
                'tarjeta_circulacion_nombre' => $this->nullableString($row['tarjeta_circulacion_nombre'] ?? null),
                'grua_id' => $row['grua_id'] ?? null,
                'corralon_id' => $row['corralon_id'] ?? null,
                'grua' => $this->nullableString($row['grua'] ?? null),
                'corralon' => $this->nullableString($row['corralon'] ?? null),
                'servicio_unidad_id' => $tieneServicioGrua ? $captura->unidad_id : null,
                'servicio_delegacion_id' => $tieneServicioGrua ? $captura->delegacion_id : null,
                'servicio_created_by' => $tieneServicioGrua ? $captura->created_by : null,
                'aseguradora' => $this->nullableString($row['aseguradora'] ?? null),
                'monto_danos' => $row['monto_danos'] ?? null,
                'partes_danadas' => $this->nullableString($row['partes_danadas'] ?? null),
                'antecedente_vehiculo' => (bool) ($row['antecedente_vehiculo'] ?? false),
                'raw_tarjeta_qr' => $this->nullableString($row['raw_tarjeta_qr'] ?? null),
                'licencia_punto_infraccion_id' => $infraccion ? $infraccion->id : null,
                'infraccion_codigo' => $infraccion ? $infraccion->codigo : null,
                'fundamento_legal' => $infraccion ? $infraccion->fundamento_legal : null,
                'retencion_vehiculo' => $retencionVehiculo,
                'motivo_retencion' => $motivoRetencion,
                'persona_habilitada_resguardo' => $personaHabilitadaResguardo,
                'observaciones' => $this->nullableString($row['observaciones'] ?? null),
            ]);
        }
    }

    private function tieneServicioGrua(array $row): bool
    {
        return !empty($row['grua_id'])
            || !empty($row['corralon_id'])
            || $this->nullableString($row['grua'] ?? null) !== null
            || $this->nullableString($row['corralon'] ?? null) !== null;
    }

    private function booleanRow(array $row, string $field, ?string $alias = null): bool
    {
        $value = $row[$field] ?? ($alias !== null ? ($row[$alias] ?? false) : false);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function replacePersonas(ConduceLegalidadCaptura $captura, array $personas): void
    {
        $captura->personas()->delete();

        foreach ($personas as $row) {
            $infraccion = $this->personaInfraccion($row['licencia_punto_infraccion_id'] ?? null);

            $captura->personas()->create([
                'nombre' => $this->nullableString($row['nombre'] ?? null),
                'telefono' => $this->nullableString($row['telefono'] ?? null),
                'domicilio' => $this->nullableString($row['domicilio'] ?? null),
                'sexo' => $this->nullableString($row['sexo'] ?? null),
                'ocupacion' => $this->nullableString($row['ocupacion'] ?? null),
                'edad' => $row['edad'] ?? null,
                'tipo_licencia' => $this->nullableString($row['tipo_licencia'] ?? null),
                'estado_licencia' => $this->nullableString($row['estado_licencia'] ?? null),
                'numero_licencia' => $this->nullableString($row['numero_licencia'] ?? null),
                'vigencia_licencia' => $row['vigencia_licencia'] ?? null,
                'permanente' => (bool) ($row['permanente'] ?? false),
                'raw_licencia_qr' => $this->nullableString($row['raw_licencia_qr'] ?? null),
                'licencia_punto_infraccion_id' => $infraccion ? $infraccion->id : null,
                'infraccion_codigo' => $infraccion ? $infraccion->codigo : null,
                'fundamento_legal' => $infraccion ? $infraccion->fundamento_legal : null,
                'observaciones' => $this->nullableString($row['observaciones'] ?? null),
            ]);
        }
    }

    private function retencionInfraccion($id): ?LicenciaPuntoInfraccion
    {
        $id = (int) ($id ?? 0);
        if ($id <= 0) {
            return null;
        }

        $infraccion = LicenciaPuntoInfraccion::activas()->find($id);
        if (
            !$infraccion ||
            !$this->infraccionAplicaRetencionOperativo($infraccion) ||
            $this->esFundamentoExcluidoDelOperativo($infraccion)
        ) {
            throw ValidationException::withMessages([
                'licencia_punto_infraccion_id' => 'El fundamento seleccionado no aplica para este operativo o no amerita retiro de vehiculo.',
            ]);
        }

        return $infraccion;
    }

    private function personaInfraccion($id): ?LicenciaPuntoInfraccion
    {
        $id = (int) ($id ?? 0);
        if ($id <= 0) {
            return null;
        }

        $infraccion = LicenciaPuntoInfraccion::activas()->find($id);
        if (!$infraccion || !$this->infraccionAplicaSancionPersonaOperativo($infraccion)) {
            throw ValidationException::withMessages([
                'licencia_punto_infraccion_id' => 'El fundamento seleccionado no aplica como sancion de persona en este operativo.',
            ]);
        }

        return $infraccion;
    }

    private function infraccionAplicaRetencionOperativo(LicenciaPuntoInfraccion $infraccion): bool
    {
        return (bool) $infraccion->retencion_vehiculo
            || (bool) $infraccion->deposito_si_sin_persona_habilitada;
    }

    private function infraccionAplicaSancionPersonaOperativo(LicenciaPuntoInfraccion $infraccion): bool
    {
        return (bool) $infraccion->amonestacion
            || (bool) $infraccion->arresto_persona
            || (bool) $infraccion->suspension_licencia
            || (bool) $infraccion->cancelacion_licencia
            || (int) $infraccion->puntos > 0;
    }

    private function fundamentosCorralonPayload()
    {
        return LicenciaPuntoInfraccion::activas()
            ->where(function ($query) {
                $query->where('retencion_vehiculo', true)
                    ->orWhere('deposito_si_sin_persona_habilitada', true);
            })
            ->get()
            ->reject(fn (LicenciaPuntoInfraccion $infraccion) => $this->esFundamentoExcluidoDelOperativo($infraccion))
            ->sortBy(function (LicenciaPuntoInfraccion $infraccion) {
                return implode('|', [
                    $infraccion->articulo ? str_pad((string) $infraccion->articulo, 8, '0', STR_PAD_LEFT) : 'ZZZZZZZZ',
                    $infraccion->fraccion ?: 'ZZZZ',
                    $infraccion->inciso ?: 'ZZZZ',
                    $infraccion->nombre,
                ]);
            })
            ->values()
            ->map(fn (LicenciaPuntoInfraccion $infraccion) => $this->fundamentoInfraccionPayload($infraccion));
    }

    private function fundamentosPersonaPayload()
    {
        return LicenciaPuntoInfraccion::activas()
            ->where(function ($query) {
                $query->where('amonestacion', true)
                    ->orWhere('arresto_persona', true)
                    ->orWhere('suspension_licencia', true)
                    ->orWhere('cancelacion_licencia', true)
                    ->orWhere('puntos', '>', 0);
            })
            ->get()
            ->filter(fn (LicenciaPuntoInfraccion $infraccion) => $this->infraccionAplicaSancionPersonaOperativo($infraccion))
            ->sortBy(function (LicenciaPuntoInfraccion $infraccion) {
                return implode('|', [
                    $infraccion->arresto_persona ? '0' : '1',
                    $infraccion->articulo ? str_pad((string) $infraccion->articulo, 8, '0', STR_PAD_LEFT) : 'ZZZZZZZZ',
                    $infraccion->fraccion ?: 'ZZZZ',
                    $infraccion->inciso ?: 'ZZZZ',
                    $infraccion->nombre,
                ]);
            })
            ->values()
            ->map(fn (LicenciaPuntoInfraccion $infraccion) => $this->fundamentoInfraccionPayload($infraccion));
    }

    private function fundamentoInfraccionPayload(LicenciaPuntoInfraccion $infraccion): array
    {
        return [
            'id' => $infraccion->id,
            'codigo' => $infraccion->codigo,
            'nombre' => $infraccion->nombre,
            'articulo' => $infraccion->articulo,
            'fraccion' => $infraccion->fraccion,
            'inciso' => $infraccion->inciso,
            'ambito_vehiculo' => $infraccion->ambito_vehiculo,
            'ambito_vehiculo_texto' => $infraccion->ambito_vehiculo_texto,
            'referencia_legal_corta' => $infraccion->referencia_legal_corta,
            'puntos' => (int) $infraccion->puntos,
            'multa_uma_min' => $infraccion->multa_uma_min,
            'multa_uma_max' => $infraccion->multa_uma_max,
            'multa_uma_texto' => $infraccion->multa_uma_texto,
            'amonestacion' => (bool) $infraccion->amonestacion,
            'arresto_persona' => (bool) $infraccion->arresto_persona,
            'suspension_licencia' => (bool) $infraccion->suspension_licencia,
            'cancelacion_licencia' => (bool) $infraccion->cancelacion_licencia,
            'deposito_si_sin_persona_habilitada' => (bool) $infraccion->deposito_si_sin_persona_habilitada,
            'sancion_persona_texto' => $infraccion->sancion_persona_texto,
            'retencion_vehiculo' => (bool) $infraccion->retencion_vehiculo,
            'resumen_sanciones' => $infraccion->resumen_sanciones,
            'etiqueta_operativa' => $this->textoOperativoInfraccion($infraccion),
            'texto_operativo' => $this->textoOperativoInfraccion($infraccion),
            'descripcion' => $infraccion->descripcion,
            'fundamento_legal' => $infraccion->fundamento_legal,
            'narrativa_sugerida' => $this->narrativaSugeridaInfraccion($infraccion),
        ];
    }

    private function operativoPayload(ConduceLegalidadOperativo $operativo, $user, $capturas = null): array
    {
        $isManager = $this->canManage($user);
        $misCapturas = $operativo->mis_capturas_count;
        if ($misCapturas === null && $user) {
            $misCapturas = $operativo->capturas()->where('created_by', $user->id)->count();
        }
        $totalCapturas = $isManager
            ? (int) ($operativo->capturas_count ?? $operativo->capturas()->count())
            : (int) $misCapturas;

        $payload = [
            'id' => $operativo->id,
            'client_uuid' => $operativo->client_uuid,
            'nombre' => $operativo->nombre,
            'fecha' => optional($operativo->fecha)->toDateString(),
            'hora_inicio' => $operativo->hora_inicio,
            'hora_cierre' => $operativo->hora_cierre,
            'municipio' => $operativo->municipio,
            'lugar' => $operativo->lugar,
            'lat' => $operativo->lat === null ? null : (float) $operativo->lat,
            'lng' => $operativo->lng === null ? null : (float) $operativo->lng,
            'coordenadas_texto' => $operativo->coordenadas_texto,
            'objetivo' => $operativo->objetivo,
            'narrativa' => $operativo->narrativa,
            'observaciones' => $operativo->observaciones,
            'estado' => $operativo->estado,
            'can_edit' => $this->canManage($user),
            'can_delete' => $this->canDeleteOperativo($user),
            'created_by' => $operativo->created_by,
            'creador' => $this->userPayload($operativo->creador),
            'total_capturas' => $totalCapturas,
            'mis_capturas' => (int) $misCapturas,
            'created_at' => optional($operativo->created_at)->toISOString(),
            'updated_at' => optional($operativo->updated_at)->toISOString(),
        ];

        if ($capturas !== null) {
            $payload['capturas'] = $capturas
                ->map(fn (ConduceLegalidadCaptura $captura) => $this->capturaPayload($captura, $user))
                ->values();
        }

        return $payload;
    }

    private function capturaPayload(ConduceLegalidadCaptura $captura, $user): array
    {
        $captura->loadMissing(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas.infraccion', 'fotos']);

        return [
            'id' => $captura->id,
            'client_uuid' => $captura->client_uuid,
            'operativo_id' => $captura->operativo_id,
            'created_by' => $captura->created_by,
            'creador' => $this->userPayload($captura->creador),
            'unidad' => $this->refPayload($captura->unidad),
            'delegacion' => $this->refPayload($captura->delegacion),
            'fecha' => optional($captura->fecha)->toDateString(),
            'hora' => $captura->hora,
            'municipio' => $captura->municipio,
            'lugar' => $captura->lugar,
            'lat' => $captura->lat === null ? null : (float) $captura->lat,
            'lng' => $captura->lng === null ? null : (float) $captura->lng,
            'coordenadas_texto' => $captura->coordenadas_texto,
            'narrativa' => $captura->narrativa,
            'observaciones' => $captura->observaciones,
            'rnd_data' => $captura->rnd_data,
            'can_edit' => $this->canEditCaptura($user, $captura),
            'can_delete' => $this->canDeleteCaptura($user),
            'vehiculos' => $captura->vehiculos->map(fn (ConduceLegalidadVehiculo $vehiculo) => $this->vehiculoPayload($vehiculo))->values(),
            'personas' => $captura->personas->map(fn (ConduceLegalidadPersona $persona) => $this->personaPayload($persona))->values(),
            'fotos' => $captura->fotos->map(fn (ConduceLegalidadFoto $foto) => $this->fotoPayload($foto))->values(),
            'created_at' => optional($captura->created_at)->toISOString(),
            'updated_at' => optional($captura->updated_at)->toISOString(),
        ];
    }

    private function fotoPayload(ConduceLegalidadFoto $foto): array
    {
        return [
            'id' => $foto->id,
            'foto_path' => $foto->foto_path,
            'foto_thumbnail_path' => $foto->foto_thumbnail_path,
            'foto_nombre_original' => $foto->foto_nombre_original,
            'foto_url' => $this->storageUrl($foto->foto_path),
            'foto_thumbnail_url' => $this->storageUrl($foto->foto_thumbnail_path ?: $foto->foto_path),
            'foto_preview_url' => $this->storageUrl($foto->foto_thumbnail_path ?: $foto->foto_path),
            'orden' => (int) $foto->orden,
        ];
    }

    private function vehiculoPayload(ConduceLegalidadVehiculo $vehiculo): array
    {
        return [
            'id' => $vehiculo->id,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
            'tipo_general' => $vehiculo->tipo_general,
            'tipo' => $vehiculo->tipo,
            'linea' => $vehiculo->linea,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas,
            'estado_placas' => $vehiculo->estado_placas,
            'serie' => $vehiculo->serie,
            'capacidad_personas' => (int) $vehiculo->capacidad_personas,
            'tipo_servicio' => $vehiculo->tipo_servicio,
            'tarjeta_circulacion_nombre' => $vehiculo->tarjeta_circulacion_nombre,
            'grua_id' => $vehiculo->grua_id,
            'corralon_id' => $vehiculo->corralon_id,
            'grua' => $vehiculo->grua,
            'corralon' => $vehiculo->corralon,
            'servicio_unidad_id' => $vehiculo->servicio_unidad_id,
            'servicio_delegacion_id' => $vehiculo->servicio_delegacion_id,
            'servicio_created_by' => $vehiculo->servicio_created_by,
            'aseguradora' => $vehiculo->aseguradora,
            'monto_danos' => $vehiculo->monto_danos === null ? null : (float) $vehiculo->monto_danos,
            'partes_danadas' => $vehiculo->partes_danadas,
            'antecedente_vehiculo' => (bool) $vehiculo->antecedente_vehiculo,
            'raw_tarjeta_qr' => $vehiculo->raw_tarjeta_qr,
            'licencia_punto_infraccion_id' => $vehiculo->licencia_punto_infraccion_id,
            'infraccion_codigo' => $vehiculo->infraccion_codigo,
            'fundamento_legal' => $vehiculo->fundamento_legal,
            'retencion_vehiculo' => (bool) $vehiculo->retencion_vehiculo,
            'motivo_retencion' => $vehiculo->motivo_retencion,
            'persona_habilitada_resguardo' => (bool) $vehiculo->persona_habilitada_resguardo,
            'observaciones' => $vehiculo->observaciones,
            'infraccion' => $vehiculo->infraccion ? [
                'id' => $vehiculo->infraccion->id,
                'codigo' => $vehiculo->infraccion->codigo,
                'nombre' => $vehiculo->infraccion->nombre,
                'articulo' => $vehiculo->infraccion->articulo,
                'fraccion' => $vehiculo->infraccion->fraccion,
                'inciso' => $vehiculo->infraccion->inciso,
                'referencia_legal_corta' => $vehiculo->infraccion->referencia_legal_corta,
                'ambito_vehiculo' => $vehiculo->infraccion->ambito_vehiculo,
                'ambito_vehiculo_texto' => $vehiculo->infraccion->ambito_vehiculo_texto,
                'puntos' => (int) $vehiculo->infraccion->puntos,
                'multa_uma_min' => $vehiculo->infraccion->multa_uma_min,
                'multa_uma_max' => $vehiculo->infraccion->multa_uma_max,
                'multa_uma_texto' => $vehiculo->infraccion->multa_uma_texto,
                'etiqueta_operativa' => $this->textoOperativoInfraccion($vehiculo->infraccion),
                'texto_operativo' => $this->textoOperativoInfraccion($vehiculo->infraccion),
                'descripcion' => $vehiculo->infraccion->descripcion,
                'fundamento_legal' => $vehiculo->infraccion->fundamento_legal,
                'amonestacion' => (bool) $vehiculo->infraccion->amonestacion,
                'arresto_persona' => (bool) $vehiculo->infraccion->arresto_persona,
                'suspension_licencia' => (bool) $vehiculo->infraccion->suspension_licencia,
                'cancelacion_licencia' => (bool) $vehiculo->infraccion->cancelacion_licencia,
                'deposito_si_sin_persona_habilitada' => (bool) $vehiculo->infraccion->deposito_si_sin_persona_habilitada,
                'sancion_persona_texto' => $vehiculo->infraccion->sancion_persona_texto,
                'retencion_vehiculo' => (bool) $vehiculo->infraccion->retencion_vehiculo,
                'resumen_sanciones' => $vehiculo->infraccion->resumen_sanciones,
                'narrativa_sugerida' => $this->narrativaSugeridaInfraccion($vehiculo->infraccion),
            ] : null,
        ];
    }

    private function personaPayload(ConduceLegalidadPersona $persona): array
    {
        return [
            'id' => $persona->id,
            'nombre' => $persona->nombre,
            'telefono' => $persona->telefono,
            'domicilio' => $persona->domicilio,
            'sexo' => $persona->sexo,
            'ocupacion' => $persona->ocupacion,
            'edad' => $persona->edad,
            'tipo_licencia' => $persona->tipo_licencia,
            'estado_licencia' => $persona->estado_licencia,
            'numero_licencia' => $persona->numero_licencia,
            'vigencia_licencia' => optional($persona->vigencia_licencia)->toDateString(),
            'permanente' => (bool) $persona->permanente,
            'raw_licencia_qr' => $persona->raw_licencia_qr,
            'licencia_punto_infraccion_id' => $persona->licencia_punto_infraccion_id,
            'infraccion_codigo' => $persona->infraccion_codigo,
            'fundamento_legal' => $persona->fundamento_legal,
            'infraccion' => $persona->infraccion
                ? $this->fundamentoInfraccionPayload($persona->infraccion)
                : null,
            'observaciones' => $persona->observaciones,
        ];
    }

    private function userPayload($user): ?array
    {
        if (!$user) {
            return null;
        }

        $user->loadMissing(['personal.unidad', 'unidad']);
        $personal = $user->personal;
        $unidad = ($personal && $personal->unidad) ? $personal->unidad : $user->unidad;
        $unidadNombre = $unidad ? $unidad->nombre : null;
        $numeroPlaca = $this->nullableString($personal ? $personal->numero_placa : null);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'numero_placa' => $numeroPlaca,
            'placa' => $numeroPlaca,
            'adscripcion' => $unidadNombre,
            'unidad_nombre' => $unidadNombre,
            'unidad_id' => $user->unidad_id,
            'delegacion_id' => $user->delegacion_id,
        ];
    }

    private function refPayload($model): ?array
    {
        if (!$model) {
            return null;
        }

        return [
            'id' => $model->id,
            'nombre' => $model->nombre ?? $model->name ?? null,
        ];
    }

    private function storageUrl(?string $path): ?string
    {
        $clean = $this->nullableString($path);
        return $clean ? asset('storage/' . $clean) : null;
    }

    private function textoOperativoInfraccion(LicenciaPuntoInfraccion $infraccion): string
    {
        return $this->nullableString($infraccion->nombre)
            ?: $this->nullableString($infraccion->descripcion)
            ?: $this->nullableString($infraccion->codigo)
            ?: 'Fundamento #' . $infraccion->id;
    }

    private function narrativaSugeridaInfraccion(LicenciaPuntoInfraccion $infraccion): ?string
    {
        return strtoupper(trim((string) $infraccion->codigo)) === self::FUNDAMENTO_SIN_LICENCIA_CODIGO
            ? self::NARRATIVA_SIN_LICENCIA
            : null;
    }

    private function esFundamentoExcluidoDelOperativo(LicenciaPuntoInfraccion $infraccion): bool
    {
        $codigo = strtoupper(trim((string) $infraccion->codigo));
        if (in_array($codigo, self::FUNDAMENTOS_EXCLUIDOS_OPERATIVO, true)) {
            return true;
        }

        $texto = $this->normalizarTextoFiltro(implode(' ', [
            $infraccion->codigo,
            $infraccion->nombre,
            $infraccion->descripcion,
            $infraccion->fundamento_legal,
        ]));

        foreach (self::TEXTOS_EXCLUIDOS_OPERATIVO as $excluido) {
            if (str_contains($texto, $excluido)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarTextoFiltro(string $texto): string
    {
        $texto = strtoupper(strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]));
        $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/', ' ', trim($texto)) ?? $texto;

        return $texto;
    }

    private function motivoRetencionPorInfraccion(LicenciaPuntoInfraccion $infraccion, bool $retencionCondicional = false): string
    {
        $referencia = $this->nullableString($infraccion->referencia_legal_corta);
        $motivo = $this->textoOperativoInfraccion($infraccion);

        if ($retencionCondicional) {
            $motivo = trim($motivo . ' - sin persona habilitada para resguardar el vehiculo');
        }

        if ($referencia && $motivo) {
            return $referencia . ' - ' . $motivo;
        }

        return $referencia ?: $motivo;
    }

    private function abilitiesPayload($user): array
    {
        return [
            'can_feed' => (bool) $user,
            'can_create_operativo' => $this->canCreateOperativo($user),
            'can_manage_operativos' => $this->canManage($user),
            'can_view_all_capturas' => $this->canManage($user),
            'can_use_rnd' => $this->canUseRnd($user),
            'scope' => $this->canManage($user) ? 'all' : 'own',
        ];
    }

    private function scopeCapturas($query, $user): void
    {
        if ($this->canManage($user)) {
            return;
        }

        $query->where('created_by', $user ? $user->id : null);
    }

    private function canUseRnd($user): bool
    {
        if (!$user) {
            return false;
        }

        return (int) ($user->unidad_id ?? 0) !== self::UNIDAD_DELEGACIONES
            && optional($user->unidad)->slug !== 'delegaciones';
    }

    private function normalizeRndData($raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $out = [];
        foreach ($this->rndDataKeys() as $key) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }

            $value = $this->nullableString($raw[$key]);
            if ($value !== null) {
                $out[$key] = Str::limit($value, 2000, '');
            }
        }

        return $out === [] ? null : $out;
    }

    private function rndDataKeys(): array
    {
        return [
            'elementos_nombre',
            'elementos_cargo',
            'elementos_adscripcion',
            'falta_administrativa',
            'detencion_fecha_hora',
            'detencion_tiempo_forma',
            'detencion_motivo',
            'lugar_municipio',
            'lugar_localidad',
            'lugar_calle_numero',
            'lugar_referencia',
            'detenido_nombre',
            'detenido_alias',
            'detenido_nacionalidad',
            'detenido_edad',
            'detenido_lesiones_visibles',
            'detenido_delincuencia_organizada',
            'detenido_complexion',
            'traslado_ruta',
            'traslado_unidad',
            'solicitante_nombre',
            'solicitante_telefono',
        ];
    }

    private function rndMessage(array $data, string $usuario, string $telefono, string $referencia): string
    {
        $v = fn (string $key) => $this->rndValue($data[$key] ?? null);

        return implode("\n", [
            'DATOS PARA RND DE FALTAS ADMINISTRATIVAS',
            '',
            'REFERENCIA',
            'Usuario: ' . $usuario,
            'Telefono: ' . $telefono,
            'Registro: ' . $referencia,
            '',
            'ELEMENTOS',
            'Nombre: ' . $v('elementos_nombre'),
            'Cargo: ' . $v('elementos_cargo'),
            'Adscripcion: ' . $v('elementos_adscripcion'),
            '',
            'DETENCION',
            'Falta: ' . $v('falta_administrativa'),
            'Fecha/hora: ' . $v('detencion_fecha_hora'),
            'Tiempo y forma: ' . $v('detencion_tiempo_forma'),
            'Motivo: ' . $v('detencion_motivo'),
            '',
            'LUGAR',
            'Municipio: ' . $v('lugar_municipio'),
            'Localidad: ' . $v('lugar_localidad'),
            'Calle/numero: ' . $v('lugar_calle_numero'),
            'Referencia: ' . $v('lugar_referencia'),
            '',
            'DETENIDO',
            'Nombre: ' . $v('detenido_nombre'),
            'Alias: ' . $v('detenido_alias'),
            'Nacionalidad: ' . $v('detenido_nacionalidad'),
            'Edad: ' . $v('detenido_edad'),
            'Lesiones visibles: ' . $v('detenido_lesiones_visibles'),
            'Delincuencia organizada: ' . $v('detenido_delincuencia_organizada'),
            'Complexion: ' . $v('detenido_complexion'),
            '',
            'TRASLADO',
            'Ruta: ' . $v('traslado_ruta'),
            'Unidad: ' . $v('traslado_unidad'),
        ]);
    }

    private function rndValue($value): string
    {
        return $this->nullableString($value) ?: 'SIN DATO';
    }
    private function canCreateOperativo($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->canManage($user)
            || ($this->isVialidadesUser($user) && $user->can('crear conduce legalidad'));
    }

    private function canManage($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ((int) ($user->unidad_id ?? 0) === self::UNIDAD_SEGURIDAD_VIAL) {
            return true;
        }

        if (($this->isRtVialidades($user) || $this->isSubdirectorVialidades($user))) {
            return true;
        }

        return $this->isVialidadesUser($user) && $user->can('editar conduce legalidad');
    }

    private function canEditCaptura($user, ConduceLegalidadCaptura $captura): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canManage($user)) {
            return true;
        }

        return (int) $captura->created_by === (int) $user->id;
    }

    private function canDeleteOperativo($user): bool
    {
        return $this->canDeleteAdministrativo($user);
    }

    private function canDeleteCaptura($user): bool
    {
        return $this->canDeleteAdministrativo($user);
    }

    private function canDeleteAdministrativo($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['Superadmin', 'Administrador']);
    }

    private function isRtVialidades($user): bool
    {
        return $this->isVialidadesUser($user)
            && $user->hasAnyRole(['Responsable de Turno', 'RT']);
    }

    private function isSubdirectorVialidades($user): bool
    {
        return $this->isVialidadesUser($user)
            && $user->hasRole('Subdirector');
    }

    private function isVialidadesUser($user): bool
    {
        return (int) ($user->unidad_id ?? 0) === self::UNIDAD_VIALIDADES_URBANAS
            || optional($user->unidad)->slug === 'vialidades-urbanas';
    }

    private function nullableString($value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }
}
