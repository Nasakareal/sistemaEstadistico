<?php

namespace App\Http\Controllers;

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
        $this->middleware(['auth', 'can:ver actividades']);
    }

    public function index(Request $request)
    {
        $query = Actividad::query()
            ->with(['categoria', 'subcategoria'])
            ->orderByDesc('created_at');

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

        $actividades = $query->get();

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.index', compact('actividades', 'categorias'));
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
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'nullable|exists:actividad_subcategorias,id',
            'nombre'                   => 'required|string|max:200',
            'cantidad'                 => 'required|integer|min:0',
            'foto'                     => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validated['nombre'] = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');

        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated) {

            $file = $request->file('foto');

            $fotoHash = hash_file('sha256', $file->getRealPath());

            $yaExiste = Actividad::query()->where('foto_hash', $fotoHash)->exists();
            if ($yaExiste) {
                return back()->withErrors([
                    'foto' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                ])->withInput();
            }

            $fotoNombreOriginal = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

            $fotoPath = $file->storeAs('actividades', $filename, 'public');

            Actividad::create([
                'actividad_categoria_id'    => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                    => $validated['nombre'],
                'cantidad'                  => 1,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('actividades.index')->with('success', 'Actividad creada correctamente.');
        });
    }

    public function show(Actividad $actividad)
    {
        $actividad->load(['categoria', 'subcategoria']);
        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $subcategorias = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $actividad->actividad_categoria_id)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.edit', compact('actividad', 'categorias', 'subcategorias'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $validated = $request->validate([
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'nullable|exists:actividad_subcategorias,id',
            'nombre'                   => 'required|string|max:200',
            'cantidad'                 => 'required|integer|min:0',
            'foto'                     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validated['nombre'] = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');

        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated, $actividad) {

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
                    return back()->withErrors([
                        'foto' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                    ])->withInput();
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
                'nombre'                    => $validated['nombre'],
                'cantidad'                  => 1,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('actividades.index')->with('success', 'Actividad actualizada correctamente.');
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

            return back()->with('success', 'Actividad eliminada correctamente.');
        });
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $items = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $categoria->id)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($items);
    }
}
