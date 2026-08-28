<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadFoto;
use App\Models\ActividadSubcategoria;
use App\Models\Delegacion;
use App\Models\FomentoCulturaVialPrograma;
use App\Models\Grua;
use App\Models\Vehiculo;
use App\Services\ActividadCorralonInfraccionService;
use App\Services\ActividadConduceLegalidadSyncService;
use App\Services\ActividadDuplicateGuard;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Services\FomentoCulturaVialDetalleManager;
use App\Services\ImageThumbnailService;
use App\Services\VialidadesUrbanasSiniestrosAlertService;
use App\Support\ActividadSubcategoriaCaptura;
use App\Support\HechoAccess;
use App\Support\GruaEditGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActividadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'can:ver actividades']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) $perPage = 1;
        if ($perPage > 20) $perPage = 20;

        $date = $request->query('date');
        $tz = config('app.timezone', 'America/Mexico_City');

        if (empty($date)) {
            $start = now($tz)->startOfDay();
            $end = now($tz)->endOfDay();
            $dateSeleccionada = now($tz)->toDateString();
        } else {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Parámetro date inválido. Usa YYYY-MM-DD.',
                    'errors' => ['date' => ['Formato esperado: YYYY-MM-DD']],
                ], 422);
            }

            $start = Carbon::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $date, $tz)->endOfDay();
            $dateSeleccionada = $date;
        }

        $usuario = Auth::user();

        $query = Actividad::query()
            ->with([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'fotos',
                'vehiculos',
                'fomentoCulturaVialDetalle',
            ])
            ->where(function ($q) use ($start, $end, $dateSeleccionada) {
                $q->whereDate('fecha', $dateSeleccionada)
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->whereNull('fecha')
                          ->whereBetween('created_at', [$start, $end]);
                  });
            });

        $this->applyActividadesVisibilityScope($query, $usuario);

        if ($request->filled('unidad_id')) {
            $unidadId = (int) $request->query('unidad_id');

            if ($unidadId > 0) {
                $this->scopeActividadesUnidad($query, $unidadId);
            }
        }

        if ($request->filled('delegacion_id')) {
            $delegacionId = (int) $request->query('delegacion_id');

            if ($delegacionId > 0) {
                $query->where('delegacion_id', $delegacionId);
            }
        }

        if ($request->filled('actividad_categoria_id')) {
            $query->where('actividad_categoria_id', (int) $request->actividad_categoria_id);
        }

        if ($request->filled('actividad_subcategoria_id')) {
            $query->where('actividad_subcategoria_id', (int) $request->actividad_subcategoria_id);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('lugar', 'like', "%{$q}%")
                    ->orWhere('municipio', 'like', "%{$q}%")
                    ->orWhere('carretera', 'like', "%{$q}%")
                    ->orWhere('tramo', 'like', "%{$q}%")
                    ->orWhere('motivo', 'like', "%{$q}%")
                    ->orWhere('narrativa', 'like', "%{$q}%")
                    ->orWhere('elementos_participantes_texto', 'like', "%{$q}%")
                    ->orWhere('patrullas_participantes_texto', 'like', "%{$q}%");
            });
        }

        $query->orderByDesc(DB::raw('COALESCE(fecha, DATE(created_at))'))
              ->orderByDesc(DB::raw('COALESCE(hora, TIME(created_at))'))
              ->orderByDesc('id');

        $actividades = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'date' => $dateSeleccionada,
            'per_page' => $perPage,
            'data' => $actividades,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('crear actividades');

        $user = Auth::user();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($user);

        $validated = $request->validate(array_merge([
            'client_uuid' => 'nullable|string|max:36',
            'folio_c5i' => 'nullable|string|max:50',
            'actividad_categoria_id' => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'fecha' => $puedeCapturarFechaHora ? 'nullable|date' : 'nullable',
            'hora' => $puedeCapturarFechaHora ? 'nullable|date_format:H:i' : 'nullable',
            'lugar' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'carretera' => 'nullable|string|max:255',
            'tramo' => 'nullable|string|max:255',
            'kilometro' => 'nullable|string|max:50',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'km_recorridos' => 'nullable|numeric|min:0|max:500',
            'coordenadas_texto' => 'nullable|string',
            'fuente_ubicacion' => 'nullable|string|max:50',
            'nota_geo' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'personas_alcanzadas' => 'nullable|integer|min:0',
            'personas_participantes' => 'nullable|integer|min:0',
            'personas_detenidas' => 'nullable|integer|min:0|max:3',
            'elementos_participantes_texto' => 'nullable|string',
            'patrullas_participantes_texto' => 'nullable|string',
            'destacamento_id' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'fotos' => 'nullable|array|min:1',
            'fotos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehiculos' => 'nullable|array',
            'conduce_legalidad_fundamentos' => 'nullable|array|max:20',
            'conduce_legalidad_fundamentos.*.licencia_punto_infraccion_id' => 'required|integer|exists:licencia_punto_infracciones,id',
            'conduce_legalidad_fundamentos.*.infraccion_codigo' => 'nullable|string|max:80',
            'conduce_legalidad_fundamentos.*.fundamento_legal' => 'nullable|string|max:2000',
            'actividad_infracciones' => 'nullable|array|max:20',
            'actividad_infracciones.*.licencia_punto_infraccion_id' => 'required|integer|exists:licencia_punto_infracciones,id',
            'vehiculos.*.marca' => 'required|string|max:50',
            'vehiculos.*.modelo' => 'nullable|string|max:10',
            'vehiculos.*.tipo' => 'required|string|max:50',
            'vehiculos.*.linea' => 'required|string|max:50',
            'vehiculos.*.color' => 'required|string|max:30',
            'vehiculos.*.placas' => 'nullable|string|max:15',
            'vehiculos.*.estado_placas' => 'nullable|string|max:15',
            'vehiculos.*.serie' => 'nullable|string|max:17',
            'vehiculos.*.capacidad_personas' => 'required|integer|min:0',
            'vehiculos.*.tipo_servicio' => 'required|string|max:50',
            'vehiculos.*.tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'vehiculos.*.grua_id' => 'nullable|integer|exists:gruas,id',
            'vehiculos.*.grua' => 'nullable|string|max:255',
            'vehiculos.*.corralon' => 'nullable|string|max:255',
            'vehiculos.*.aseguradora' => 'nullable|string|max:100',
            'vehiculos.*.antecedente_vehiculo' => 'nullable|boolean',
            'vehiculos.*.monto_danos' => 'nullable|numeric|min:0',
            'vehiculos.*.partes_danadas' => 'nullable|string',
        ], FomentoCulturaVialDetalleManager::validationRules()), [
            'personas_detenidas.max' => 'No se pueden capturar mas de 3 personas detenidas.',
        ]);

        if (!empty($validated['client_uuid'])) {
            $actividadExistente = Actividad::query()
                ->where('client_uuid', $validated['client_uuid'])
                ->first();

            if ($actividadExistente) {
                $actividadExistente->load([
                    'categoria',
                    'subcategoria',
                    'unidad',
                    'delegacion',
                    'destacamento',
                    'fotos',
                    'vehiculos',
                    'fomentoCulturaVialDetalle',
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => 'Actividad ya existente.',
                    'created' => false,
                    'data' => $this->withFotoUrls($actividadExistente),
                    'meta' => [
                        'id' => $actividadExistente->id,
                        'client_uuid' => $actividadExistente->client_uuid,
                    ],
                ], 200);
            }
        }

        if (!$request->hasFile('foto') && !$request->hasFile('fotos')) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes subir al menos una foto.',
                'errors' => [
                    'foto' => ['Debes subir al menos una foto.'],
                ],
            ], 422);
        }

        $archivos = collect();

        if ($request->hasFile('foto')) {
            $archivos->push($request->file('foto'));
        }

        if ($request->hasFile('fotos')) {
            foreach ((array) $request->file('fotos', []) as $file) {
                if ($file) {
                    $archivos->push($file);
                }
            }
        }

        $archivos = $archivos->values();

        if ($archivos->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes subir al menos una foto.',
                'errors' => [
                    'foto' => ['Debes subir al menos una foto.'],
                ],
            ], 422);
        }

        if ($response = $this->validarGruasPermitidasEnVehiculosJson($validated['vehiculos'] ?? [], $user)) {
            return $response;
        }

        $tz = config('app.timezone', 'America/Mexico_City');
        $ahora = now($tz);

        $fecha = $puedeCapturarFechaHora && !empty($validated['fecha'])
            ? Carbon::parse($validated['fecha'], $tz)->toDateString()
            : $ahora->toDateString();
        $hora = $puedeCapturarFechaHora && !empty($validated['hora'])
            ? $validated['hora']
            : $ahora->format('H:i');

        $hasCoords = $request->filled('lat') && $request->filled('lng');
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_APP';
        }

        $unidadOrg = (int) ($user->unidad_id ?? 0);
        if ($unidadOrg <= 0) {
            $unidadOrg = 1;
        }

        $delegacionId = (int) ($user->delegacion_id ?? 0);
        $delegacionId = $delegacionId > 0 ? $delegacionId : null;

        $conduceSync = app(ActividadConduceLegalidadSyncService::class);
        $conduceSync->assertCanSync(
            (int) $validated['actividad_subcategoria_id'],
            $unidadOrg,
            $delegacionId,
            $validated['conduce_legalidad_fundamentos'] ?? [],
            count($validated['vehiculos'] ?? [])
        );
        $infraccionesCorralon = app(ActividadCorralonInfraccionService::class)->validarYSnapshot(
            (int) $validated['actividad_subcategoria_id'],
            $validated['actividad_infracciones'] ?? []
        );

        $nombre = mb_strtoupper((string) ($user->name ?? ''), 'UTF-8');
        $cantidad = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            if (!$this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $user
            )) {
                $mensaje = $this->mensajeSubcategoriaNoPermitida(
                    (int) $validated['actividad_subcategoria_id'],
                    $user
                );

                return response()->json([
                    'ok' => false,
                    'message' => $mensaje,
                    'errors' => [
                        'actividad_subcategoria_id' => [$mensaje],
                    ],
                ], 422);
            }
        }

        $duplicateGuard = app(ActividadDuplicateGuard::class);
        $fotoHashes = $duplicateGuard->hashUploadedFiles($archivos);

        if ($duplicateGuard->hasRepeatedHashes($fotoHashes)) {
            return response()->json([
                'ok' => false,
                'message' => 'Estas intentando subir fotos duplicadas en la misma solicitud.',
                'errors' => [
                    'fotos' => ['Estas intentando subir fotos duplicadas en la misma solicitud.'],
                ],
            ], 422);
        }

        $duplicatePayload = array_merge($validated, [
            'fecha' => $fecha,
            'hora' => $hora,
            'unidad_org_id' => $unidadOrg,
            'delegacion_id' => $delegacionId,
        ]);

        if ($duplicateGuard->findRecentDuplicate((int) $user->id, $duplicatePayload, $fotoHashes)) {
            return response()->json([
                'ok' => false,
                'message' => ActividadDuplicateGuard::MESSAGE,
                'errors' => [
                    'fotos' => [ActividadDuplicateGuard::MESSAGE],
                ],
            ], 422);
        }

        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);

        return DB::transaction(function () use ($archivos, $fotoHashes, $validated, $nombre, $cantidad, $user, $unidadOrg, $delegacionId, $fecha, $hora, $fomentoManager, $conduceSync, $infraccionesCorralon) {
            $actividad = Actividad::create([
                'client_uuid' => !empty($validated['client_uuid']) ? $validated['client_uuid'] : (string) Str::uuid(),
                'folio_c5i' => $this->toUpperOrNull($validated['folio_c5i'] ?? null),
                'sync_status' => 'local',
                'sync_error' => null,
                'synced_at' => null,
                'actividad_categoria_id' => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre' => $nombre,
                'cantidad' => $cantidad,
                'foto_path' => null,
                'foto_nombre_original' => null,
                'foto_hash' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'estado_revision' => 'pendiente',
                'revisado_por' => null,
                'revisado_at' => null,
                'observacion_revision' => null,
                'unidad_org_id' => $unidadOrg,
                'delegacion_id' => $delegacionId,
                'destacamento_id' => $validated['destacamento_id'] ?? null,
                'fecha' => $fecha,
                'hora' => $hora,
                'lugar' => $this->toUpperOrNull($validated['lugar'] ?? null),
                'municipio' => $this->toUpperOrNull($validated['municipio'] ?? null),
                'carretera' => $this->toUpperOrNull($validated['carretera'] ?? null),
                'tramo' => $this->toUpperOrNull($validated['tramo'] ?? null),
                'kilometro' => $this->toUpperOrNull($validated['kilometro'] ?? null),
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'km_recorridos' => isset($validated['km_recorridos']) ? (float) $validated['km_recorridos'] : null,
                'coordenadas_texto' => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion' => $validated['fuente_ubicacion'] ?? null,
                'nota_geo' => $validated['nota_geo'] ?? null,
                'motivo' => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa' => $validated['narrativa'] ?? null,
                'acciones_realizadas' => $validated['acciones_realizadas'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'infracciones_actividad' => $infraccionesCorralon ?: null,
                'personas_alcanzadas' => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes' => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas' => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
            ]);

            $fomentoManager->syncForActividad($actividad, $validated);

            $ordenBase = 0;
            $thumbnailDir = $this->actividadThumbnailDirectory($unidadOrg, $fecha);

            foreach ($archivos as $index => $file) {
                $fotoHash = $fotoHashes[$index] ?? hash_file('sha256', $file->getRealPath());
                $fotoNombreOriginal = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                $fotoPath = $file->storeAs('actividades', $filename, 'public');
                $orden = $ordenBase + $index;
                $fotoThumbnailPath = $this->crearThumbnailSeguro(
                    $fotoPath,
                    $thumbnailDir,
                    'actividad_' . $actividad->id . '_foto_' . $orden
                );

                $actividad->fotos()->create([
                    'foto_path' => $fotoPath,
                    'foto_nombre_original' => $fotoNombreOriginal,
                    'foto_hash' => $fotoHash,
                    'foto_thumbnail_path' => $fotoThumbnailPath,
                    'orden' => $orden,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            $fotoPrincipal = $actividad->fotosTodas()
                ->whereNull('foto_archivada_at')
                ->whereNull('foto_eliminada_at')
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

            if ($fotoPrincipal) {
                $actividad->update([
                    'foto_path' => $fotoPrincipal->foto_path,
                    'foto_thumbnail_path' => $fotoPrincipal->foto_thumbnail_path,
                    'foto_archivo_zip_path' => null,
                    'foto_archivada_at' => null,
                    'foto_eliminada_at' => null,
                    'foto_nombre_original' => $fotoPrincipal->foto_nombre_original,
                    'foto_hash' => $fotoPrincipal->foto_hash,
                ]);
            }

            foreach (($validated['vehiculos'] ?? []) as $vehiculoData) {
                $this->crearVehiculoParaActividad($actividad, $vehiculoData);
            }

            $conduceSync->sync(
                $actividad,
                $validated['conduce_legalidad_fundamentos'] ?? []
            );

            if ((int) ($actividad->personas_detenidas ?? 0) > 0) {
                DB::afterCommit(function () use ($actividad) {
                    app(DelegacionesWhatsAppAlertService::class)->notificarActividadConDetenidos($actividad);
                });
            }

            DB::afterCommit(function () use ($actividad) {
                app(VialidadesUrbanasSiniestrosAlertService::class)->notificarActividad($actividad);
            });

            $actividad->load([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'fotos',
                'vehiculos',
                'fomentoCulturaVialDetalle',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Actividad creada correctamente.',
                'created' => true,
                'data' => $this->withFotoUrls($actividad),
                'meta' => [
                    'id' => $actividad->id,
                    'client_uuid' => $actividad->client_uuid,
                ],
            ], 201);
        });
    }

    public function show(Actividad $actividad)
    {
        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No encontrado'
            ], 404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'fotos',
            'vehiculos',
            'fomentoCulturaVialDetalle',
            'puestaDisposicion',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->withFotoUrls($actividad),
        ]);
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para modificar esta actividad'
            ], 403);
        }

        $validated = $request->validate(array_merge([
            'actividad_categoria_id' => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'folio_c5i' => 'nullable|string|max:50',
            'fecha' => $puedeCapturarFechaHora ? 'nullable|date' : 'nullable',
            'hora' => $puedeCapturarFechaHora ? 'nullable|date_format:H:i' : 'nullable',
            'lugar' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'carretera' => 'nullable|string|max:255',
            'tramo' => 'nullable|string|max:255',
            'kilometro' => 'nullable|string|max:50',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'km_recorridos' => 'nullable|numeric|min:0|max:500',
            'coordenadas_texto' => 'nullable|string',
            'fuente_ubicacion' => 'nullable|string|max:50',
            'nota_geo' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'personas_alcanzadas' => 'nullable|integer|min:0',
            'personas_participantes' => 'nullable|integer|min:0',
            'personas_detenidas' => 'nullable|integer|min:0|max:3',
            'elementos_participantes_texto' => 'nullable|string',
            'patrullas_participantes_texto' => 'nullable|string',
            'destacamento_id' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'fotos' => 'nullable|array|min:1',
            'fotos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer',
            'conduce_legalidad_fundamentos' => 'nullable|array|max:20',
            'conduce_legalidad_fundamentos.*.licencia_punto_infraccion_id' => 'required|integer|exists:licencia_punto_infracciones,id',
            'conduce_legalidad_fundamentos.*.infraccion_codigo' => 'nullable|string|max:80',
            'conduce_legalidad_fundamentos.*.fundamento_legal' => 'nullable|string|max:2000',
            'actividad_infracciones' => 'nullable|array|max:20',
            'actividad_infracciones.*.licencia_punto_infraccion_id' => 'required|integer|exists:licencia_punto_infracciones,id',
        ], FomentoCulturaVialDetalleManager::validationRules()), [
            'personas_detenidas.max' => 'No se pueden capturar mas de 3 personas detenidas.',
        ]);

        $user = $usuario;
        $tz = config('app.timezone', 'America/Mexico_City');

        $hasCoords = $request->filled('lat') && $request->filled('lng');
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_APP';
        }

        if ($request->has('lat') && !$request->filled('lat')) {
            $validated['lat'] = null;
        }

        if ($request->has('lng') && !$request->filled('lng')) {
            $validated['lng'] = null;
        }

        if (!empty($validated['actividad_subcategoria_id'])) {
            if (!$this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $user
            )) {
                $mensaje = $this->mensajeSubcategoriaNoPermitida(
                    (int) $validated['actividad_subcategoria_id'],
                    $user
                );

                return response()->json([
                    'ok' => false,
                    'message' => $mensaje,
                    'errors' => [
                        'actividad_subcategoria_id' => [$mensaje],
                    ],
                ], 422);
            }
        }

        $conduceSync = app(ActividadConduceLegalidadSyncService::class);
        if ($conduceSync->isConduceLegalidadSubcategoriaId(
            (int) $validated['actividad_subcategoria_id']
        )) {
            $actividad->loadMissing('vehiculos');
            $conduceSync->assertCanSync(
                (int) $validated['actividad_subcategoria_id'],
                (int) $actividad->unidad_org_id,
                $actividad->delegacion_id ? (int) $actividad->delegacion_id : null,
                $validated['conduce_legalidad_fundamentos'] ?? [],
                $actividad->vehiculos->count()
            );
        }
        $infraccionesCorralon = app(ActividadCorralonInfraccionService::class)->validarYSnapshot(
            (int) $validated['actividad_subcategoria_id'],
            $validated['actividad_infracciones'] ?? []
        );

        $detenidosAntes = (int) ($actividad->personas_detenidas ?? 0);

        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);

        return DB::transaction(function () use ($request, $validated, $actividad, $user, $tz, $detenidosAntes, $puedeCapturarFechaHora, $fomentoManager, $conduceSync, $infraccionesCorralon) {
            $fotoIdsEliminar = collect($request->input('eliminar_fotos', []))
                ->map(function ($id) {
                    return (int) $id;
                })
                ->filter(function ($id) {
                    return $id > 0;
                })
                ->unique()
                ->values()
                ->all();

            $archivos = collect();

            if ($request->hasFile('foto')) {
                $archivos->push($request->file('foto'));
            }

            if ($request->hasFile('fotos')) {
                foreach ((array) $request->file('fotos', []) as $file) {
                    if ($file) {
                        $archivos->push($file);
                    }
                }
            }

            $fotosVisiblesActuales = $actividad->fotos()
                ->when(!empty($fotoIdsEliminar), function ($query) use ($fotoIdsEliminar) {
                    $query->whereNotIn('id', $fotoIdsEliminar);
                })
                ->count();

            $tieneFotoLegacy = $actividad->fotosTodas()->count() === 0
                && !empty($actividad->foto_path)
                && empty($actividad->foto_eliminada_at);

            if (($fotosVisiblesActuales + ($tieneFotoLegacy ? 1 : 0) + $archivos->count()) < 1) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La actividad debe conservar al menos una foto.',
                    'errors' => [
                        'fotos' => ['La actividad debe conservar al menos una foto.'],
                    ],
                ], 422);
            }

            $hashes = [];
            foreach ($archivos as $file) {
                $hash = hash_file('sha256', $file->getRealPath());

                if (in_array($hash, $hashes, true)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Estas intentando subir fotos duplicadas en la misma solicitud.',
                        'errors' => [
                            'fotos' => ['Estas intentando subir fotos duplicadas en la misma solicitud.'],
                        ],
                    ], 422);
                }

                $hashes[] = $hash;

                $yaExisteEnEstaActividad = ActividadFoto::query()
                    ->where('actividad_id', $actividad->id)
                    ->where('foto_hash', $hash)
                    ->whereNull('foto_eliminada_at')
                    ->when(!empty($fotoIdsEliminar), function ($query) use ($fotoIdsEliminar) {
                        $query->whereNotIn('id', $fotoIdsEliminar);
                    })
                    ->exists();

                if ($yaExisteEnEstaActividad) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Una de las fotos ya existe en esta actividad.',
                        'errors' => [
                            'fotos' => ['Una de las fotos ya existe en esta actividad.'],
                        ],
                    ], 422);
                }
            }

            $fechaRespaldo = $actividad->created_at
                ? Carbon::parse($actividad->created_at, $tz)->toDateString()
                : now($tz)->toDateString();

            $horaRespaldo = $actividad->created_at
                ? Carbon::parse($actividad->created_at, $tz)->format('H:i')
                : now($tz)->format('H:i');
            $fechaCaptura = $puedeCapturarFechaHora
                ? ($validated['fecha'] ?? $actividad->fecha ?? $fechaRespaldo)
                : ($actividad->fecha ?? $fechaRespaldo);
            $horaCaptura = $puedeCapturarFechaHora
                ? ($validated['hora'] ?? $actividad->hora ?? $horaRespaldo)
                : ($actividad->hora ?? $horaRespaldo);

            $actividad->update([
                'folio_c5i' => array_key_exists('folio_c5i', $validated) ? $this->toUpperOrNull($validated['folio_c5i']) : $actividad->folio_c5i,
                'actividad_categoria_id' => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'cantidad' => 1,
                'updated_by' => $user->id,
                'fecha' => $fechaCaptura,
                'hora' => $horaCaptura,
                'destacamento_id' => $validated['destacamento_id'] ?? $actividad->destacamento_id,
                'lugar' => array_key_exists('lugar', $validated) ? $this->toUpperOrNull($validated['lugar']) : $actividad->lugar,
                'municipio' => array_key_exists('municipio', $validated) ? $this->toUpperOrNull($validated['municipio']) : $actividad->municipio,
                'carretera' => array_key_exists('carretera', $validated) ? $this->toUpperOrNull($validated['carretera']) : $actividad->carretera,
                'tramo' => array_key_exists('tramo', $validated) ? $this->toUpperOrNull($validated['tramo']) : $actividad->tramo,
                'kilometro' => array_key_exists('kilometro', $validated) ? $this->toUpperOrNull($validated['kilometro']) : $actividad->kilometro,
                'lat' => array_key_exists('lat', $validated) ? $validated['lat'] : $actividad->lat,
                'lng' => array_key_exists('lng', $validated) ? $validated['lng'] : $actividad->lng,
                'km_recorridos' => array_key_exists('km_recorridos', $validated) ? (float) $validated['km_recorridos'] : $actividad->km_recorridos,
                'coordenadas_texto' => array_key_exists('coordenadas_texto', $validated) ? $validated['coordenadas_texto'] : $actividad->coordenadas_texto,
                'fuente_ubicacion' => array_key_exists('fuente_ubicacion', $validated) ? $validated['fuente_ubicacion'] : $actividad->fuente_ubicacion,
                'nota_geo' => array_key_exists('nota_geo', $validated) ? $validated['nota_geo'] : $actividad->nota_geo,
                'motivo' => array_key_exists('motivo', $validated) ? $this->toUpperOrNull($validated['motivo']) : $actividad->motivo,
                'narrativa' => array_key_exists('narrativa', $validated) ? $validated['narrativa'] : $actividad->narrativa,
                'acciones_realizadas' => array_key_exists('acciones_realizadas', $validated) ? $validated['acciones_realizadas'] : $actividad->acciones_realizadas,
                'observaciones' => array_key_exists('observaciones', $validated) ? $validated['observaciones'] : $actividad->observaciones,
                'infracciones_actividad' => $infraccionesCorralon ?: null,
                'personas_alcanzadas' => array_key_exists('personas_alcanzadas', $validated) ? $validated['personas_alcanzadas'] : $actividad->personas_alcanzadas,
                'personas_participantes' => array_key_exists('personas_participantes', $validated) ? $validated['personas_participantes'] : $actividad->personas_participantes,
                'personas_detenidas' => array_key_exists('personas_detenidas', $validated) ? $validated['personas_detenidas'] : $actividad->personas_detenidas,
                'elementos_participantes_texto' => array_key_exists('elementos_participantes_texto', $validated) ? $validated['elementos_participantes_texto'] : $actividad->elementos_participantes_texto,
                'patrullas_participantes_texto' => array_key_exists('patrullas_participantes_texto', $validated) ? $validated['patrullas_participantes_texto'] : $actividad->patrullas_participantes_texto,
            ]);

            $fomentoManager->syncForActividad($actividad, $validated);

            if (!empty($fotoIdsEliminar)) {
                $fotosEliminar = $actividad->fotosTodas()
                    ->whereIn('id', $fotoIdsEliminar)
                    ->whereNull('foto_eliminada_at')
                    ->get();

                foreach ($fotosEliminar as $fotoEliminar) {
                    if (!empty($fotoEliminar->foto_path) && Storage::disk('public')->exists($fotoEliminar->foto_path)) {
                        Storage::disk('public')->delete($fotoEliminar->foto_path);
                    }

                    if (!empty($fotoEliminar->foto_thumbnail_path) && Storage::disk('public')->exists($fotoEliminar->foto_thumbnail_path)) {
                        Storage::disk('public')->delete($fotoEliminar->foto_thumbnail_path);
                    }

                    $fotoEliminar->update([
                        'foto_eliminada_at' => now($tz),
                        'updated_by' => $user->id,
                    ]);
                }
            }

            if ($archivos->isNotEmpty()) {
                $maxOrden = $actividad->fotosTodas()->max('orden');
                $ordenBase = $maxOrden === null ? 0 : ((int) $maxOrden + 1);
                $thumbnailDir = $this->actividadThumbnailDirectory(
                    (int) ($actividad->unidad_org_id ?? $user->unidad_id ?? 0),
                    $actividad->fecha ?? now($tz)->toDateString()
                );

                foreach ($archivos as $index => $file) {
                    $fotoHash = hash_file('sha256', $file->getRealPath());
                    $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                    $fotoPath = $file->storeAs('actividades', $filename, 'public');
                    $orden = $ordenBase + $index;
                    $fotoThumbnailPath = $this->crearThumbnailSeguro(
                        $fotoPath,
                        $thumbnailDir,
                        'actividad_' . $actividad->id . '_foto_' . $orden
                    );

                    $actividad->fotos()->create([
                        'foto_path' => $fotoPath,
                        'foto_nombre_original' => $file->getClientOriginalName(),
                        'foto_hash' => $fotoHash,
                        'foto_thumbnail_path' => $fotoThumbnailPath,
                        'orden' => $orden,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }

            $this->sincronizarFotoPrincipal($actividad);

            $conduceSync->sync(
                $actividad,
                $validated['conduce_legalidad_fundamentos'] ?? []
            );

            $alertService = app(DelegacionesWhatsAppAlertService::class);

            if ($alertService->debeNotificarActividadConDetenidos($detenidosAntes, $actividad)) {
                DB::afterCommit(function () use ($actividad) {
                    app(DelegacionesWhatsAppAlertService::class)->notificarActividadConDetenidos($actividad);
                });
            }

            $actividad->load([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'fotos',
                'vehiculos',
                'fomentoCulturaVialDetalle',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Actividad actualizada correctamente.',
                'data' => $this->withFotoUrls($actividad),
            ]);
        });
    }

    public function destroy(Actividad $actividad)
    {
        $this->authorize('eliminar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para eliminar esta actividad'
            ], 403);
        }

        $actividad->loadMissing('vehiculos');

        if (
            GruaEditGuard::locksActividad($usuario, $actividad)
            && $actividad->vehiculos->contains(fn ($vehiculo) => GruaEditGuard::vehicleHasGruaData($vehiculo))
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta actividad tiene grúa o corralón bloqueado. Solicita autorización de un Administrador.',
            ], 403);
        }

        return DB::transaction(function () use ($actividad) {

            if (!empty($actividad->foto_path) && Storage::disk('public')->exists($actividad->foto_path)) {
                Storage::disk('public')->delete($actividad->foto_path);
            }

            if (!empty($actividad->foto_thumbnail_path) && Storage::disk('public')->exists($actividad->foto_thumbnail_path)) {
                Storage::disk('public')->delete($actividad->foto_thumbnail_path);
            }

            $actividad->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Actividad eliminada correctamente.',
            ]);
        });
    }

    public function categorias()
    {
        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);
        $fomentoCategoriaIds = $fomentoManager->categoriaIds();
        $usuarioEsFomento = $fomentoManager->usuarioEsFomento(Auth::user());

        $items = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug'])
            ->map(function ($categoria) use ($fomentoCategoriaIds, $usuarioEsFomento) {
                return [
                    'id' => (int) $categoria->id,
                    'nombre' => $categoria->nombre,
                    'slug' => $categoria->slug,
                    'requiere_fomento_cultura_vial' => $usuarioEsFomento || in_array((int) $categoria->id, $fomentoCategoriaIds, true),
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $usuario = Auth::user();

        $programas = FomentoCulturaVialPrograma::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'actividad_subcategoria_id', 'nombre'])
            ->groupBy('actividad_subcategoria_id');

        $items = $this->obtenerSubcategoriasDisponibles((int) $categoria->id, $usuario)
            ->map(function ($subcategoria) use ($programas) {
                return [
                    'id' => (int) $subcategoria->id,
                    'nombre' => $subcategoria->nombre,
                    'programas_fomento' => ($programas->get($subcategoria->id, collect()))
                        ->map(function ($programa) {
                            return [
                                'id' => (int) $programa->id,
                                'nombre' => $programa->nombre,
                            ];
                        })
                        ->values(),
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
    }

    private function obtenerSubcategoriasDisponibles(int $categoriaId, $usuario)
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $query = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $categoriaId)
            ->where('activo', 1);

        if ($categoriaId === 10 && $unidadId === 2) {
            $query->where('unidad_id', 2);
        } else {
            $query->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id')
                  ->orWhere('unidad_id', $unidadId);
            });
        }

        return ActividadSubcategoriaCaptura::filtrarParaUsuario(
            $query->orderBy('nombre')->get(['id', 'nombre', 'unidad_id']),
            $usuario
        );
    }

    private function subcategoriaPermitidaParaUsuario(int $categoriaId, int $subcategoriaId, $usuario): bool
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $query = ActividadSubcategoria::query()
            ->where('id', $subcategoriaId)
            ->where('actividad_categoria_id', $categoriaId)
            ->where('activo', 1);

        if ($categoriaId === 10 && $unidadId === 2) {
            $query->where('unidad_id', 2);
        } else {
            $query->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id')
                  ->orWhere('unidad_id', $unidadId);
            });
        }

        $subcategoria = $query->first(['id', 'nombre']);

        return $subcategoria !== null
            && ActividadSubcategoriaCaptura::permitidaParaUsuario($subcategoria, $usuario);
    }

    private function mensajeSubcategoriaNoPermitida(int $subcategoriaId, $usuario): string
    {
        $subcategoria = ActividadSubcategoria::query()
            ->find($subcategoriaId, ['id', 'nombre']);

        if ($subcategoria) {
            $mensaje = ActividadSubcategoriaCaptura::mensajeRechazoParaUsuario(
                $subcategoria,
                $usuario
            );

            if ($mensaje !== null) {
                return $mensaje;
            }
        }

        return 'La subcategoría no pertenece a la categoría seleccionada o no está permitida para tu unidad.';
    }

    public function compartir(Actividad $actividad)
    {
        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json(['ok' => false, 'message' => 'No encontrado'], 404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'fotos',
        ]);

        $fecha = $actividad->fecha ? Carbon::parse($actividad->fecha)->format('d/m/Y') : '';
        $hora = $actividad->hora ? substr((string)$actividad->hora, 0, 5) : '';
        [$lat, $lng, $coordenadas] = $this->coordenadasActividad($actividad);

        $texto = "GUARDIA CIVIL\n\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n\n";

        if (optional($actividad->unidad)->nombre) {
            $texto .= $actividad->unidad->nombre . "\n\n";
        }

        if (optional($actividad->delegacion)->nombre) {
            $texto .= $actividad->delegacion->nombre . "\n\n";
        } elseif (optional($actividad->destacamento)->nombre) {
            $texto .= $actividad->destacamento->nombre . "\n\n";
        }

        $texto .= "ID DE ACTIVIDAD: {$actividad->id}\n\n";

        if ($fecha) {
            $texto .= "FECHA {$fecha}\n";
        }

        if ($hora) {
            $texto .= "HORA {$hora}\n";
        }

        if ($coordenadas !== '') {
            $texto .= "COORDENADAS: {$coordenadas}\n";

            if ($lat !== null && $lng !== null) {
                $texto .= "GOOGLE MAPS: https://www.google.com/maps?q={$lat},{$lng}\n";
            }
        }

        if ($hora || $coordenadas !== '') {
            $texto .= "\n";
        }

        if ($actividad->motivo) {
            $texto .= "ASUNTO: " . mb_strtoupper($actividad->motivo, 'UTF-8') . "\n\n";
        }

        $infraccionesActividad = is_array($actividad->infracciones_actividad)
            ? $actividad->infracciones_actividad
            : [];
        if ($infraccionesActividad !== []) {
            $tipoRemision = trim((string) optional($actividad->subcategoria)->nombre);
            if ($tipoRemision !== '') {
                $texto .= "TIPO DE REMISIÓN: " . mb_strtoupper($tipoRemision, 'UTF-8') . "\n";
            }
            $texto .= "FUNDAMENTO(S) DE LA INFRACCIÓN\n";
            foreach ($infraccionesActividad as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $nombre = trim((string) (
                    $item['texto_operativo']
                    ?? $item['nombre']
                    ?? $item['descripcion']
                    ?? $item['codigo']
                    ?? 'Fundamento legal'
                ));
                $legal = trim((string) (
                    $item['fundamento_legal']
                    ?? $item['referencia_legal_corta']
                    ?? ''
                ));
                $sancion = trim((string) ($item['resumen_sanciones'] ?? ''));
                $texto .= ($index + 1) . ". " . $nombre . "\n";
                if ($legal !== '') {
                    $texto .= "   {$legal}\n";
                }
                if ($sancion !== '') {
                    $texto .= "   SANCIÓN: {$sancion}\n";
                }
            }
            $texto .= "\n";
        }

        if ($actividad->narrativa) $texto .= trim($actividad->narrativa) . "\n\n";
        if ($actividad->acciones_realizadas) $texto .= trim($actividad->acciones_realizadas) . "\n\n";
        if ($actividad->observaciones) $texto .= trim($actividad->observaciones) . "\n\n";

        $texto .= "DATOS GENERALES\n";
        $texto .= "PERSONAS ALCANZADAS: " . (int)($actividad->personas_alcanzadas ?? 0) . "\n";
        $texto .= "PERSONAS PARTICIPANTES: " . (int)($actividad->personas_participantes ?? 0) . "\n";
        $texto .= "PERSONAS DETENIDAS: " . (int)($actividad->personas_detenidas ?? 0) . "\n\n";

        if ($actividad->elementos_participantes_texto) {
            $texto .= "ESTADO DE FUERZA\n" . $actividad->elementos_participantes_texto . "\n\n";
        }

        if ($actividad->patrullas_participantes_texto) {
            $texto .= "CRP\n" . $actividad->patrullas_participantes_texto . "\n\n";
        }

        $fotos = $actividad->fotos
            ->sortBy([['orden','asc'],['id','asc']])
            ->map(function ($f) {
                $path = $f->foto_thumbnail_path ?: $f->foto_path ?: $f->foto_thumbnail_blob_path ?: $f->foto_blob_path;

                return $path ? route('actividades.fotos.archivo', [$f->id, 'thumbnail']) : null;
            })
            ->filter()
            ->values();

        $fotoActividad = $actividad->foto_thumbnail_path ?: $actividad->foto_path ?: $actividad->foto_thumbnail_blob_path ?: $actividad->foto_blob_path;

        if ($fotos->isEmpty() && $fotoActividad) {
            $fotos = collect([route('actividades.fotos.principal_archivo', [$actividad->id, 'thumbnail'])]);
        }

        return response()->json([
            'ok' => true,
            'texto' => trim($texto),
            'fotos' => $fotos,
        ]);
    }

    public function compartirTotalesWhatsapp(Request $request)
    {
        $usuario = Auth::user();
        $tz = 'America/Mexico_City';

        $fecha = $request->input('fecha') ?? now($tz)->toDateString();

        $unidadId = (int)($usuario->unidad_id ?? 0);

        $categorias = DB::table('actividad_categorias')
            ->join('actividades', 'actividades.actividad_categoria_id','=','actividad_categorias.id')
            ->whereDate('actividades.fecha',$fecha)
            ->where('actividades.unidad_org_id',$unidadId)
            ->groupBy('actividad_categorias.id','actividad_categorias.nombre')
            ->select('actividad_categorias.nombre', DB::raw('SUM(actividades.cantidad) as total'))
            ->get();

        if ($categorias->isEmpty()) {
            return response()->json(['ok'=>false,'message'=>'Sin datos'],404);
        }

        $fechaTexto = Carbon::parse($fecha,$tz)->locale('es')->translatedFormat('l d F Y');

        $texto = "GUARDIA CIVIL\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n";
        $texto .= strtoupper($fechaTexto)."\n";
        $texto .= "ACTIVIDADES RELEVANTES\n\n";

        foreach ($categorias as $cat) {
            $texto .= "- {$cat->nombre}: " . str_pad($cat->total,2,'0',STR_PAD_LEFT) . "\n";
        }

        return response()->json([
            'ok'=>true,
            'texto'=>trim($texto),
            'fotos'=>[]
        ]);
    }

    private function coordenadasActividad(Actividad $actividad): array
    {
        $lat = $this->formatearCoordenadaActividad($actividad->lat ?? null);
        $lng = $this->formatearCoordenadaActividad($actividad->lng ?? null);

        if ($lat !== null && $lng !== null) {
            return [$lat, $lng, "{$lat}, {$lng}"];
        }

        $coordenadasTexto = trim((string) ($actividad->coordenadas_texto ?? ''));

        return [null, null, $coordenadasTexto];
    }

    private function formatearCoordenadaActividad($coordenada): ?string
    {
        if ($coordenada === null || $coordenada === '') {
            return null;
        }

        if (!is_numeric($coordenada)) {
            return null;
        }

        return number_format((float) $coordenada, 7, '.', '');
    }

    private function applyActividadesVisibilityScope($query, $usuario): void
    {
        $unidadId = (int)($usuario->unidad_id ?? 0);

        if ($usuario->hasRole('Superadmin') || $usuario->hasRole('Coordinador') || $unidadId === 3) {
            return;
        }

        if ($unidadId === 2) {
            $this->scopeActividadesUnidad($query, 2);

            if ($this->esRolAdministrativoUnidad($usuario)) {
                return;
            }

            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $ids = HechoAccess::delegacionIdsVisiblesParaUsuario($usuario);

            if (empty($ids)) {
                $query->whereRaw('1=0');
                return;
            }

            $query->whereIn('delegacion_id', $ids);
            return;
        }

        if ($unidadId > 0) {
            $this->scopeActividadesUnidad($query, $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function toUpperOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_strtoupper($value, 'UTF-8');
    }

    private function sincronizarFotoPrincipal(Actividad $actividad): void
    {
        $fotoPrincipal = $actividad->fotosTodas()
            ->whereNull('foto_archivada_at')
            ->whereNull('foto_eliminada_at')
            ->orderBy('orden')
            ->orderBy('id')
            ->first();

        $fotoArchivada = $fotoPrincipal
            ? null
            : $actividad->fotos()
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

        $fotoReferencia = $fotoPrincipal ?: $fotoArchivada;

        $actividad->update([
            'foto_path' => optional($fotoPrincipal)->foto_path,
            'foto_thumbnail_path' => optional($fotoReferencia)->foto_thumbnail_path,
            'foto_archivo_zip_path' => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivo_zip_path,
            'foto_archivada_at' => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivada_at,
            'foto_eliminada_at' => null,
            'foto_nombre_original' => optional($fotoReferencia)->foto_nombre_original,
            'foto_hash' => optional($fotoReferencia)->foto_hash,
        ]);

        $actividad->refresh();
    }

    private function withFotoUrls(Actividad $actividad): array
    {
        $actividad->loadMissing(
            'creador:id,unidad_id',
            'conduceLegalidadCaptura.operativo',
            'conduceLegalidadCaptura.fundamentos.infraccion'
        );

        $data = $actividad->toArray();
        $data['creador_unidad_id'] = $actividad->creador
            ? (int) $actividad->creador->unidad_id
            : null;
        $actividadArchivada = !empty($actividad->foto_archivo_zip_path) || !empty($actividad->foto_archivada_at);
        $fotoDisplayPath = !$actividadArchivada && (!empty($actividad->foto_path) || !empty($actividad->foto_blob_path))
            ? ($actividad->foto_path ?: $actividad->foto_blob_path)
            : ($actividad->foto_thumbnail_path ?: $actividad->foto_path ?: $actividad->foto_thumbnail_blob_path ?: $actividad->foto_blob_path);

        $data['foto_thumbnail_url'] = $this->actividadPrincipalFotoUrl($actividad, 'thumbnail');
        $data['foto_url'] = $fotoDisplayPath ? $this->actividadPrincipalFotoUrl($actividad, 'original') : null;
        $data['foto_preview_url'] = $this->actividadPrincipalFotoUrl($actividad, 'thumbnail');

        if (!empty($data['fotos']) && is_array($data['fotos'])) {
            $data['fotos'] = array_map(function ($foto) {
                $thumbnailPath = $foto['foto_thumbnail_path'] ?? null;
                $fotoPath = $foto['foto_path'] ?? null;
                $thumbnailBlobPath = $foto['foto_thumbnail_blob_path'] ?? null;
                $fotoBlobPath = $foto['foto_blob_path'] ?? null;
                $fotoArchivada = !empty($foto['foto_archivo_zip_path']) || !empty($foto['foto_archivada_at']);
                $displayPath = $fotoArchivada
                    ? ($thumbnailPath ?: $fotoPath ?: $thumbnailBlobPath ?: $fotoBlobPath)
                    : ($fotoPath ?: $thumbnailPath ?: $fotoBlobPath ?: $thumbnailBlobPath);

                $foto['foto_thumbnail_url'] = $this->actividadFotoUrl($foto, 'thumbnail');
                $foto['foto_preview_url'] = ($thumbnailPath ?: $thumbnailBlobPath ?: $displayPath)
                    ? $this->actividadFotoUrl($foto, 'thumbnail')
                    : null;
                $foto['foto_url'] = $displayPath ? $this->actividadFotoUrl($foto, 'original') : null;
                return $foto;
            }, $data['fotos']);
        }

        $captura = $actividad->conduceLegalidadCaptura;
        $data['conduce_legalidad_operativo_id'] = $captura ? (int) $captura->operativo_id : null;
        $data['conduce_legalidad_captura_id'] = $captura ? (int) $captura->id : null;
        $data['conduce_legalidad_fundamentos'] = $captura
            ? $captura->fundamentos->map(function ($item) {
                $infraccion = $item->infraccion;
                if (!$infraccion) {
                    return [
                        'id' => (int) $item->licencia_punto_infraccion_id,
                        'codigo' => $item->infraccion_codigo,
                        'nombre' => $item->infraccion_codigo ?: 'Fundamento legal',
                        'fundamento_legal' => $item->fundamento_legal,
                        'retencion_vehiculo' => true,
                    ];
                }

                $payload = $infraccion->toArray();
                $payload['id'] = (int) $infraccion->id;
                $payload['codigo'] = $item->infraccion_codigo ?: $infraccion->codigo;
                $payload['fundamento_legal'] = $item->fundamento_legal ?: $infraccion->fundamento_legal;
                $payload['referencia_legal_corta'] = $infraccion->referencia_legal_corta;
                $payload['resumen_sanciones'] = $infraccion->resumen_sanciones;
                $payload['ambito_vehiculo_texto'] = $infraccion->ambito_vehiculo_texto;
                return $payload;
            })->values()->all()
            : [];

        return $data;
    }

    private function actividadThumbnailDirectory(int $unidadId, $fecha): string
    {
        $unidadId = $unidadId > 0 ? $unidadId : 0;
        $year = null;

        if ($fecha) {
            try {
                $year = Carbon::parse($fecha)->format('Y');
            } catch (\Throwable $e) {
                $year = null;
            }
        }

        $year = $year ?: now('America/Mexico_City')->format('Y');

        return 'actividades_thumbnails/unidad_' . $unidadId . '/' . $year;
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

    private function actividadFotoUrl(array $foto, string $tipo): ?string
    {
        $id = $foto['id'] ?? null;

        if (!$id) {
            return null;
        }

        return route('actividades.fotos.archivo', [$id, $tipo === 'thumbnail' ? 'thumbnail' : 'original']);
    }

    private function actividadPrincipalFotoUrl(Actividad $actividad, string $tipo): ?string
    {
        $hasPath = $tipo === 'thumbnail'
            ? ($actividad->foto_thumbnail_path || $actividad->foto_thumbnail_blob_path || $actividad->foto_path || $actividad->foto_blob_path)
            : ($actividad->foto_path || $actividad->foto_blob_path || $actividad->foto_thumbnail_path || $actividad->foto_thumbnail_blob_path);

        if (!$hasPath) {
            return null;
        }

        return route('actividades.fotos.principal_archivo', [$actividad->id, $tipo === 'thumbnail' ? 'thumbnail' : 'original']);
    }

    private function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasAnyRole(['Delegado', 'Administrativo']);
    }

    private function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector');
    }

    private function userCanCaptureFechaHora($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector');
    }

    private function scopeActividadesUnidad($query, int $unidadId): void
    {
        $query->where(function ($q) use ($unidadId) {
            $q->where('unidad_org_id', $unidadId)
                ->orWhere(function ($legacy) use ($unidadId) {
                    $legacy->whereNull('unidad_org_id')
                        ->whereHas('creador', function ($creador) use ($unidadId) {
                            $creador->where('unidad_id', $unidadId);
                        });
                });
        });
    }

    public function storeVehiculo(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para modificar esta actividad'
            ], 403);
        }

        $conduceSync = app(ActividadConduceLegalidadSyncService::class);
        if ($conduceSync->isConduceLegalidadSubcategoriaId($actividad->actividad_subcategoria_id)
            && $actividad->vehiculos()->count() >= 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Cada alimentación de Conduce con Legalidad admite únicamente un vehículo.',
                'errors' => ['vehiculos' => ['Solo puedes agregar un vehículo.']],
            ], 422);
        }

        $validated = $this->validarVehiculoRequest($request);

        if (!$this->gruaPermitidaParaUsuario($validated['grua_id'] ?? null, $usuario)) {
            return response()->json([
                'ok' => false,
                'message' => 'La grúa seleccionada no está disponible para tu unidad o delegación.',
                'errors' => [
                    'grua_id' => ['La grúa seleccionada no está disponible para tu unidad o delegación.'],
                ],
            ], 422);
        }

        return DB::transaction(function () use ($actividad, $validated, $conduceSync) {
            $vehiculo = $this->crearVehiculoParaActividad($actividad, $validated);
            $actividad->unsetRelation('vehiculos');
            $conduceSync->sync($actividad);

            $actividad->load([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'fotos',
                'vehiculos',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Vehículo agregado correctamente.',
                'vehiculo' => $vehiculo,
                'data' => $this->withFotoUrls($actividad),
            ], 201);
        });
    }

    public function destroyVehiculo(Actividad $actividad, $vehiculoId)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para modificar esta actividad'
            ], 403);
        }

        $vehiculo = Vehiculo::find($vehiculoId);

        if (!$vehiculo || !$actividad->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el vehículo dentro de esta actividad.',
            ], 404);
        }

        if (
            GruaEditGuard::locksActividad($usuario, $actividad)
            && GruaEditGuard::vehicleHasGruaData($vehiculo)
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'La grúa o corralón de este vehículo está bloqueado. Solicita autorización de un Administrador.',
            ], 403);
        }

        DB::transaction(function () use ($actividad, $vehiculoId) {
            $actividad->vehiculos()->detach($vehiculoId);

            $tieneOtroOrigen = DB::table('hecho_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('actividad_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('operativo_dispositivo_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('puestas_disposicion_vehiculos')->where('vehiculo_id', $vehiculoId)->exists();

            if (!$tieneOtroOrigen) {
                DB::table('servicios')->where('vehiculo_id', $vehiculoId)->delete();
            }

            $actividad->unsetRelation('vehiculos');
            app(ActividadConduceLegalidadSyncService::class)->sync($actividad);
        });

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'fotos',
            'vehiculos',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Vehículo desvinculado correctamente.',
            'data' => $this->withFotoUrls($actividad),
        ]);
    }

    public function updateVehiculo(Request $request, Actividad $actividad, $vehiculoId)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();
        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para modificar esta actividad',
            ], 403);
        }

        $vehiculo = $actividad->vehiculos()
            ->where('vehiculos.id', $vehiculoId)
            ->first();

        if (!$vehiculo) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el vehículo dentro de esta actividad.',
            ], 404);
        }

        $validated = $this->validarVehiculoRequest($request);

        if (!$this->gruaPermitidaParaUsuario($validated['grua_id'] ?? null, $usuario)) {
            return response()->json([
                'ok' => false,
                'message' => 'La grúa seleccionada no está disponible para tu unidad o delegación.',
                'errors' => [
                    'grua_id' => ['La grúa seleccionada no está disponible para tu unidad o delegación.'],
                ],
            ], 422);
        }

        if (GruaEditGuard::locksActividad($usuario, $actividad)) {
            $gruaCoincide = GruaEditGuard::requestedGruaMatchesCurrent(
                $vehiculo,
                !empty($validated['grua_id']) ? (int) $validated['grua_id'] : null
            );
            if (
                !$gruaCoincide
                && GruaEditGuard::currentGruaId($vehiculo) === null
                && GruaEditGuard::normalizeProtectedText($vehiculo->grua)
                    === GruaEditGuard::normalizeProtectedText($validated['grua'] ?? null)
            ) {
                $gruaCoincide = true;
            }
            $corralonCoincide = GruaEditGuard::normalizeProtectedText($vehiculo->corralon)
                === GruaEditGuard::normalizeProtectedText($validated['corralon'] ?? null);

            if (!$gruaCoincide || !$corralonCoincide) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La grúa o corralón ya quedó fijo. Solicita autorización de un Administrador.',
                    'errors' => [
                        'grua_id' => $gruaCoincide ? [] : ['No puedes cambiar la grúa asignada.'],
                        'corralon' => $corralonCoincide ? [] : ['No puedes cambiar el corralón asignado.'],
                    ],
                ], 422);
            }
        }

        return DB::transaction(function () use ($actividad, $vehiculo, $validated) {
            $vehiculo->fill($this->vehiculoAttributesParaActividad($validated));
            $vehiculo->save();
            $this->registrarServicioGruaParaActividad($actividad, $vehiculo, $validated);

            $actividad->unsetRelation('vehiculos');
            app(ActividadConduceLegalidadSyncService::class)->sync($actividad);
            $actividad->load([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'fotos',
                'vehiculos',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Vehículo actualizado correctamente.',
                'vehiculo' => $vehiculo->fresh(),
                'data' => $this->withFotoUrls($actividad),
            ]);
        });
    }

    private function validarVehiculoRequest(Request $request): array
    {
        $request->merge($this->normalizarVehiculoData($request->only(array_keys($this->vehiculoRules()))));

        return $request->validate($this->vehiculoRules());
    }

    private function vehiculoRules(): array
    {
        return [
            'marca' => 'required|string|max:50',
            'modelo' => 'nullable|string|max:10',
            'tipo' => 'required|string|max:50',
            'linea' => 'required|string|max:50',
            'color' => 'required|string|max:30',
            'placas' => 'nullable|string|max:15',
            'estado_placas' => 'nullable|string|max:15',
            'serie' => 'nullable|string|max:17',
            'capacidad_personas' => 'required|integer|min:0',
            'tipo_servicio' => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'grua_id' => 'nullable|integer|exists:gruas,id',
            'grua' => 'nullable|string|max:255',
            'corralon' => 'nullable|string|max:255',
            'aseguradora' => 'nullable|string|max:100',
            'antecedente_vehiculo' => 'nullable|boolean',
            'monto_danos' => 'nullable|numeric|min:0',
            'partes_danadas' => 'nullable|string',
        ];
    }

    private function normalizarVehiculoData(array $data): array
    {
        foreach ([
            'marca',
            'modelo',
            'tipo',
            'linea',
            'color',
            'placas',
            'estado_placas',
            'serie',
            'tipo_servicio',
            'tarjeta_circulacion_nombre',
            'grua_id',
            'grua',
            'corralon',
            'aseguradora',
            'partes_danadas',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
    }

    private function crearVehiculoParaActividad(Actividad $actividad, array $data): Vehiculo
    {
        $vehiculo = Vehiculo::create(array_merge(
            ['client_uuid' => (string) Str::uuid()],
            $this->vehiculoAttributesParaActividad($data),
            ['fotos' => null]
        ));

        $actividad->vehiculos()->syncWithoutDetaching([$vehiculo->id]);

        $this->registrarServicioGruaParaActividad($actividad, $vehiculo, $data);

        return $vehiculo;
    }

    private function vehiculoAttributesParaActividad(array $data): array
    {
        $data = $this->normalizarVehiculoData($data);
        $gruaId = !empty($data['grua_id']) ? (int) $data['grua_id'] : null;
        $nombreGrua = $gruaId
            ? Grua::query()->whereKey($gruaId)->value('nombre')
            : null;

        return [
            'marca' => $this->toUpperOrNull($data['marca'] ?? null),
            'modelo' => $this->toUpperOrNull($data['modelo'] ?? null),
            'tipo' => $this->toUpperOrNull($data['tipo'] ?? null),
            'linea' => $this->toUpperOrNull($data['linea'] ?? null),
            'color' => $this->toUpperOrNull($data['color'] ?? null),
            'placas' => $this->toUpperOrNull(str_replace('-', '', (string) ($data['placas'] ?? ''))),
            'estado_placas' => $this->toUpperOrNull($data['estado_placas'] ?? null),
            'serie' => $this->toUpperOrNull(str_replace('-', '', (string) ($data['serie'] ?? ''))),
            'capacidad_personas' => $data['capacidad_personas'] ?? 0,
            'tipo_servicio' => $this->toUpperOrNull($data['tipo_servicio'] ?? null),
            'tarjeta_circulacion_nombre' => $this->toUpperOrNull($data['tarjeta_circulacion_nombre'] ?? null),
            'grua' => $this->toUpperOrNull($nombreGrua ?: ($data['grua'] ?? null)),
            'grua_id' => $gruaId,
            'corralon' => $this->toUpperOrNull($data['corralon'] ?? null),
            'aseguradora' => $this->toUpperOrNull($data['aseguradora'] ?? null),
            'antecedente_vehiculo' => (int) ($data['antecedente_vehiculo'] ?? 0),
            'monto_danos' => $data['monto_danos'] ?? 0,
            'partes_danadas' => $this->toUpperOrNull($data['partes_danadas'] ?? null),
        ];
    }

    private function registrarServicioGruaParaActividad(Actividad $actividad, Vehiculo $vehiculo, array $data): void
    {
        $gruaId = !empty($data['grua_id']) ? (int) $data['grua_id'] : null;

        if (!$gruaId) {
            DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();
            return;
        }

        $unidadId = (int) ($actividad->unidad_org_id ?? 0);
        $unidadId = $unidadId > 0 ? $unidadId : 1;

        DB::table('servicios')->updateOrInsert(
            ['vehiculo_id' => $vehiculo->id],
            [
                'grua_id' => $gruaId,
                'unidad_id' => $unidadId,
                'delegacion_id' => $actividad->delegacion_id,
                'tipo_vehiculo' => $this->toUpperOrNull($data['tipo'] ?? $vehiculo->tipo),
                'aseguradora' => $this->toUpperOrNull($data['aseguradora'] ?? $vehiculo->aseguradora) ?? '',
                'created_at' => $this->fechaServicioActividad($actividad),
                'updated_at' => now(),
            ]
        );
    }

    private function fechaServicioActividad(Actividad $actividad): string
    {
        $fecha = $actividad->fecha
            ? Carbon::parse($actividad->fecha)->format('Y-m-d')
            : now('America/Mexico_City')->toDateString();

        $hora = $actividad->hora
            ? Carbon::parse($actividad->hora)->format('H:i:s')
            : '12:00:00';

        return $fecha . ' ' . $hora;
    }

    private function validarGruasPermitidasEnVehiculosJson(array $vehiculos, $usuario)
    {
        foreach ($vehiculos as $index => $vehiculo) {
            if (!$this->gruaPermitidaParaUsuario($vehiculo['grua_id'] ?? null, $usuario)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La grúa seleccionada no está disponible para tu unidad o delegación.',
                    'errors' => [
                        "vehiculos.{$index}.grua_id" => ['La grúa seleccionada no está disponible para tu unidad o delegación.'],
                    ],
                ], 422);
            }
        }

        return null;
    }

    private function gruaPermitidaParaUsuario($gruaId, $usuario): bool
    {
        if (empty($gruaId)) {
            return true;
        }

        return $this->gruasDisponiblesQuery($usuario)
            ->where('gruas.id', (int) $gruaId)
            ->exists();
    }

    private function gruasDisponiblesQuery($usuario)
    {
        $query = Grua::query()->orderBy('nombre');

        if (!$usuario) {
            return $query->whereRaw('1 = 0');
        }

        if (GruaEditGuard::canViewFullGruaCatalog($usuario)) {
            return $query;
        }

        if (GruaEditGuard::usesSiniestrosGruaCatalog($usuario)) {
            return $query->whereHas('unidades', function ($q) {
                $q->where('unidades.id', 1);
            });
        }

        if ((int) $usuario->unidad_id === 2) {
            $delegacionIds = $this->obtenerIdsDelegacionesGruasUsuario($usuario);

            if (empty($delegacionIds)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                $q->whereIn('delegaciones.id', $delegacionIds);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function obtenerIdsDelegacionesGruasUsuario($usuario): array
    {
        $ids = [];

        if (!empty($usuario->delegacion_id)) {
            $ids[] = (int) $usuario->delegacion_id;
        }

        $idsPivot = DB::table('delegacion_user')
            ->where('user_id', $usuario->id)
            ->pluck('delegacion_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        return array_values(array_unique(array_merge($ids, $idsPivot)));
    }
}
