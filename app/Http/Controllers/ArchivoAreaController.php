<?php

namespace App\Http\Controllers;

use App\Models\ArchivoArea;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoAreaController extends Controller
{
    public function index()
    {
        $areas = ArchivoArea::withCount('anaqueles')->orderBy('nombre')->paginate(30);
        return view('admin.settings.archivo_siniestros.areas.index', compact('areas'));
    }

    public function create()
    {
        return view('admin.settings.archivo_siniestros.areas.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        ArchivoArea::create($datos);
        return redirect()->route('archivo_siniestros.areas.index')->with('success', 'Área registrada correctamente.');
    }

    public function edit(ArchivoArea $area)
    {
        return view('admin.settings.archivo_siniestros.areas.edit', compact('area'));
    }

    public function update(Request $request, ArchivoArea $area)
    {
        $datos = $request->validate($this->reglas($area));
        if (!(bool) ($datos['activo'] ?? true) && ($area->anaqueles()->exists() || ArchivoLibro::where('area_actual_id', $area->id)->exists())) {
            return back()->withErrors(['activo' => 'No puedes desactivar un área que todavía contiene anaqueles o libros.'])->withInput();
        }
        $area->update($datos);
        return redirect()->route('archivo_siniestros.areas.index')->with('success', 'Área actualizada correctamente.');
    }

    private function reglas(?ArchivoArea $area = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('archivo_areas', 'codigo')->ignore($area?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(['sala', 'mesa', 'otro'])],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'plano_ancho' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_alto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
