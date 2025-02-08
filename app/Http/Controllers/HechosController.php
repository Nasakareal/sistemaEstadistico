<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HechosController extends Controller
{
    public function index()
    {
        $hechos = Hechos::all();

        return view('hechos.index', compact('hechos'));
    }

    public function create()
    {
        return view('hechos.create');
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'folio_c5i' => 'required|string|max:20|unique:hechos,folio_c5i',
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'required|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'required|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
        ]);

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($value);
            }
        }

        $validated['created_by'] = $usuario->id;

        $hecho = Hechos::create($validated);

        return redirect()->route('hechos.index')->with('success', 'Hecho creado exitosamente.');
    }

    public function show(Hechos $hechos)
    {
        $hecho = $hechos->load('vehiculos');
        return view('hechos.show', compact('hecho'));
    }

    public function edit(Hechos $hecho)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $hecho->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        return view('hechos.edit', compact('hecho'));
    }

    public function update(Request $request, Hechos $hecho)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $hecho->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para modificar este hecho.');
        }

        $validated = $request->validate([
            'folio_c5i' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hechos')->ignore($hecho->id),
            ],
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'required|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
        ]);

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($value);
            }
        }

        $validated['updated_by'] = $usuario->id;

        $hecho->update($validated);

        return redirect()->route('hechos.index')->with('success', 'Hecho actualizado exitosamente.');
    }

    public function destroy(Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para eliminar este hecho.');
        }

        $hecho->delete();

        return redirect()->route('hechos.index')->with('success', 'Hecho eliminado exitosamente.');
    }
}
