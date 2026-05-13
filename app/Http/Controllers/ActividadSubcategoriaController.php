<?php

namespace App\Http\Controllers;

use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActividadSubcategoriaController extends Controller
{
    public function create(ActividadCategoria $actividadCategoria)
    {
        return view('admin.settings.catalogos_actividades.subcategorias.create', compact('actividadCategoria'));
    }

    public function store(Request $request, ActividadCategoria $actividadCategoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:180',
            'activo' => 'nullable|boolean',
        ]);

        ActividadSubcategoria::create([
            'actividad_categoria_id' => $actividadCategoria->id,
            'unidad_id' => null,
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Subcategoría creada correctamente.');
    }

    public function edit(ActividadSubcategoria $actividadSubcategoria)
    {
        return view('admin.settings.catalogos_actividades.subcategorias.edit', compact('actividadSubcategoria'));
    }

    public function update(Request $request, ActividadSubcategoria $actividadSubcategoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:180',
            'activo' => 'nullable|boolean',
        ]);

        $actividadSubcategoria->update([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Subcategoría actualizada correctamente.');
    }

    public function destroy(ActividadSubcategoria $actividadSubcategoria)
    {
        $actividadSubcategoria->delete();

        return redirect()->route('catalogos_actividades.index')->with('success', 'Subcategoría eliminada correctamente.');
    }
}
