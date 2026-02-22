<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grua;
use App\Models\Tramo;
use App\Models\GruaGuardiaSct;

class GruaGuardiaSctController extends Controller
{
    public function index()
    {
        $guardias = GruaGuardiaSct::with(['grua', 'tramo'])
            ->orderByDesc('activo')
            ->orderBy('dia_inicio')
            ->orderBy('dia_fin')
            ->get();

        return view('gruas.guardias_sct.index', compact('guardias'));
    }

    public function create()
    {
        $gruas = Grua::orderBy('nombre')->get();

        $tramos = Tramo::where('activo', 1)
            ->orderBy('carretera')
            ->orderBy('nombre')
            ->get();

        return view('gruas.guardias_sct.create', compact('gruas', 'tramos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grua_id'     => 'required|integer|exists:gruas,id',
            'tramo_id'    => 'required|integer|exists:tramos,id',
            'dia_inicio'  => 'required|integer|min:1|max:31',
            'dia_fin'     => 'required|integer|min:1|max:31',
            'activo'      => 'nullable|boolean',
        ]);

        $data = $request->all();

        $data['dia_inicio'] = (int) $data['dia_inicio'];
        $data['dia_fin'] = (int) $data['dia_fin'];

        if ($data['dia_inicio'] > $data['dia_fin']) {
            return back()
                ->withInput()
                ->withErrors(['dia_inicio' => 'El día de inicio no puede ser mayor que el día fin.']);
        }

        $data['activo'] = $request->has('activo') ? 1 : 0;

        $existsOverlap = GruaGuardiaSct::where('activo', 1)
            ->where('tramo_id', $data['tramo_id'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('dia_inicio', [$data['dia_inicio'], $data['dia_fin']])
                  ->orWhereBetween('dia_fin', [$data['dia_inicio'], $data['dia_fin']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('dia_inicio', '<=', $data['dia_inicio'])
                         ->where('dia_fin', '>=', $data['dia_fin']);
                  });
            })
            ->exists();

        if ($existsOverlap) {
            return back()
                ->withInput()
                ->withErrors(['dia_inicio' => 'Ese rango de días se traslapa con otra guardia SCT activa en el mismo tramo.']);
        }

        GruaGuardiaSct::create($data);

        return redirect()->route('grua-guardias-sct.index')
            ->with('success', 'Guardia SCT registrada correctamente.');
    }

    public function show($id)
    {
        $guardia = GruaGuardiaSct::with(['grua', 'tramo'])->findOrFail($id);
        return view('gruas.guardias_sct.show', compact('guardia'));
    }

    public function edit($id)
    {
        $guardia = GruaGuardiaSct::findOrFail($id);

        $gruas = Grua::orderBy('nombre')->get();

        $tramos = Tramo::where('activo', 1)
            ->orderBy('carretera')
            ->orderBy('nombre')
            ->get();

        return view('gruas.guardias_sct.edit', compact('guardia', 'gruas', 'tramos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'grua_id'     => 'required|integer|exists:gruas,id',
            'tramo_id'    => 'required|integer|exists:tramos,id',
            'dia_inicio'  => 'required|integer|min:1|max:31',
            'dia_fin'     => 'required|integer|min:1|max:31',
            'activo'      => 'nullable|boolean',
        ]);

        $guardia = GruaGuardiaSct::findOrFail($id);

        $data = $request->all();

        $data['dia_inicio'] = (int) $data['dia_inicio'];
        $data['dia_fin'] = (int) $data['dia_fin'];

        if ($data['dia_inicio'] > $data['dia_fin']) {
            return back()
                ->withInput()
                ->withErrors(['dia_inicio' => 'El día de inicio no puede ser mayor que el día fin.']);
        }

        $data['activo'] = $request->has('activo') ? 1 : 0;

        $existsOverlap = GruaGuardiaSct::where('activo', 1)
            ->where('id', '!=', $guardia->id)
            ->where('tramo_id', $data['tramo_id'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('dia_inicio', [$data['dia_inicio'], $data['dia_fin']])
                  ->orWhereBetween('dia_fin', [$data['dia_inicio'], $data['dia_fin']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('dia_inicio', '<=', $data['dia_inicio'])
                         ->where('dia_fin', '>=', $data['dia_fin']);
                  });
            })
            ->exists();

        if ($existsOverlap) {
            return back()
                ->withInput()
                ->withErrors(['dia_inicio' => 'Ese rango de días se traslapa con otra guardia SCT activa en el mismo tramo.']);
        }

        $guardia->update($data);

        return redirect()->route('grua-guardias-sct.index')
            ->with('success', 'Guardia SCT actualizada correctamente.');
    }

    public function destroy($id)
    {
        $guardia = GruaGuardiaSct::findOrFail($id);
        $guardia->delete();

        return redirect()->route('grua-guardias-sct.index')
            ->with('success', 'Guardia SCT eliminada correctamente.');
    }
}
