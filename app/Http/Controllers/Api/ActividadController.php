<?php

namespace App\Http\Controllers\Api;

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
            $end   = now($tz)->endOfDay();
        } else {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Parámetro date inválido. Usa YYYY-MM-DD.',
                    'errors' => ['date' => ['Formato esperado: YYYY-MM-DD']],
                ], 422);
            }

            $start = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
            $end   = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $tz)->endOfDay();
        }

        $query = Actividad::query()
            ->with(['categoria', 'subcategoria'])
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('actividad_categoria_id')) {
            $query->where('actividad_categoria_id', (int) $request->actividad_categoria_id);
        }

        if ($request->filled('actividad_subcategoria_id')) {
            $query->where('actividad_subcategoria_id', (int) $request->actividad_subcategoria_id);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nombre', 'like', "%{$q}%");
        }

        $actividades = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'date' => empty($date) ? now($tz)->toDateString() : $date,
            'per_page' => $perPage,
            'data' => $actividades,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('crear actividades');

        $validated = $request->validate([
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'foto'                      => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $nombre = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');
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

        return DB::transaction(function () use ($request, $validated, $nombre, $cantidad) {

            $file = $request->file('foto');

            $fotoHash = hash_file('sha256', $file->getRealPath());

            $yaExiste = Actividad::query()->where('foto_hash', $fotoHash)->exists();
            if ($yaExiste) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                    'errors' => [
                        'foto' => ['Esta foto ya fue subida anteriormente (mismo contenido).'],
                    ],
                ], 422);
            }

            $fotoNombreOriginal = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

            $fotoPath = $file->storeAs('actividades', $filename, 'public');

            $actividad = Actividad::create([
                'actividad_categoria_id'    => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                    => $nombre,
                'cantidad'                  => $cantidad,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $actividad->load(['categoria', 'subcategoria']);

            return response()->json([
                'ok' => true,
                'message' => 'Actividad creada correctamente.',
                'data' => $actividad,
            ], 201);
        });
    }

    public function show(Actividad $actividad)
    {
        $actividad->load(['categoria', 'subcategoria']);

        return response()->json([
            'ok' => true,
            'data' => $actividad,
        ]);
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $validated = $request->validate([
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'required|exists:actividad_subcategorias,id',
            'foto'                      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $nombre = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');
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

        return DB::transaction(function () use ($request, $validated, $actividad, $nombre, $cantidad) {

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

                if (!empty($fotoPath) && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }

                $fotoNombreOriginal = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

                $fotoPath = $file->storeAs('actividades', $filename, 'public');
                $fotoHash = $nuevoHash;
            }

            $actividad->update([
                'actividad_categoria_id'    => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                    => $nombre,
                'cantidad'                  => $cantidad,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'updated_by' => Auth::id(),
            ]);

            $actividad->load(['categoria', 'subcategoria']);

            return response()->json([
                'ok' => true,
                'message' => 'Actividad actualizada correctamente.',
                'data' => $actividad,
            ]);
        });
    }

    public function destroy(Actividad $actividad)
    {
        $this->authorize('eliminar actividades');

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
}
