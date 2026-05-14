<?php

namespace App\Http\Controllers;

use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Models\FomentoCulturaVialPrograma;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActividadCategoriaController extends Controller
{
    public function index()
    {
        $categorias = ActividadCategoria::with(['subcategorias' => function ($query) {
            $query->orderBy('nombre');
        }])
            ->orderBy('nombre')
            ->get();

        $fomentoSubcategorias = ActividadSubcategoria::query()
            ->whereHas('categoria', function ($query) {
                $query->where('slug', 'capacitaciones');
            })
            ->whereIn('slug', [
                'taller-educacion-seguridad-vial',
                'campana-educacion-seguridad-vial',
                'capacitaciones-educacion-seguridad-vial',
                'modulos-educacion-seguridad-vial',
            ])
            ->orderBy('nombre')
            ->get();

        $fomentoProgramasPorSubcategoria = FomentoCulturaVialPrograma::query()
            ->with('subcategoria')
            ->whereIn('actividad_subcategoria_id', $fomentoSubcategorias->pluck('id'))
            ->orderBy('actividad_subcategoria_id')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->groupBy('actividad_subcategoria_id');

        return view('admin.settings.catalogos_actividades.index', compact(
            'categorias',
            'fomentoSubcategorias',
            'fomentoProgramasPorSubcategoria'
        ));
    }

    public function create()
    {
        return view('admin.settings.catalogos_actividades.categorias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:120|unique:actividad_categorias,nombre',
            'activo' => 'nullable|boolean',
        ]);

        ActividadCategoria::create([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(ActividadCategoria $actividadCategoria)
    {
        return view('admin.settings.catalogos_actividades.categorias.edit', compact('actividadCategoria'));
    }

    public function update(Request $request, ActividadCategoria $actividadCategoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:120|unique:actividad_categorias,nombre,' . $actividadCategoria->id,
            'activo' => 'nullable|boolean',
        ]);

        $actividadCategoria->update([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(ActividadCategoria $actividadCategoria)
    {
        $actividadCategoria->delete();

        return redirect()->route('catalogos_actividades.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
