<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Lesionado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LesionadoController extends Controller
{
    public function index(Hechos $hecho)
    {
        $lesionados = $hecho->lesionados;
        return view('lesionados.index', compact('hecho', 'lesionados'));
    }

    public function create(Hechos $hecho)
    {
        return view('lesionados.create', compact('hecho'));
    }

    public function store(Request $request, Hechos $hecho)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|string|in:Masculino,Femenino,Otro',
            'tipo_lesion' => 'required|string|in:Leve,Moderada,Grave,Fallecido',
            'hospitalizado' => 'required|boolean',
            'hospital' => 'nullable|string|max:255',
            'atencion_en_sitio' => 'required|boolean',
            'ambulancia' => 'nullable|string|max:255',
            'paramedico' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $validated['hospitalizado'] = (bool) $validated['hospitalizado'];
        $validated['atencion_en_sitio'] = (bool) $validated['atencion_en_sitio'];

        DB::transaction(function () use ($hecho, $validated) {
            $hecho->lesionados()->create($validated);
            $hecho->actualizarEstadoCaptura();
        });

        return redirect()->route('lesionados.index', $hecho->id)
            ->with('success', 'Lesionado agregado correctamente.');
    }

    public function edit(Hechos $hecho, Lesionado $lesionado)
    {
        if ((int) $lesionado->hecho_id !== (int) $hecho->id) {
            abort(404, 'El lesionado no pertenece a este hecho.');
        }

        return view('lesionados.edit', compact('hecho', 'lesionado'));
    }

    public function update(Request $request, Hechos $hecho, Lesionado $lesionado)
    {
        if ((int) $lesionado->hecho_id !== (int) $hecho->id) {
            abort(404, 'El lesionado no pertenece a este hecho.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|string|in:Masculino,Femenino,Otro',
            'tipo_lesion' => 'required|string|in:Leve,Moderada,Grave,Fallecido',
            'hospitalizado' => 'required|boolean',
            'hospital' => 'nullable|string|max:255',
            'atencion_en_sitio' => 'required|boolean',
            'ambulancia' => 'nullable|string|max:255',
            'paramedico' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $validated['hospitalizado'] = (bool) $validated['hospitalizado'];
        $validated['atencion_en_sitio'] = (bool) $validated['atencion_en_sitio'];

        DB::transaction(function () use ($lesionado, $hecho, $validated) {
            $lesionado->update($validated);
            $hecho->actualizarEstadoCaptura();
        });

        return redirect()->route('lesionados.index', $hecho->id)
            ->with('success', 'Lesionado actualizado correctamente.');
    }

    public function destroy(Hechos $hecho, Lesionado $lesionado)
    {
        if ((int) $lesionado->hecho_id !== (int) $hecho->id) {
            abort(404, 'El lesionado no pertenece a este hecho.');
        }

        DB::transaction(function () use ($lesionado, $hecho) {
            $lesionado->delete();
            $hecho->actualizarEstadoCaptura();
        });

        return back()->with('success', 'Lesionado eliminado correctamente.');
    }
}
