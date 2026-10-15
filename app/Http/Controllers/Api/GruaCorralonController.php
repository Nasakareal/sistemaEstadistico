<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Models\ConduceLegalidadFoto;
use App\Models\GruaUsuario;
use App\Models\LiberacionCorralon;
use App\Models\Servicio;
use App\Models\Vehiculo;
use App\Services\Fotos\ActividadFotoBlobStorage;
use App\Services\Fotos\HechoFotoStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GruaCorralonController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->gruaUser($request);
        $estado = mb_strtolower(trim((string) $request->input('estado', 'pendientes')));
        $busqueda = trim((string) $request->input('q', ''));

        if ($estado === 'entregados') {
            $query = LiberacionCorralon::query()
                ->where('grua_id', $user->grua_id)
                ->where('estado', 'ENTREGADO')
                ->with($this->vehiculoRelations('vehiculo.'))
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->whereHas('vehiculo', function ($vehiculo) use ($busqueda) {
                        $this->buscarVehiculo($vehiculo, $busqueda);
                    });
                })
                ->orderByDesc('fecha_entrega')
                ->orderByDesc('id');

            $page = $query->paginate(30);
            $items = collect($page->items())
                ->map(fn (LiberacionCorralon $liberacion) => $this->serializeVehiculo(
                    $liberacion->vehiculo,
                    $liberacion
                ))
                ->values();
        } else {
            $query = $this->vehiculosPendientesQuery((int) $user->grua_id)
                ->with($this->vehiculoRelations())
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->where(function ($vehiculo) use ($busqueda) {
                        $this->buscarVehiculo($vehiculo, $busqueda);
                    });
                })
                ->orderByDesc('ultimo_servicio_at')
                ->orderByDesc('fecha_inventario_grua')
                ->orderByDesc('updated_at');

            $page = $query->paginate(30);
            $items = collect($page->items())
                ->map(fn (Vehiculo $vehiculo) => $this->serializeVehiculo($vehiculo))
                ->values();
        }

        return response()->json([
            'items' => $items,
            'summary' => $this->resumen((int) $user->grua_id),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'grua' => [
                'id' => $user->grua_id,
                'nombre' => optional($user->grua)->nombre,
            ],
        ]);
    }

    public function exportar(Request $request, string $formato)
    {
        $user = $this->gruaUser($request);
        $estado = mb_strtolower(trim((string) $request->input('estado', 'pendientes')));
        $busqueda = trim((string) $request->input('q', ''));
        abort_unless(in_array($formato, ['csv', 'pdf'], true), 404);

        $items = $this->reporteItems((int) $user->grua_id, $estado, $busqueda);
        $fecha = now('America/Mexico_City')->format('Y-m-d_H-i');
        $nombre = 'servicios_corralon_' . $estado . '_' . $fecha;

        if ($formato === 'pdf') {
            return Pdf::loadView('pdf.grua_corralon_reporte', [
                'items' => $items,
                'estado' => $estado,
                'grua' => $user->grua,
                'generado' => now('America/Mexico_City'),
                'summary' => $this->resumen((int) $user->grua_id),
            ])->setPaper('letter', 'landscape')->download($nombre . '.pdf');
        }

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Estado',
                'Origen',
                'Placas',
                'NIV',
                'Marca',
                'Línea',
                'Modelo',
                'Color',
                'Inventario',
                'Corralón',
                'Fecha servicio',
                'Realizó maniobras',
                'Descripción de maniobras',
                'Observaciones de la grúa',
                'Fecha entrega',
                'Persona que recibió',
                'Folio SIERSE',
                'Condición',
                'Observaciones',
            ]);

            foreach ($items as $item) {
                fputcsv($out, array_map([$this, 'csvSeguro'], [
                    $item['estado'],
                    data_get($item, 'origen.etiqueta'),
                    $item['placas'],
                    $item['serie'],
                    $item['marca'],
                    $item['linea'],
                    $item['modelo'],
                    $item['color'],
                    $item['numero_inventario_grua'],
                    $item['corralon'],
                    $item['fecha_servicio'],
                    data_get($item, 'servicio.realizo_maniobras') === null
                        ? 'SIN CAPTURAR'
                        : (data_get($item, 'servicio.realizo_maniobras') ? 'SI' : 'NO'),
                    data_get($item, 'servicio.maniobras_descripcion'),
                    data_get($item, 'servicio.observaciones_grua'),
                    data_get($item, 'liberacion.fecha_entrega'),
                    data_get($item, 'liberacion.persona_recibe'),
                    data_get($item, 'liberacion.sierse_folio'),
                    data_get($item, 'liberacion.condicion_entrega'),
                    data_get($item, 'liberacion.observaciones'),
                ]));
            }

            fclose($out);
        }, $nombre . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);

        $vehiculo->load($this->vehiculoRelations());

        return response()->json($this->serializeVehiculo(
            $vehiculo,
            $vehiculo->liberacionCorralon
        ));
    }

    public function entregar(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);

        abort_if(
            $vehiculo->liberacionCorralon()
                ->where('estado', 'ENTREGADO')
                ->exists(),
            422,
            'Este vehículo ya fue marcado como entregado.'
        );

        $validated = $request->validate([
            'persona_recibe' => ['required', 'string', 'max:255'],
            'identificacion_recibe' => ['required', 'string', 'max:255'],
            'telefono_recibe' => ['nullable', 'string', 'max:30'],
            'sierse_folio' => ['required', 'string', 'max:120'],
            'condicion_entrega' => ['required', 'in:CONFORME_INVENTARIO,CON_OBSERVACIONES'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'foto_entrega' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'foto_identificacion' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'documento_liberacion' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'memoria_descriptiva' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
            'comprobante_pago' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'firma_recibe' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'conformidad_memoria' => ['accepted'],
            'confirmar_entrega' => ['accepted'],
        ]);

        unset($validated['confirmar_entrega']);
        $validated['conformidad_memoria'] = true;
        $validated['telefono_recibe'] = $this->normalizarTelefonoMx(
            $validated['telefono_recibe'] ?? null
        );

        foreach ([
            'foto_entrega',
            'foto_identificacion',
            'documento_liberacion',
            'memoria_descriptiva',
            'comprobante_pago',
            'firma_recibe',
        ] as $campo) {
            if ($request->hasFile($campo)) {
                $validated[$campo] = $request->file($campo)
                    ->store('liberaciones_corralon', 'local');
            }
        }

        $liberacion = DB::transaction(function () use ($request, $vehiculo, $user, $validated) {
            $liberacion = LiberacionCorralon::updateOrCreate(
                ['vehiculo_id' => $vehiculo->id],
                array_merge($validated, [
                    'grua_id' => $user->grua_id,
                    'grua_usuario_id' => $user->id,
                    'estado' => 'ENTREGADO',
                    'fecha_entrega' => now(),
                    'fecha_conformidad' => now(),
                    'ip_entrega' => $request->ip(),
                    'user_agent_entrega' => mb_substr((string) $request->userAgent(), 0, 500),
                ])
            );

            $vehiculo->update(['corralon' => null]);

            return $liberacion;
        });

        $vehiculo->refresh()->load($this->vehiculoRelations());

        return response()->json([
            'message' => 'Vehículo marcado como entregado.',
            'item' => $this->serializeVehiculo($vehiculo, $liberacion),
        ]);
    }

    public function evidencia(Request $request, LiberacionCorralon $liberacionCorralon, string $campo)
    {
        $user = $this->gruaUser($request);
        abort_unless((int) $liberacionCorralon->grua_id === (int) $user->grua_id, 403);

        abort_unless(in_array($campo, [
            'foto_identificacion',
            'foto_entrega',
            'documento_liberacion',
            'memoria_descriptiva',
            'comprobante_pago',
            'firma_recibe',
        ], true), 404);

        $ruta = $liberacionCorralon->{$campo};
        abort_unless(is_string($ruta) && $ruta !== '', 404);

        $disco = Storage::disk('local')->exists($ruta)
            ? 'local'
            : (Storage::disk('public')->exists($ruta) ? 'public' : null);
        abort_unless($disco, 404);

        return Storage::disk($disco)->response($ruta, basename($ruta));
    }

    public function fotoInventario(
        Request $request,
        Vehiculo $vehiculo,
        HechoFotoStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        abort_unless($vehiculo->foto_inventario_grua, 404);

        return $storage->response(
            $vehiculo->foto_inventario_grua,
            'inventario_vehiculo_' . $vehiculo->id . '.'
            . (pathinfo($vehiculo->foto_inventario_grua, PATHINFO_EXTENSION) ?: 'jpg')
        );
    }

    public function fotoVehiculo(
        Request $request,
        Vehiculo $vehiculo,
        HechoFotoStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        abort_unless($vehiculo->getRawOriginal('fotos'), 404);

        return $storage->response(
            $vehiculo->getRawOriginal('fotos'),
            'vehiculo_' . $vehiculo->id . '.'
            . (pathinfo($vehiculo->getRawOriginal('fotos'), PATHINFO_EXTENSION) ?: 'jpg')
        );
    }

    public function fotoServicio(
        Request $request,
        Vehiculo $vehiculo,
        HechoFotoStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        $servicio = $this->servicioActual($vehiculo);
        abort_unless($servicio && $servicio->foto_vehiculo, 404);

        return $storage->response(
            $servicio->foto_vehiculo,
            'servicio_' . $servicio->id . '.'
            . (pathinfo($servicio->foto_vehiculo, PATHINFO_EXTENSION) ?: 'jpg')
        );
    }

    public function fotoActividad(
        Request $request,
        Vehiculo $vehiculo,
        ActividadFoto $foto,
        ActividadFotoBlobStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        abort_unless(
            $vehiculo->actividades()->whereKey($foto->actividad_id)->exists(),
            404
        );

        return $storage->response(
            $foto->foto_blob_path ?: $foto->foto_thumbnail_blob_path,
            $foto->foto_path ?: $foto->foto_thumbnail_path,
            $foto->foto_nombre_original ?: 'actividad_' . $foto->id . '.jpg'
        );
    }

    public function fotoActividadPrincipal(
        Request $request,
        Vehiculo $vehiculo,
        Actividad $actividad,
        ActividadFotoBlobStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        abort_unless($vehiculo->actividades()->whereKey($actividad->id)->exists(), 404);

        return $storage->response(
            $actividad->foto_blob_path ?: $actividad->foto_thumbnail_blob_path,
            $actividad->foto_path ?: $actividad->foto_thumbnail_path,
            $actividad->foto_nombre_original ?: 'actividad_' . $actividad->id . '.jpg'
        );
    }

    public function fotoConduce(
        Request $request,
        Vehiculo $vehiculo,
        ConduceLegalidadFoto $foto,
        HechoFotoStorage $storage
    ) {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        abort_unless(
            $vehiculo->actividades()
                ->whereHas('conduceLegalidadCaptura.fotos', function ($query) use ($foto) {
                    $query->whereKey($foto->id);
                })
                ->exists(),
            404
        );

        return $storage->response(
            $foto->foto_path,
            $foto->foto_nombre_original ?: 'conduce_legalidad_' . $foto->id . '.jpg'
        );
    }

    public function actualizarServicio(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        $servicio = $this->servicioActual($vehiculo);
        abort_unless($servicio, 404, 'No se encontró el servicio de grúa.');

        $validated = $request->validate([
            'realizo_maniobras' => ['required', 'boolean'],
            'maniobras_descripcion' => ['nullable', 'string', 'max:2000'],
            'observaciones_grua' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['realizo_maniobras']
            && trim((string) ($validated['maniobras_descripcion'] ?? '')) === '') {
            return response()->json([
                'message' => 'Describe las maniobras realizadas.',
                'errors' => [
                    'maniobras_descripcion' => ['Describe las maniobras realizadas.'],
                ],
            ], 422);
        }

        $servicio->update([
            'realizo_maniobras' => (bool) $validated['realizo_maniobras'],
            'maniobras_descripcion' => trim((string) ($validated['maniobras_descripcion'] ?? '')) ?: null,
            'observaciones_grua' => trim((string) ($validated['observaciones_grua'] ?? '')) ?: null,
            'actualizado_por_grua_usuario_id' => $user->id,
            'actualizado_por_grua_at' => now(),
        ]);

        $vehiculo->load($this->vehiculoRelations());

        return response()->json([
            'message' => 'Datos del servicio actualizados.',
            'item' => $this->serializeVehiculo($vehiculo, $vehiculo->liberacionCorralon),
        ]);
    }

    public function memoriaDescriptiva(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);
        $vehiculo->load($this->vehiculoRelations());
        $item = $this->serializeVehiculo($vehiculo, $vehiculo->liberacionCorralon);

        return Pdf::loadView('pdf.grua_memoria_descriptiva', [
            'item' => $item,
            'grua' => $user->grua,
            'generado' => now('America/Mexico_City'),
        ])->setPaper('letter')->download(
            'memoria_servicio_' . $vehiculo->id . '.pdf'
        );
    }

    private function gruaUser(Request $request): GruaUsuario
    {
        $user = $request->user();
        abort_unless($user instanceof GruaUsuario && $user->activo, 403);
        $user->loadMissing('grua');

        return $user;
    }

    private function vehiculosPendientesQuery(int $gruaId)
    {
        return Vehiculo::query()
            ->select('vehiculos.*')
            ->selectSub(function ($query) {
                $query->from('servicios')
                    ->select('created_at')
                    ->whereColumn('servicios.vehiculo_id', 'vehiculos.id')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'ultimo_servicio_at')
            ->whereRaw(
                'COALESCE((SELECT servicios_actuales.grua_id FROM servicios AS servicios_actuales '
                . 'WHERE servicios_actuales.vehiculo_id = vehiculos.id '
                . 'ORDER BY servicios_actuales.created_at DESC, servicios_actuales.id DESC LIMIT 1), '
                . 'vehiculos.grua_id) = ?',
                [$gruaId]
            )
            ->whereNotNull('vehiculos.corralon')
            ->whereRaw("TRIM(vehiculos.corralon) <> ''")
            ->whereRaw(
                "UPPER(TRIM(vehiculos.corralon)) NOT IN ("
                . implode(',', array_fill(0, count(Vehiculo::corralonValoresInvalidos()), '?'))
                . ')',
                Vehiculo::corralonValoresInvalidos()
            )
            ->whereDoesntHave('liberacionCorralon', function ($liberacion) {
                $liberacion->where('estado', 'ENTREGADO');
            });
    }

    private function autorizarVehiculo(Vehiculo $vehiculo, GruaUsuario $user): void
    {
        $gruaServicioActual = $vehiculo->servicios()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('grua_id');
        $gruaAsignada = $gruaServicioActual ?: $vehiculo->grua_id;

        abort_unless((int) $gruaAsignada === (int) $user->grua_id, 403);
    }

    private function buscarVehiculo($query, string $busqueda): void
    {
        $query->where('placas', 'like', '%' . $busqueda . '%')
            ->orWhere('serie', 'like', '%' . $busqueda . '%')
            ->orWhere('marca', 'like', '%' . $busqueda . '%')
            ->orWhere('linea', 'like', '%' . $busqueda . '%')
            ->orWhere('numero_inventario_grua', 'like', '%' . $busqueda . '%');
    }

    private function serializeVehiculo(Vehiculo $vehiculo, ?LiberacionCorralon $liberacion = null): array
    {
        $hecho = $vehiculo->relationLoaded('hechos') ? $vehiculo->hechos->first() : null;
        $servicio = $vehiculo->relationLoaded('servicio') ? $vehiculo->servicio : null;
        $origen = $this->origenVehiculo($vehiculo);
        $galeria = $this->galeriaVehiculo($vehiculo, $servicio, $liberacion);

        return [
            'id' => $vehiculo->id,
            'marca' => $vehiculo->marca,
            'linea' => $vehiculo->linea,
            'modelo' => $vehiculo->modelo,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas,
            'serie' => $vehiculo->serie,
            'tipo' => $vehiculo->tipo,
            'numero_inventario_grua' => $vehiculo->numero_inventario_grua,
            'corralon' => $vehiculo->nombreCorralonValido(),
            'fecha_servicio' => $vehiculo->ultimo_servicio_at
                ?? optional($servicio)->created_at
                ?? $vehiculo->fecha_inventario_grua,
            'servicio_descripcion' => optional($servicio)->descripcion,
            'servicio' => $servicio ? [
                'id' => $servicio->id,
                'realizo_maniobras' => $servicio->realizo_maniobras,
                'maniobras_descripcion' => $servicio->maniobras_descripcion,
                'observaciones_grua' => $servicio->observaciones_grua,
                'actualizado_por_grua_at' => $servicio->actualizado_por_grua_at,
            ] : null,
            'origen' => $origen,
            'hecho' => $hecho ? [
                'id' => $hecho->id,
                'fecha' => $hecho->fecha,
                'calle' => $hecho->calle,
                'colonia' => $hecho->colonia,
            ] : null,
            'estado' => $liberacion && $liberacion->estado === 'ENTREGADO'
                ? 'ENTREGADO'
                : 'EN_CORRALON',
            'liberacion' => $liberacion ? [
                'id' => $liberacion->id,
                'persona_recibe' => $liberacion->persona_recibe,
                'identificacion_recibe' => $liberacion->identificacion_recibe,
                'sierse_folio' => $liberacion->sierse_folio,
                'condicion_entrega' => $liberacion->condicion_entrega,
                'observaciones' => $liberacion->observaciones,
                'fecha_entrega' => $liberacion->fecha_entrega,
            ] : null,
            'fotos' => [
                'inventario' => $vehiculo->foto_inventario_grua
                    ? '/grua/corralon/' . $vehiculo->id . '/foto-inventario'
                    : null,
                'entrega' => $liberacion && $liberacion->foto_entrega
                    ? '/grua/corralon/liberacion/' . $liberacion->id
                        . '/evidencia/foto_entrega'
                    : null,
            ],
            'galeria' => $galeria,
        ];
    }

    private function origenVehiculo(Vehiculo $vehiculo): array
    {
        $actividades = $vehiculo->relationLoaded('actividades')
            ? $vehiculo->actividades
            : collect();
        $actividadConduce = $actividades->first(function ($actividad) {
            return $actividad->relationLoaded('conduceLegalidadCaptura')
                && $actividad->conduceLegalidadCaptura;
        });

        if ($actividadConduce) {
            $captura = $actividadConduce->conduceLegalidadCaptura;
            $operativo = optional($captura)->operativo;

            return [
                'tipo' => 'CONDUCE_LEGALIDAD',
                'etiqueta' => 'Conduce con Legalidad',
                'id' => optional($captura)->id,
                'folio' => optional($captura)->folio_numero,
                'fecha' => optional($captura)->fecha ?: $actividadConduce->fecha,
                'lugar' => optional($captura)->lugar ?: $actividadConduce->lugar,
                'detalle' => optional($operativo)->nombre
                    ?: optional($operativo)->titulo
                    ?: 'Operativo Conduce con Legalidad',
            ];
        }

        $hecho = $vehiculo->relationLoaded('hechos') ? $vehiculo->hechos->first() : null;
        if ($hecho) {
            return [
                'tipo' => 'SINIESTRO',
                'etiqueta' => 'Siniestro',
                'id' => $hecho->id,
                'folio' => $hecho->folio_c5i,
                'fecha' => $hecho->fecha,
                'lugar' => collect([$hecho->calle, $hecho->colonia])->filter()->implode(', '),
                'detalle' => $hecho->tipo_hecho,
            ];
        }

        $actividad = $actividades->first();
        if ($actividad) {
            return [
                'tipo' => 'ACTIVIDAD',
                'etiqueta' => 'Actividad',
                'id' => $actividad->id,
                'folio' => $actividad->folio_c5i,
                'fecha' => $actividad->fecha,
                'lugar' => $actividad->lugar,
                'detalle' => optional($actividad->subcategoria)->nombre
                    ?: $actividad->motivo
                    ?: $actividad->nombre,
            ];
        }

        return [
            'tipo' => 'SIN_CLASIFICAR',
            'etiqueta' => 'Sin clasificar',
            'id' => null,
            'folio' => null,
            'fecha' => optional($vehiculo->servicio)->created_at,
            'lugar' => null,
            'detalle' => null,
        ];
    }

    private function galeriaVehiculo(
        Vehiculo $vehiculo,
        ?Servicio $servicio,
        ?LiberacionCorralon $liberacion
    ): array {
        $fotos = collect();

        if ($vehiculo->getRawOriginal('fotos')) {
            $fotos->push([
                'tipo' => 'vehiculo',
                'etiqueta' => 'Vehículo',
                'url' => '/grua/corralon/' . $vehiculo->id . '/foto-vehiculo',
            ]);
        }
        if ($vehiculo->foto_inventario_grua) {
            $fotos->push([
                'tipo' => 'inventario',
                'etiqueta' => 'Inventario de grúa',
                'url' => '/grua/corralon/' . $vehiculo->id . '/foto-inventario',
            ]);
        }
        if ($servicio && $servicio->foto_vehiculo) {
            $fotos->push([
                'tipo' => 'servicio',
                'etiqueta' => 'Servicio de grúa',
                'url' => '/grua/corralon/' . $vehiculo->id . '/foto-servicio',
            ]);
        }

        $actividades = $vehiculo->relationLoaded('actividades')
            ? $vehiculo->actividades
            : collect();
        foreach ($actividades as $actividad) {
            $captura = $actividad->relationLoaded('conduceLegalidadCaptura')
                ? $actividad->conduceLegalidadCaptura
                : null;
            if ($captura && $captura->relationLoaded('fotos')) {
                foreach ($captura->fotos as $foto) {
                    $fotos->push([
                        'tipo' => 'conduce_legalidad',
                        'etiqueta' => 'Conduce con Legalidad',
                        'url' => '/grua/corralon/' . $vehiculo->id
                            . '/foto-conduce/' . $foto->id,
                    ]);
                }
            }

            if ($actividad->relationLoaded('fotos')) {
                foreach ($actividad->fotos as $foto) {
                    $fotos->push([
                        'tipo' => 'actividad',
                        'etiqueta' => 'Actividad',
                        'url' => '/grua/corralon/' . $vehiculo->id
                            . '/foto-actividad/' . $foto->id,
                    ]);
                }
            }
            if ($actividad->foto_path || $actividad->foto_blob_path
                || $actividad->foto_thumbnail_path || $actividad->foto_thumbnail_blob_path) {
                $fotos->push([
                    'tipo' => 'actividad',
                    'etiqueta' => 'Actividad',
                    'url' => '/grua/corralon/' . $vehiculo->id
                        . '/foto-actividad-principal/' . $actividad->id,
                ]);
            }
        }

        if ($liberacion && $liberacion->foto_entrega) {
            $fotos->push([
                'tipo' => 'entrega',
                'etiqueta' => 'Entrega',
                'url' => '/grua/corralon/liberacion/' . $liberacion->id
                    . '/evidencia/foto_entrega',
            ]);
        }

        return $fotos->unique('url')->take(12)->values()->all();
    }

    private function servicioActual(Vehiculo $vehiculo): ?Servicio
    {
        if ($vehiculo->relationLoaded('servicio') && $vehiculo->servicio) {
            return $vehiculo->servicio;
        }

        return $vehiculo->servicios()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    private function vehiculoRelations(string $prefix = ''): array
    {
        return [
            $prefix . 'hechos',
            $prefix . 'servicio.grua',
            $prefix . 'liberacionCorralon',
            $prefix . 'actividades.subcategoria',
            $prefix . 'actividades.fotos',
            $prefix . 'actividades.conduceLegalidadCaptura.operativo',
            $prefix . 'actividades.conduceLegalidadCaptura.fotos',
        ];
    }

    private function resumen(int $gruaId): array
    {
        $hoy = now('America/Mexico_City');
        $liberaciones = LiberacionCorralon::query()
            ->where('grua_id', $gruaId)
            ->where('estado', 'ENTREGADO');

        $serie = collect(range(6, 0))->map(function (int $dias) use ($liberaciones, $hoy) {
            $fecha = $hoy->copy()->subDays($dias);

            return [
                'fecha' => $fecha->format('Y-m-d'),
                'etiqueta' => $fecha->locale('es')->isoFormat('dd'),
                'total' => (clone $liberaciones)->whereDate('fecha_entrega', $fecha)->count(),
            ];
        })->values();

        return [
            'pendientes' => $this->vehiculosPendientesQuery($gruaId)->count(),
            'entregados_hoy' => (clone $liberaciones)
                ->whereDate('fecha_entrega', $hoy)
                ->count(),
            'entregados_mes' => (clone $liberaciones)
                ->whereYear('fecha_entrega', $hoy->year)
                ->whereMonth('fecha_entrega', $hoy->month)
                ->count(),
            'total_entregados' => (clone $liberaciones)->count(),
            'con_observaciones_mes' => (clone $liberaciones)
                ->whereYear('fecha_entrega', $hoy->year)
                ->whereMonth('fecha_entrega', $hoy->month)
                ->where('condicion_entrega', 'CON_OBSERVACIONES')
                ->count(),
            'serie_7_dias' => $serie,
        ];
    }

    private function reporteItems(int $gruaId, string $estado, string $busqueda)
    {
        if ($estado === 'entregados') {
            return LiberacionCorralon::query()
                ->where('grua_id', $gruaId)
                ->where('estado', 'ENTREGADO')
                ->with($this->vehiculoRelations('vehiculo.'))
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->whereHas('vehiculo', function ($vehiculo) use ($busqueda) {
                        $this->buscarVehiculo($vehiculo, $busqueda);
                    });
                })
                ->orderByDesc('fecha_entrega')
                ->get()
                ->map(fn (LiberacionCorralon $liberacion) => $this->serializeVehiculo(
                    $liberacion->vehiculo,
                    $liberacion
                ));
        }

        return $this->vehiculosPendientesQuery($gruaId)
            ->with($this->vehiculoRelations())
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($vehiculo) use ($busqueda) {
                    $this->buscarVehiculo($vehiculo, $busqueda);
                });
            })
            ->orderByDesc('ultimo_servicio_at')
            ->get()
            ->map(fn (Vehiculo $vehiculo) => $this->serializeVehiculo($vehiculo));
    }

    private function csvSeguro($valor): string
    {
        $valor = (string) ($valor ?? '');

        return preg_match('/^[=+\-@]/', $valor) ? "'" . $valor : $valor;
    }

    private function normalizarTelefonoMx(?string $telefono): ?string
    {
        if ($telefono === null || trim($telefono) === '') {
            return null;
        }

        $telefono = preg_replace('/\D+/', '', $telefono);
        if ($telefono === '') {
            return null;
        }
        if (strlen($telefono) === 10) {
            return '521' . $telefono;
        }
        if (strlen($telefono) === 12 && str_starts_with($telefono, '52')) {
            return '521' . substr($telefono, 2);
        }

        return $telefono;
    }
}
