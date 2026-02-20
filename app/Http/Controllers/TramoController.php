<?php

namespace App\Http\Controllers;

use App\Models\Tramo;
use Illuminate\Http\Request;

class TramoController extends Controller
{
    public function index()
    {
        $tramos = Tramo::orderBy('carretera')
            ->orderBy('km_inicio')
            ->get();

        return view('tramos.index', compact('tramos'));
    }

    public function create()
    {
        return view('tramos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'carretera' => ['nullable', 'string', 'max:50'],
            'nombre'    => ['nullable', 'string', 'max:255'],
            'km_inicio' => ['nullable', 'numeric'],
            'km_fin'    => ['nullable', 'numeric', 'gte:km_inicio'],
            'activo'    => ['nullable', 'boolean'],
        ]);

        $data['carretera'] = strtoupper($data['carretera'] ?? '');
        $data['nombre'] = strtoupper($data['nombre'] ?? '');
        $data['activo'] = isset($data['activo']) ? (int)$data['activo'] : 1;

        Tramo::create($data);

        return redirect()
            ->route('tramos.index')
            ->with('success', 'Tramo registrado correctamente.');
    }

    public function show($id)
    {
        $tramo = Tramo::with([
            'gruas' => function ($q) {
                $q->orderBy('grua_tramo.prioridad');
            }
        ])->findOrFail($id);

        return view('tramos.show', compact('tramo'));
    }

    public function edit($id)
    {
        $tramo = Tramo::findOrFail($id);
        return view('tramos.edit', compact('tramo'));
    }

    public function update(Request $request, $id)
    {
        $tramo = Tramo::findOrFail($id);

        $data = $request->validate([
            'carretera' => ['nullable', 'string', 'max:50'],
            'nombre'    => ['nullable', 'string', 'max:255'],
            'km_inicio' => ['nullable', 'numeric'],
            'km_fin'    => ['nullable', 'numeric', 'gte:km_inicio'],
            'activo'    => ['nullable', 'boolean'],
        ]);

        $data['carretera'] = strtoupper($data['carretera'] ?? '');
        $data['nombre'] = strtoupper($data['nombre'] ?? '');
        $data['activo'] = isset($data['activo']) ? (int)$data['activo'] : 1;

        $tramo->update($data);

        return redirect()
            ->route('tramos.index')
            ->with('success', 'Tramo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tramo = Tramo::findOrFail($id);
        $tramo->delete();

        return redirect()
            ->route('tramos.index')
            ->with('success', 'Tramo eliminado correctamente.');
    }
}
