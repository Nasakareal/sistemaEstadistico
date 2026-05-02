<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Models\Delegacion;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActividadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:ver actividades']);
    }

    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $query = $this->buildQuery($request, $inicioDia, $finDia);

        $actividades = $query->get();

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.index', compact('actividades', 'categorias', 'fechaSeleccionada'));
    }

    public function informeDiario(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        return $this->informeFecha($fechaSeleccionada, $request);
    }

    public function informeFecha($fecha, Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $tz = 'America/Mexico_City';

        try {
            $fechaSeleccionada = Carbon::createFromFormat('Y-m-d', (string) $fecha, $tz)->toDateString();
        } catch (\Throwable $e) {
            abort(404);
        }

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $actividades = $this->buildQuery($request, $inicioDia, $finDia)->get();

        $actividades->transform(function ($a) {
            $rel = $this->getOrCreatePdfImage($a->foto_path ?: $a->foto_thumbnail_path);
            $a->foto_pdf_path = $rel;
            $a->foto_pdf_abs = $rel ? public_path('storage/' . ltrim($rel, '/')) : null;
            return $a;
        });

        $pdfFacade = null;

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\Pdf::class;
        } elseif (class_exists(\Barryvdh\DomPDF\Facade\PDF::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\PDF::class;
        }

        if (!$pdfFacade) {
            return back()->with('error', 'No está disponible el generador de PDF en el sistema.');
        }

        $nombreArchivo = 'INFORME_ACTIVIDADES_' . Carbon::parse($fechaSeleccionada, $tz)->format('d-m-Y') . '.pdf';

        $pdf = $pdfFacade::loadView('actividades.informe', [
            'actividades' => $actividades,
            'fechaSeleccionada' => $fechaSeleccionada,
            'tz' => $tz,
        ])->setPaper('letter', 'portrait')
          ->setOptions([
              'dpi' => 96,
              'defaultFont' => 'DejaVu Sans',
              'isRemoteEnabled' => true,
              'chroot' => base_path(),
          ]);

        return $pdf->download($nombreArchivo);
    }

    public function create()
    {
        $this->authorize('crear actividades');

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $this->authorize('crear actividades');

        $validated = $request->validate([
            'actividad_categoria_id'         => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id'      => 'nullable|exists:actividad_subcategorias,id',
            'fecha'                          => 'required|date',
            'hora'                           => 'nullable|date_format:H:i',
            'lugar'                          => 'nullable|string|max:255',
            'municipio'                      => 'nullable|string|max:255',
            'carretera'                      => 'nullable|string|max:255',
            'tramo'                          => 'nullable|string|max:255',
            'kilometro'                      => 'nullable|string|max:50',
            'lat'                            => 'nullable|numeric|between:-90,90',
            'lng'                            => 'nullable|numeric|between:-180,180',
            'coordenadas_texto'              => 'nullable|string',
            'fuente_ubicacion'               => 'nullable|string|max:50',
            'nota_geo'                       => 'nullable|string|max:255',
            'motivo'                         => 'nullable|string',
            'narrativa'                      => 'nullable|string',
            'acciones_realizadas'            => 'nullable|string',
            'observaciones'                  => 'nullable|string',
            'personas_alcanzadas'            => 'nullable|integer|min:0',
            'personas_participantes'         => 'nullable|integer|min:0',
            'personas_detenidas'             => 'nullable|integer|min:0',
            'elementos_participantes_texto'  => 'nullable|string',
            'patrullas_participantes_texto'  => 'nullable|string',
            'destacamento_id'                => 'nullable|integer',
            'fotos'                          => 'required|array|min:1',
            'fotos.*'                        => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehiculos'                      => 'nullable|array',
            'vehiculos.*.marca'              => 'required|string|max:50',
            'vehiculos.*.modelo'             => 'nullable|string|max:10',
            'vehiculos.*.tipo'               => 'required|string|max:50',
            'vehiculos.*.linea'              => 'required|string|max:50',
            'vehiculos.*.color'              => 'required|string|max:30',
            'vehiculos.*.placas'             => 'nullable|string|max:15',
            'vehiculos.*.estado_placas'      => 'nullable|string|max:15',
            'vehiculos.*.serie'              => 'nullable|string|max:17',
            'vehiculos.*.capacidad_personas' => 'required|integer|min:0',
            'vehiculos.*.tipo_servicio'      => 'required|string|max:50',
            'vehiculos.*.tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'vehiculos.*.grua'               => 'nullable|string|max:255',
            'vehiculos.*.corralon'           => 'nullable|string|max:255',
            'vehiculos.*.aseguradora'        => 'nullable|string|max:100',
            'vehiculos.*.antecedente_vehiculo' => 'nullable|boolean',
            'vehiculos.*.monto_danos'        => 'nullable|numeric|min:0',
            'vehiculos.*.partes_danadas'     => 'nullable|string',
        ]);

        $user = Auth::user();

        $validated['nombre'] = mb_strtoupper((string) ($user->name ?? ''), 'UTF-8');
        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = $this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $user
            );

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada o no está permitida para tu unidad.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated, $user) {
            $actividad = Actividad::create([
                'client_uuid'                   => (string) Str::uuid(),
                'sync_status'                   => 'local',
                'sync_error'                    => null,
                'synced_at'                     => null,
                'actividad_categoria_id'        => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id'     => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                        => $validated['nombre'],
                'cantidad'                      => 1,
                'foto_path'                     => null,
                'foto_nombre_original'          => null,
                'foto_hash'                     => null,
                'created_by'                    => $user->id,
                'updated_by'                    => $user->id,
                'unidad_org_id'                 => $user->unidad_id,
                'delegacion_id'                 => $user->delegacion_id,
                'destacamento_id'               => $validated['destacamento_id'] ?? null,
                'fecha'                         => $validated['fecha'],
                'hora'                          => $validated['hora'] ?? null,
                'lugar'                         => $this->toUpperOrNull($validated['lugar'] ?? null),
                'municipio'                     => $this->toUpperOrNull($validated['municipio'] ?? null),
                'carretera'                     => $this->toUpperOrNull($validated['carretera'] ?? null),
                'tramo'                         => $this->toUpperOrNull($validated['tramo'] ?? null),
                'kilometro'                     => $this->toUpperOrNull($validated['kilometro'] ?? null),
                'lat'                           => $validated['lat'] ?? null,
                'lng'                           => $validated['lng'] ?? null,
                'coordenadas_texto'             => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion'              => $validated['fuente_ubicacion'] ?? null,
                'nota_geo'                      => $validated['nota_geo'] ?? null,
                'motivo'                        => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa'                     => $validated['narrativa'] ?? null,
                'acciones_realizadas'           => $validated['acciones_realizadas'] ?? null,
                'observaciones'                 => $validated['observaciones'] ?? null,
                'personas_alcanzadas'           => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes'        => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas'            => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
                'estado_revision'               => 'pendiente',
                'revisado_por'                  => null,
                'revisado_at'                   => null,
                'observacion_revision'          => null,
            ]);

            $ordenBase = 0;

            foreach ($request->file('fotos', []) as $index => $file) {
                $fotoHash = hash_file('sha256', $file->getRealPath());

                $yaExiste = $actividad->fotos()
                    ->where('foto_hash', $fotoHash)
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                $fotoPath = $file->storeAs('actividades', $filename, 'public');

                $actividad->fotos()->create([
                    'foto_path'            => $fotoPath,
                    'foto_nombre_original' => $file->getClientOriginalName(),
                    'foto_hash'            => $fotoHash,
                    'orden'                => $ordenBase + $index,
                    'created_by'           => $user->id,
                    'updated_by'           => $user->id,
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
                    'foto_path'            => $fotoPrincipal->foto_path,
                    'foto_thumbnail_path'  => null,
                    'foto_archivo_zip_path'=> null,
                    'foto_archivada_at'    => null,
                    'foto_eliminada_at'    => null,
                    'foto_nombre_original' => $fotoPrincipal->foto_nombre_original,
                    'foto_hash'            => $fotoPrincipal->foto_hash,
                ]);
            }

            foreach (($validated['vehiculos'] ?? []) as $vehiculoData) {
                $this->crearVehiculoParaActividad($actividad, $vehiculoData);
            }

            return redirect()->route('actividades.index')->with('success', 'Actividad creada correctamente.');
        });
    }

    public function show(Actividad $actividad)
    {
        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'creador',
            'actualizador',
            'revisor',
            'vehiculos',
        ]);

        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $subcategorias = $this->obtenerSubcategoriasDisponibles(
            (int) $actividad->actividad_categoria_id,
            $usuario
        );

        $actividad->load('vehiculos');

        return view('actividades.edit', compact('actividad', 'categorias', 'subcategorias'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $validated = $request->validate([
            'actividad_categoria_id'         => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id'      => 'nullable|exists:actividad_subcategorias,id',
            'fecha'                          => 'required|date',
            'hora'                           => 'nullable|date_format:H:i',
            'lugar'                          => 'nullable|string|max:255',
            'municipio'                      => 'nullable|string|max:255',
            'carretera'                      => 'nullable|string|max:255',
            'tramo'                          => 'nullable|string|max:255',
            'kilometro'                      => 'nullable|string|max:50',
            'lat'                            => 'nullable|numeric|between:-90,90',
            'lng'                            => 'nullable|numeric|between:-180,180',
            'coordenadas_texto'              => 'nullable|string',
            'fuente_ubicacion'               => 'nullable|string|max:50',
            'nota_geo'                       => 'nullable|string|max:255',
            'motivo'                         => 'nullable|string',
            'narrativa'                      => 'nullable|string',
            'acciones_realizadas'            => 'nullable|string',
            'observaciones'                  => 'nullable|string',
            'personas_alcanzadas'            => 'nullable|integer|min:0',
            'personas_participantes'         => 'nullable|integer|min:0',
            'personas_detenidas'             => 'nullable|integer|min:0',
            'elementos_participantes_texto'  => 'nullable|string',
            'patrullas_participantes_texto'  => 'nullable|string',
            'destacamento_id'                => 'nullable|integer',
            'fotos'                          => 'nullable|array|min:1',
            'fotos.*'                        => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validated['nombre'] = mb_strtoupper((string) ($usuario->name ?? ''), 'UTF-8');
        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = $this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $usuario
            );

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada o no está permitida para tu unidad.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated, $actividad, $usuario) {
            $actividad->update([
                'sync_status'                   => $actividad->sync_status ?: 'local',
                'actividad_categoria_id'        => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id'     => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                        => $validated['nombre'],
                'cantidad'                      => 1,
                'updated_by'                    => $usuario->id,
                'unidad_org_id'                 => $actividad->unidad_org_id ?? $usuario->unidad_id,
                'delegacion_id'                 => $actividad->delegacion_id ?? $usuario->delegacion_id,
                'destacamento_id'               => $validated['destacamento_id'] ?? null,
                'fecha'                         => $validated['fecha'],
                'hora'                          => $validated['hora'] ?? null,
                'lugar'                         => $this->toUpperOrNull($validated['lugar'] ?? null),
                'municipio'                     => $this->toUpperOrNull($validated['municipio'] ?? null),
                'carretera'                     => $this->toUpperOrNull($validated['carretera'] ?? null),
                'tramo'                         => $this->toUpperOrNull($validated['tramo'] ?? null),
                'kilometro'                     => $this->toUpperOrNull($validated['kilometro'] ?? null),
                'lat'                           => $validated['lat'] ?? null,
                'lng'                           => $validated['lng'] ?? null,
                'coordenadas_texto'             => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion'              => $validated['fuente_ubicacion'] ?? null,
                'nota_geo'                      => $validated['nota_geo'] ?? null,
                'motivo'                        => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa'                     => $validated['narrativa'] ?? null,
                'acciones_realizadas'           => $validated['acciones_realizadas'] ?? null,
                'observaciones'                 => $validated['observaciones'] ?? null,
                'personas_alcanzadas'           => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes'        => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas'            => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
            ]);

            if ($request->hasFile('fotos')) {
                $ordenBase = (int) $actividad->fotos()->max('orden');
                $ordenBase = $ordenBase >= 0 ? $ordenBase + 1 : 0;

                foreach ($request->file('fotos', []) as $index => $file) {
                    $fotoHash = hash_file('sha256', $file->getRealPath());

                    $yaExiste = $actividad->fotos()
                        ->where('foto_hash', $fotoHash)
                        ->exists();

                    if ($yaExiste) {
                        continue;
                    }

                    $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                    $fotoPath = $file->storeAs('actividades', $filename, 'public');

                    $actividad->fotos()->create([
                        'foto_path'            => $fotoPath,
                        'foto_nombre_original' => $file->getClientOriginalName(),
                        'foto_hash'            => $fotoHash,
                        'orden'                => $ordenBase + $index,
                        'created_by'           => $usuario->id,
                        'updated_by'           => $usuario->id,
                    ]);
                }
            }

            $fotoPrincipal = $actividad->fotosTodas()
                ->whereNull('foto_archivada_at')
                ->whereNull('foto_eliminada_at')
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

            $fotoArchivada = $fotoPrincipal ? null : $actividad->fotos()->orderBy('orden')->orderBy('id')->first();

            $actividad->update([
                'foto_path'             => optional($fotoPrincipal)->foto_path,
                'foto_thumbnail_path'   => $fotoPrincipal ? null : optional($fotoArchivada)->foto_thumbnail_path,
                'foto_archivo_zip_path' => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivo_zip_path,
                'foto_archivada_at'     => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivada_at,
                'foto_eliminada_at'     => null,
                'foto_nombre_original'  => optional($fotoPrincipal ?: $fotoArchivada)->foto_nombre_original,
                'foto_hash'             => optional($fotoPrincipal ?: $fotoArchivada)->foto_hash,
            ]);

            return redirect()->route('actividades.index')->with('success', 'Actividad actualizada correctamente.');
        });
    }

    public function destroy(Actividad $actividad)
    {
        $this->authorize('eliminar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        return DB::transaction(function () use ($actividad) {
            $actividad->load('fotos');

            foreach ($actividad->fotos as $foto) {
                if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                    Storage::disk('public')->delete($foto->foto_path);
                }

                if (!empty($foto->foto_thumbnail_path) && Storage::disk('public')->exists($foto->foto_thumbnail_path)) {
                    Storage::disk('public')->delete($foto->foto_thumbnail_path);
                }

                $this->deletePdfCacheForOriginal($foto->foto_path);
            }

            if (!empty($actividad->foto_path) && Storage::disk('public')->exists($actividad->foto_path)) {
                Storage::disk('public')->delete($actividad->foto_path);
            }

            if (!empty($actividad->foto_thumbnail_path) && Storage::disk('public')->exists($actividad->foto_thumbnail_path)) {
                Storage::disk('public')->delete($actividad->foto_thumbnail_path);
            }

            $this->deletePdfCacheForOriginal($actividad->foto_path);

            $actividad->delete();

            return back()->with('success', 'Actividad eliminada correctamente.');
        });
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $usuario = Auth::user();

        $items = $this->obtenerSubcategoriasDisponibles((int) $categoria->id, $usuario)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                ];
            })
            ->values();

        return response()->json($items);
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

        return $query->orderBy('nombre')->get();
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

        return $query->exists();
    }

    private function buildQuery(Request $request, Carbon $inicioDia, Carbon $finDia)
    {
        $query = Actividad::query()
            ->with(['categoria', 'subcategoria', 'unidad', 'delegacion', 'destacamento'])
            ->whereBetween('fecha', [$inicioDia->toDateString(), $finDia->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id');

        $usuario = Auth::user();

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

        return $query;
    }

    private function applyActividadesVisibilityScope($query, $usuario): void
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Coordinador')
            || $unidadId === 3
        ) {
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

            $esRegional = Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($this->puedeVerDelegacionesHijas($usuario)) {
                if ($esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $query->whereIn('delegacion_id', $ids);
                } else {
                    $query->where('delegacion_id', $delegacionId);
                }
            } else {
                $query->where('delegacion_id', $delegacionId);
            }

            return;
        }

        if ($unidadId > 0) {
            $this->scopeActividadesUnidad($query, $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function getOrCreatePdfImage(?string $fotoPath): ?string
    {
        if (!$fotoPath) {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return null;
        }

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));

        if (!is_file($absOriginal)) {
            return null;
        }

        $hash = @hash_file('sha1', $absOriginal);

        if (!$hash) {
            return $fotoPath;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            return $cacheRel;
        }

        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $hash . '.jpg';

        $ok = $this->resizeToJpeg($absOriginal, $tmpOut, 1280, 75);

        if (!$ok || !is_file($tmpOut)) {
            return $fotoPath;
        }

        $disk->put($cacheRel, file_get_contents($tmpOut));
        @unlink($tmpOut);

        return $cacheRel;
    }

    private function resizeToJpeg(string $src, string $dst, int $maxW, int $quality): bool
    {
        $info = @getimagesize($src);

        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        $mime = (string) $info['mime'];

        if ($w <= 0 || $h <= 0) {
            return false;
        }

        $create = null;

        if ($mime === 'image/jpeg') {
            $create = 'imagecreatefromjpeg';
        }

        if ($mime === 'image/png') {
            $create = 'imagecreatefrompng';
        }

        if ($mime === 'image/webp') {
            $create = 'imagecreatefromwebp';
        }

        if (!$create || !function_exists($create)) {
            return false;
        }

        $srcIm = @$create($src);

        if (!$srcIm) {
            return false;
        }

        $newW = $w > $maxW ? $maxW : $w;
        $newH = (int) round($h * ($newW / $w));

        $dstIm = imagecreatetruecolor($newW, $newH);

        if (!$dstIm) {
            imagedestroy($srcIm);
            return false;
        }

        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $saved = imagejpeg($dstIm, $dst, $quality);

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        return (bool) $saved;
    }

    private function deletePdfCacheForOriginal(?string $fotoPath): void
    {
        if (!$fotoPath) {
            return;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return;
        }

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));

        if (!is_file($absOriginal)) {
            return;
        }

        $hash = @hash_file('sha1', $absOriginal);

        if (!$hash) {
            return;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            $disk->delete($cacheRel);
        }
    }

    private function toUpperOrNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return null;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    private function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasRole('Delegado');
    }

    private function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
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

    public function compartir(Actividad $actividad)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->can('ver actividades')) {
            abort(403);
        }

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'fotos',
        ]);

        $fecha = $actividad->fecha ? \Carbon\Carbon::parse($actividad->fecha)->format('d/m/Y') : '';
        $hora = $actividad->hora ? substr((string) $actividad->hora, 0, 5) : '';

        $nombreUnidad = trim((string) optional($actividad->unidad)->nombre);
        $nombreDelegacion = trim((string) optional($actividad->delegacion)->nombre);
        $nombreDestacamento = trim((string) optional($actividad->destacamento)->nombre);

        $texto = "GUARDIA CIVIL\n\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n\n";

        if ($nombreUnidad !== '') {
            $texto .= $nombreUnidad . "\n\n";
        }

        if ($nombreDelegacion !== '') {
            $texto .= $nombreDelegacion . "\n\n";
        } elseif ($nombreDestacamento !== '') {
            $texto .= $nombreDestacamento . "\n\n";
        }

        if ($fecha) {
            $texto .= "FECHA {$fecha}\n";
        }

        if ($hora) {
            $texto .= "HORA {$hora}\n\n";
        }

        if ($actividad->motivo) {
            $texto .= "ASUNTO: " . mb_strtoupper((string) $actividad->motivo, 'UTF-8') . "\n\n";
        }

        if ($actividad->narrativa) {
            $texto .= trim((string) $actividad->narrativa) . "\n\n";
        }

        if ($actividad->acciones_realizadas) {
            $texto .= trim((string) $actividad->acciones_realizadas) . "\n\n";
        }

        if ($actividad->observaciones) {
            $texto .= trim((string) $actividad->observaciones) . "\n\n";
        }

        if ($actividad->personas_alcanzadas !== null || $actividad->personas_participantes !== null || $actividad->personas_detenidas !== null) {
            $texto .= "DATOS GENERALES\n";
            $texto .= "PERSONAS ALCANZADAS: " . (int) ($actividad->personas_alcanzadas ?? 0) . "\n";
            $texto .= "PERSONAS PARTICIPANTES: " . (int) ($actividad->personas_participantes ?? 0) . "\n";
            $texto .= "PERSONAS DETENIDAS: " . (int) ($actividad->personas_detenidas ?? 0) . "\n\n";
        }

        if ($actividad->elementos_participantes_texto) {
            $texto .= "ESTADO DE FUERZA\n";
            $texto .= trim((string) $actividad->elementos_participantes_texto) . "\n\n";
        }

        if ($actividad->patrullas_participantes_texto) {
            $texto .= "CRP\n";
            $texto .= trim((string) $actividad->patrullas_participantes_texto) . "\n\n";
        }

        $fotos = $actividad->fotos
            ->sortBy([['orden', 'asc'], ['id', 'asc']])
            ->map(function ($foto) {
                $path = $foto->foto_thumbnail_path ?: $foto->foto_path;

                return $path ? asset('storage/' . ltrim($path, '/')) : null;
            })
            ->filter()
            ->values();

        $fotoActividad = $actividad->foto_thumbnail_path ?: $actividad->foto_path;

        if ($fotos->isEmpty() && $fotoActividad) {
            $fotos = collect([
                asset('storage/' . ltrim($fotoActividad, '/')),
            ]);
        }

        return response()->json([
            'texto' => trim($texto),
            'fotos' => $fotos,
        ]);
    }

    public function compartirTotalesWhatsapp(Request $request)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->can('ver actividades')) {
            abort(403);
        }

        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        try {
            $fecha = Carbon::createFromFormat('Y-m-d', (string) $fechaSeleccionada, $tz)->toDateString();
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'La fecha proporcionada no es válida.',
            ], 422);
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($unidadId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario no tiene una unidad asignada.',
            ], 422);
        }

        $unidad = Unidad::find($unidadId);

        if (!$unidad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró la unidad del usuario.',
            ], 404);
        }

        $categorias = ActividadCategoria::query()
            ->select(
                'actividad_categorias.id',
                'actividad_categorias.nombre',
                DB::raw('SUM(actividades.cantidad) as total')
            )
            ->join('actividades', 'actividades.actividad_categoria_id', '=', 'actividad_categorias.id')
            ->whereDate('actividades.fecha', $fecha)
            ->where('actividades.unidad_org_id', $unidadId)
            ->groupBy('actividad_categorias.id', 'actividad_categorias.nombre')
            ->orderBy('actividad_categorias.nombre')
            ->get();

        if ($categorias->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay actividades registradas para esa fecha.',
            ], 404);
        }

        $subcategoriasPorCategoria = ActividadSubcategoria::query()
            ->select(
                'actividad_subcategorias.actividad_categoria_id',
                'actividad_subcategorias.nombre'
            )
            ->join('actividades', 'actividades.actividad_subcategoria_id', '=', 'actividad_subcategorias.id')
            ->whereDate('actividades.fecha', $fecha)
            ->where('actividades.unidad_org_id', $unidadId)
            ->whereNotNull('actividades.actividad_subcategoria_id')
            ->groupBy('actividad_subcategorias.actividad_categoria_id', 'actividad_subcategorias.nombre')
            ->orderBy('actividad_subcategorias.nombre')
            ->get()
            ->groupBy('actividad_categoria_id');

        $fechaCarbon = Carbon::parse($fecha, $tz)->locale('es');
        $fechaTexto = mb_strtoupper($fechaCarbon->translatedFormat('l d F Y'), 'UTF-8');

        $nombreUnidad = mb_strtoupper((string) $unidad->nombre, 'UTF-8');

        $texto = "GUARDIA CIVIL\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n";
        $texto .= $nombreUnidad . "\n";
        $texto .= $fechaTexto . "\n";
        $texto .= "ACTIVIDADES RELEVANTES DE LAS 06:00 A LAS 21:00 HORAS\n\n";

        foreach ($categorias as $categoria) {
            $texto .= '- ' . trim($categoria->nombre) . ': ' . str_pad((string) ((int) $categoria->total), 2, '0', STR_PAD_LEFT) . "\n";

            $subcats = $subcategoriasPorCategoria->get($categoria->id, collect());

            foreach ($subcats as $subcat) {
                $texto .= '- ' . trim($subcat->nombre) . "\n";
            }

            $texto .= "\n";
        }

        $subdirector = DB::table('personals')
            ->where('unidad_id', $unidadId)
            ->whereRaw('UPPER(puesto) = ?', ['SUBDIRECTOR'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($subdirector) {
            $nombreSubdirector = trim(
                collect([
                    $subdirector->grado ?? null,
                    $subdirector->nombre ?? null,
                    $subdirector->ap_paterno ?? null,
                    $subdirector->ap_materno ?? null,
                ])->filter()->implode(' ')
            );

            $texto .= "RESPETUOSAMENTE\n";
            $texto .= 'SUBDIRECTOR DE ' . $nombreUnidad . "\n";
            $texto .= mb_strtoupper($nombreSubdirector, 'UTF-8');
        }

        return response()->json([
            'ok' => true,
            'texto' => trim($texto),
            'fotos' => [],
        ]);
    }

    public function storeVehiculo(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $validated = $this->validarVehiculoRequest($request);

        return DB::transaction(function () use ($actividad, $validated) {
            $this->crearVehiculoParaActividad($actividad, $validated);

            return back()->with('success', 'Vehículo agregado correctamente.');
        });
    }

    public function destroyVehiculo(Actividad $actividad, $vehiculoId)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->vehiculos()->detach($vehiculoId);

        return back()->with('success', 'Vehículo desvinculado correctamente.');
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
