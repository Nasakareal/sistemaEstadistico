<?php

namespace App\Http\Controllers;

use App\Models\Grua;
use App\Models\Tramo;
use Illuminate\Http\Request;

class GruaTramoController extends Controller
{
    public function index(Grua $grua)
    {
        $asignados = $grua->tramos()
            ->orderByRaw('CASE WHEN grua_tramo.activo = 1 THEN 0 ELSE 1 END')
            ->orderBy('grua_tramo.prioridad')
            ->get();

        $tramos = Tramo::orderBy('carretera')
            ->orderBy('km_inicio')
            ->get();

        return view('gruas.tramos.index', compact('grua', 'asignados', 'tramos'));
    }

    public function store(Request $request, Grua $grua)
    {
        $data = $request->validate([
            'tramo_id'   => ['required', 'exists:tramos,id'],
            'desde'      => ['nullable', 'date'],
            'hasta'      => ['nullable', 'date', 'after_or_equal:desde'],
            'prioridad'  => ['nullable', 'integer', 'min:1', 'max:9999'],
            'activo'     => ['nullable', 'boolean'],
        ]);

        $tramoId = (int) $data['tramo_id'];

        $payload = [
            'desde' => $data['desde'] ?? null,
            'hasta' => $data['hasta'] ?? null,
            'prioridad' => $data['prioridad'] ?? 100,
            'activo' => isset($data['activo']) ? (int)$data['activo'] : 1,
        ];

        $ya = $grua->tramos()->where('tramos.id', $tramoId)->exists();

        if ($ya) {
            $grua->tramos()->updateExistingPivot($tramoId, $payload);
        } else {
            $grua->tramos()->attach($tramoId, $payload);
        }

        return redirect()
            ->route('gruas.tramos.index', $grua->id)
            ->with('success', 'Tramo asignado/actualizado correctamente.');
    }

    public function destroy(Grua $grua, Tramo $tramo)
    {
        $grua->tramos()->updateExistingPivot($tramo->id, ['activo' => 0]);

        return redirect()
            ->route('gruas.tramos.index', $grua->id)
            ->with('success', 'Tramo desactivado para esta grúa.');
    }
}
