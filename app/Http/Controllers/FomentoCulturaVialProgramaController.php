<?php

namespace App\Http\Controllers;

use App\Models\ActividadSubcategoria;
use App\Models\FomentoCulturaVialPrograma;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FomentoCulturaVialProgramaController extends Controller
{
    public function create(ActividadSubcategoria $actividadSubcategoria)
    {
        return view('admin.settings.catalogos_actividades.fomento_programas.create', compact('actividadSubcategoria'));
    }

    public function store(Request $request, ActividadSubcategoria $actividadSubcategoria)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('fomento_cultura_vial_programas', 'nombre')
                    ->where('actividad_subcategoria_id', $actividadSubcategoria->id),
            ],
            'orden' => 'nullable|integer|min:0|max:9999',
            'activo' => 'nullable|boolean',
        ]);

        FomentoCulturaVialPrograma::create([
            'actividad_subcategoria_id' => $actividadSubcategoria->id,
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'orden' => (int) ($validated['orden'] ?? 0),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Programa de Fomento creado correctamente.');
    }

    public function edit(FomentoCulturaVialPrograma $fomentoPrograma)
    {
        $fomentoPrograma->load('subcategoria');

        return view('admin.settings.catalogos_actividades.fomento_programas.edit', compact('fomentoPrograma'));
    }

    public function update(Request $request, FomentoCulturaVialPrograma $fomentoPrograma)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('fomento_cultura_vial_programas', 'nombre')
                    ->where('actividad_subcategoria_id', $fomentoPrograma->actividad_subcategoria_id)
                    ->ignore($fomentoPrograma->id),
            ],
            'orden' => 'nullable|integer|min:0|max:9999',
            'activo' => 'nullable|boolean',
        ]);

        $fomentoPrograma->update([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'orden' => (int) ($validated['orden'] ?? 0),
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        $fomentoPrograma->detalles()->update([
            'programa_nombre' => $fomentoPrograma->nombre,
        ]);

        return redirect()->route('catalogos_actividades.index')->with('success', 'Programa de Fomento actualizado correctamente.');
    }

    public function destroy(FomentoCulturaVialPrograma $fomentoPrograma)
    {
        $fomentoPrograma->delete();

        return redirect()->route('catalogos_actividades.index')->with('success', 'Programa de Fomento eliminado correctamente.');
    }
}
