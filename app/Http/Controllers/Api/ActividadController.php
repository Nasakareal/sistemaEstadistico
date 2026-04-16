<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
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
                'vehiculos'
            ])
            ->where(function ($q) use ($start, $end, $dateSeleccionada) {
                $q->whereDate('fecha', $dateSeleccionada)
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->whereNull('fecha')
                          ->whereBetween('created_at', [$start, $end]);
                  });
            });

        $this->applyActividadesVisibilityScope($query, $usuario);

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

        $validated = $request->validate([
            'client_uuid' => 'nullable|string|max:36',
            'actividad_categoria_id' => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'fecha' => 'nullable|date',
            'hora' => 'nullable|date_format:H:i',
            'lugar' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'carretera' => 'nullable|string|max:255',
            'tramo' => 'nullable|string|max:255',
            'kilometro' => 'nullable|string|max:50',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'coordenadas_texto' => 'nullable|string',
            'fuente_ubicacion' => 'nullable|string|max:50',
            'nota_geo' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'personas_alcanzadas' => 'nullable|integer|min:0',
            'personas_participantes' => 'nullable|integer|min:0',
            'personas_detenidas' => 'nullable|integer|min:0',
            'elementos_participantes_texto' => 'nullable|string',
            'patrullas_participantes_texto' => 'nullable|string',
            'destacamento_id' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'fotos' => 'nullable|array|min:1',
            'fotos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehiculos' => 'nullable|array',
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
            'vehiculos.*.grua' => 'nullable|string|max:255',
            'vehiculos.*.corralon' => 'nullable|string|max:255',
            'vehiculos.*.aseguradora' => 'nullable|string|max:100',
            'vehiculos.*.antecedente_vehiculo' => 'nullable|boolean',
            'vehiculos.*.monto_danos' => 'nullable|numeric|min:0',
            'vehiculos.*.partes_danadas' => 'nullable|string',
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

        $user = Auth::user();
        $tz = config('app.timezone', 'America/Mexico_City');
        $ahora = now($tz);

        $fecha = !empty($validated['fecha']) ? Carbon::parse($validated['fecha'], $tz)->toDateString() : $ahora->toDateString();
        $hora = !empty($validated['hora']) ? $validated['hora'] : $ahora->format('H:i');

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

        $nombre = mb_strtoupper((string) ($user->name ?? ''), 'UTF-8');
        $cantidad = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La subcategoría no pertenece a la categoría seleccionada.',
                    'errors' => [
                        'actividad_subcategoria_id' => ['La subcategoría no pertenece a la categoría seleccionada.'],
                    ],
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $validated, $nombre, $cantidad, $user, $unidadOrg, $delegacionId, $fecha, $hora) {
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

            if ($archivos->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Debes subir al menos una foto.',
                    'errors' => [
                        'foto' => ['Debes subir al menos una foto.'],
                    ],
                ], 422);
            }

            $hashes = [];
            foreach ($archivos as $file) {
                $hash = hash_file('sha256', $file->getRealPath());

                if (in_array($hash, $hashes, true)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Estás intentando subir fotos duplicadas en la misma solicitud.',
                        'errors' => [
                            'fotos' => ['Estás intentando subir fotos duplicadas en la misma solicitud.'],
                        ],
                    ], 422);
                }

                $hashes[] = $hash;

                $yaExiste = Actividad::query()->where('foto_hash', $hash)->exists();

                if ($yaExiste) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Una de las fotos ya fue subida anteriormente.',
                        'errors' => [
                            'fotos' => ['Una de las fotos ya fue subida anteriormente.'],
                        ],
                    ], 422);
                }
            }

            $actividad = Actividad::create([
                'client_uuid' => !empty($validated['client_uuid']) ? $validated['client_uuid'] : (string) Str::uuid(),
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
                'coordenadas_texto' => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion' => $validated['fuente_ubicacion'] ?? null,
                'nota_geo' => $validated['nota_geo'] ?? null,
                'motivo' => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa' => $validated['narrativa'] ?? null,
                'acciones_realizadas' => $validated['acciones_realizadas'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'personas_alcanzadas' => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes' => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas' => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
            ]);

            $ordenBase = 0;

            foreach ($archivos as $index => $file) {
                $fotoHash = hash_file('sha256', $file->getRealPath());
                $fotoNombreOriginal = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                $fotoPath = $file->storeAs('actividades', $filename, 'public');

                $actividad->fotos()->create([
                    'foto_path' => $fotoPath,
                    'foto_nombre_original' => $fotoNombreOriginal,
                    'foto_hash' => $fotoHash,
                    'orden' => $ordenBase + $index,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            $fotoPrincipal = $actividad->fotos()->orderBy('orden')->orderBy('id')->first();

            if ($fotoPrincipal) {
                $actividad->update([
                    'foto_path' => $fotoPrincipal->foto_path,
                    'foto_nombre_original' => $fotoPrincipal->foto_nombre_original,
                    'foto_hash' => $fotoPrincipal->foto_hash,
                ]);
            }

            foreach (($validated['vehiculos'] ?? []) as $vehiculoData) {
                $this->crearVehiculoParaActividad($actividad, $vehiculoData);
            }

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
            'vehiculos'
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

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado para modificar esta actividad'
            ], 403);
        }

        $validated = $request->validate([
            'actividad_categoria_id' => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'fecha' => 'nullable|date',
            'hora' => 'nullable|date_format:H:i',
            'lugar' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'carretera' => 'nullable|string|max:255',
            'tramo' => 'nullable|string|max:255',
            'kilometro' => 'nullable|string|max:50',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'coordenadas_texto' => 'nullable|string',
            'fuente_ubicacion' => 'nullable|string|max:50',
            'nota_geo' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'personas_alcanzadas' => 'nullable|integer|min:0',
            'personas_participantes' => 'nullable|integer|min:0',
            'personas_detenidas' => 'nullable|integer|min:0',
            'elementos_participantes_texto' => 'nullable|string',
            'patrullas_participantes_texto' => 'nullable|string',
            'destacamento_id' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = Auth::user();
        $tz = config('app.timezone', 'America/Mexico_City');

        $nombre = mb_strtoupper((string) ($user->name ?? ''), 'UTF-8');
        $cantidad = 1;

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
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La subcategoría no pertenece a la categoría seleccionada.',
                    'errors' => [
                        'actividad_subcategoria_id' => ['La subcategoría no pertenece a la categoría seleccionada.'],
                    ],
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $validated, $actividad, $nombre, $cantidad, $user, $tz) {
            $fotoPath = $actividad->foto_path;
            $fotoNombreOriginal = $actividad->foto_nombre_original;
            $fotoHash = $actividad->foto_hash;

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $nuevoHash = hash_file('sha256', $file->getRealPath());

                $yaExiste = Actividad::query()
                    ->where('foto_hash', $nuevoHash)
                    ->where('id', '!=', $actividad->id)
                    ->exists();

                if ($yaExiste) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                        'errors' => [
                            'foto' => ['Esta foto ya fue subida anteriormente (mismo contenido).'],
                        ],
                    ], 422);
                }

                $fotoAnteriorPath = $fotoPath;

                $fotoNombreOriginal = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

                $fotoPath = $file->storeAs('actividades', $filename, 'public');
                $fotoHash = $nuevoHash;

                if (!empty($fotoAnteriorPath) && Storage::disk('public')->exists($fotoAnteriorPath)) {
                    Storage::disk('public')->delete($fotoAnteriorPath);
                }
            }

            $fechaRespaldo = $actividad->created_at
                ? Carbon::parse($actividad->created_at, $tz)->toDateString()
                : now($tz)->toDateString();

            $horaRespaldo = $actividad->created_at
                ? Carbon::parse($actividad->created_at, $tz)->format('H:i')
                : now($tz)->format('H:i');

            $actividad->update([
                'actividad_categoria_id' => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre' => $nombre,
                'cantidad' => $cantidad,
                'foto_path' => $fotoPath,
                'foto_nombre_original' => $fotoNombreOriginal,
                'foto_hash' => $fotoHash,
                'updated_by' => $user->id,
                'fecha' => $validated['fecha'] ?? $actividad->fecha ?? $fechaRespaldo,
                'hora' => $validated['hora'] ?? $actividad->hora ?? $horaRespaldo,
                'destacamento_id' => $validated['destacamento_id'] ?? $actividad->destacamento_id,
                'lugar' => array_key_exists('lugar', $validated) ? $this->toUpperOrNull($validated['lugar']) : $actividad->lugar,
                'municipio' => array_key_exists('municipio', $validated) ? $this->toUpperOrNull($validated['municipio']) : $actividad->municipio,
                'carretera' => array_key_exists('carretera', $validated) ? $this->toUpperOrNull($validated['carretera']) : $actividad->carretera,
                'tramo' => array_key_exists('tramo', $validated) ? $this->toUpperOrNull($validated['tramo']) : $actividad->tramo,
                'kilometro' => array_key_exists('kilometro', $validated) ? $this->toUpperOrNull($validated['kilometro']) : $actividad->kilometro,
                'lat' => array_key_exists('lat', $validated) ? $validated['lat'] : $actividad->lat,
                'lng' => array_key_exists('lng', $validated) ? $validated['lng'] : $actividad->lng,
                'coordenadas_texto' => array_key_exists('coordenadas_texto', $validated) ? $validated['coordenadas_texto'] : $actividad->coordenadas_texto,
                'fuente_ubicacion' => array_key_exists('fuente_ubicacion', $validated) ? $validated['fuente_ubicacion'] : $actividad->fuente_ubicacion,
                'nota_geo' => array_key_exists('nota_geo', $validated) ? $validated['nota_geo'] : $actividad->nota_geo,
                'motivo' => array_key_exists('motivo', $validated) ? $this->toUpperOrNull($validated['motivo']) : $actividad->motivo,
                'narrativa' => array_key_exists('narrativa', $validated) ? $validated['narrativa'] : $actividad->narrativa,
                'acciones_realizadas' => array_key_exists('acciones_realizadas', $validated) ? $validated['acciones_realizadas'] : $actividad->acciones_realizadas,
                'observaciones' => array_key_exists('observaciones', $validated) ? $validated['observaciones'] : $actividad->observaciones,
                'personas_alcanzadas' => array_key_exists('personas_alcanzadas', $validated) ? $validated['personas_alcanzadas'] : $actividad->personas_alcanzadas,
                'personas_participantes' => array_key_exists('personas_participantes', $validated) ? $validated['personas_participantes'] : $actividad->personas_participantes,
                'personas_detenidas' => array_key_exists('personas_detenidas', $validated) ? $validated['personas_detenidas'] : $actividad->personas_detenidas,
                'elementos_participantes_texto' => array_key_exists('elementos_participantes_texto', $validated) ? $validated['elementos_participantes_texto'] : $actividad->elementos_participantes_texto,
                'patrullas_participantes_texto' => array_key_exists('patrullas_participantes_texto', $validated) ? $validated['patrullas_participantes_texto'] : $actividad->patrullas_participantes_texto,
            ]);

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

        return DB::transaction(function () use ($actividad) {

            if (!empty($actividad->foto_path) && Storage::disk('public')->exists($actividad->foto_path)) {
                Storage::disk('public')->delete($actividad->foto_path);
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
        $items = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $items = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $categoria->id)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
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

        if ($fecha) $texto .= "FECHA {$fecha}\n";
        if ($hora) $texto .= "HORA {$hora}\n\n";

        if ($actividad->motivo) {
            $texto .= "ASUNTO: " . mb_strtoupper($actividad->motivo, 'UTF-8') . "\n\n";
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
            ->map(fn($f)=>asset('storage/'.$f->foto_path))
            ->values();

        if ($fotos->isEmpty() && $actividad->foto_path) {
            $fotos = collect([asset('storage/'.$actividad->foto_path)]);
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

    private function applyActividadesVisibilityScope($query, $usuario): void
    {
        $unidadId = (int)($usuario->unidad_id ?? 0);

        if ($usuario->hasRole('Superadmin') || $usuario->hasRole('Coordinador') || $unidadId === 3) {
            return;
        }

        if ($unidadId === 2) {
            $query->where('delegacion_id', $usuario->delegacion_id);
            return;
        }

        if ($unidadId > 0) {
            $query->where('unidad_org_id', $unidadId);
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

    private function withFotoUrls(Actividad $actividad): array
    {
        $data = $actividad->toArray();

        $data['foto_url'] = $this->publicStoragePath($actividad->foto_path);

        if (!empty($data['fotos']) && is_array($data['fotos'])) {
            $data['fotos'] = array_map(function ($foto) {
                $foto['foto_url'] = $this->publicStoragePath($foto['foto_path'] ?? null);
                return $foto;
            }, $data['fotos']);
        }

        return $data;
    }

    private function publicStoragePath(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        return asset('storage/' . ltrim($storedPath, '/'));
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

        $validated = $this->validarVehiculoRequest($request);

        return DB::transaction(function () use ($actividad, $validated) {
            $vehiculo = $this->crearVehiculoParaActividad($actividad, $validated);

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

        $actividad->vehiculos()->detach($vehiculoId);

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

    private function crearVehiculoParaActividad(Actividad $actividad, array $data): \App\Models\Vehiculo
    {
        $data = $this->normalizarVehiculoData($data);

        $vehiculo = \App\Models\Vehiculo::create([
            'client_uuid' => (string) Str::uuid(),
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
            'grua' => $this->toUpperOrNull($data['grua'] ?? null),
            'corralon' => $this->toUpperOrNull($data['corralon'] ?? null),
            'aseguradora' => $this->toUpperOrNull($data['aseguradora'] ?? null),
            'fotos' => null,
            'antecedente_vehiculo' => (int) ($data['antecedente_vehiculo'] ?? 0),
            'monto_danos' => $data['monto_danos'] ?? 0,
            'partes_danadas' => $this->toUpperOrNull($data['partes_danadas'] ?? null),
        ]);

        $actividad->vehiculos()->syncWithoutDetaching([$vehiculo->id]);

        return $vehiculo;
    }
}
