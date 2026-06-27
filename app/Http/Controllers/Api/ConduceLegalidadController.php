<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Grua;
use App\Models\LicenciaPuntoInfraccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConduceLegalidadController extends Controller
{
    private const UNIDAD_SINIESTROS = 1;
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
            ->with(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas'])
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
                ->with(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas'])
                ->first();

            if ($existing) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Captura guardada correctamente.',
                    'data' => $this->capturaPayload($existing, $user),
                ]);
            }
        }

        $this->assertCapturaHasContent($validated);

        $captura = DB::transaction(function () use ($operativo, $user, $validated, $clientUuid) {
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
            ]);

            $this->replaceVehiculos($captura, $validated['vehiculos'] ?? []);
            $this->replacePersonas($captura, $validated['personas'] ?? []);

            return $captura;
        });

        $captura->load(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas']);

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
        $this->assertCapturaHasContent($validated);

        DB::transaction(function () use ($captura, $validated) {
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
            ]);
            $captura->save();

            $this->replaceVehiculos($captura, $validated['vehiculos'] ?? []);
            $this->replacePersonas($captura, $validated['personas'] ?? []);
        });

        $captura->load(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas']);

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
        abort_unless($this->canEditCaptura($user, $captura), 403);

        $captura->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Captura eliminada correctamente.',
        ]);
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
            'personas.*.observaciones' => ['nullable', 'string'],
        ];
    }

    private function assertCapturaHasContent(array $validated): void
    {
        $narrativa = $this->nullableString($validated['narrativa'] ?? null);
        $vehiculos = $validated['vehiculos'] ?? [];
        $personas = $validated['personas'] ?? [];

        if ($narrativa === null && count($vehiculos) === 0 && count($personas) === 0) {
            throw ValidationException::withMessages([
                'narrativa' => 'Captura una narrativa o agrega al menos un vehiculo/persona.',
            ]);
        }
    }

    private function replaceVehiculos(ConduceLegalidadCaptura $captura, array $vehiculos): void
    {
        $captura->vehiculos()->delete();

        foreach ($vehiculos as $row) {
            $infraccion = $this->retencionInfraccion($row['licencia_punto_infraccion_id'] ?? null);
            $motivoRetencion = $this->nullableString($row['motivo_retencion'] ?? null)
                ?: ($infraccion ? $this->motivoRetencionPorInfraccion($infraccion) : null);
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
                'retencion_vehiculo' => $infraccion ? (bool) $infraccion->retencion_vehiculo : false,
                'motivo_retencion' => $motivoRetencion,
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

    private function replacePersonas(ConduceLegalidadCaptura $captura, array $personas): void
    {
        $captura->personas()->delete();

        foreach ($personas as $row) {
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
            !$infraccion->retencion_vehiculo ||
            $this->esFundamentoExcluidoDelOperativo($infraccion)
        ) {
            throw ValidationException::withMessages([
                'licencia_punto_infraccion_id' => 'El fundamento seleccionado no aplica para este operativo o no amerita retiro de vehiculo.',
            ]);
        }

        return $infraccion;
    }

    private function fundamentosCorralonPayload()
    {
        return LicenciaPuntoInfraccion::activas()
            ->where('retencion_vehiculo', true)
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
            ->map(fn (LicenciaPuntoInfraccion $infraccion) => [
                'id' => $infraccion->id,
                'codigo' => $infraccion->codigo,
                'nombre' => $infraccion->nombre,
                'articulo' => $infraccion->articulo,
                'fraccion' => $infraccion->fraccion,
                'inciso' => $infraccion->inciso,
                'referencia_legal_corta' => $infraccion->referencia_legal_corta,
                'puntos' => (int) $infraccion->puntos,
                'multa_uma_min' => $infraccion->multa_uma_min,
                'multa_uma_max' => $infraccion->multa_uma_max,
                'multa_uma_texto' => $infraccion->multa_uma_texto,
                'retencion_vehiculo' => (bool) $infraccion->retencion_vehiculo,
                'resumen_sanciones' => $infraccion->resumen_sanciones,
                'etiqueta_operativa' => $this->textoOperativoInfraccion($infraccion),
                'texto_operativo' => $this->textoOperativoInfraccion($infraccion),
                'descripcion' => $infraccion->descripcion,
                'fundamento_legal' => $infraccion->fundamento_legal,
                'narrativa_sugerida' => $this->narrativaSugeridaInfraccion($infraccion),
            ]);
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
        $captura->loadMissing(['creador', 'unidad', 'delegacion', 'vehiculos.infraccion', 'personas']);

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
            'can_edit' => $this->canEditCaptura($user, $captura),
            'vehiculos' => $captura->vehiculos->map(fn (ConduceLegalidadVehiculo $vehiculo) => $this->vehiculoPayload($vehiculo))->values(),
            'personas' => $captura->personas->map(fn (ConduceLegalidadPersona $persona) => $this->personaPayload($persona))->values(),
            'created_at' => optional($captura->created_at)->toISOString(),
            'updated_at' => optional($captura->updated_at)->toISOString(),
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
            'observaciones' => $vehiculo->observaciones,
            'infraccion' => $vehiculo->infraccion ? [
                'id' => $vehiculo->infraccion->id,
                'codigo' => $vehiculo->infraccion->codigo,
                'nombre' => $vehiculo->infraccion->nombre,
                'referencia_legal_corta' => $vehiculo->infraccion->referencia_legal_corta,
                'etiqueta_operativa' => $this->textoOperativoInfraccion($vehiculo->infraccion),
                'texto_operativo' => $this->textoOperativoInfraccion($vehiculo->infraccion),
                'fundamento_legal' => $vehiculo->infraccion->fundamento_legal,
                'retencion_vehiculo' => (bool) $vehiculo->infraccion->retencion_vehiculo,
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
            'observaciones' => $persona->observaciones,
        ];
    }

    private function userPayload($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
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

    private function motivoRetencionPorInfraccion(LicenciaPuntoInfraccion $infraccion): string
    {
        $referencia = $this->nullableString($infraccion->referencia_legal_corta);
        $motivo = $this->textoOperativoInfraccion($infraccion);

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
