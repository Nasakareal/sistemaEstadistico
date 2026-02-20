<?php

namespace App\Http\Controllers;

use App\Models\Grua;
use App\Models\GruaGuardia;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GruaGuardiaController extends Controller
{
    public function index()
    {
        $guardias = GruaGuardia::with('grua')
            ->orderByDesc('week_start')
            ->get();

        return view('grua_guardias.index', compact('guardias'));
    }

    public function create()
    {
        $gruas = Grua::orderBy('nombre')->get();

        return view('grua_guardias.create', compact('gruas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grua_id'    => ['required', 'exists:gruas,id'],
            'week_start' => ['required', 'date'],
            'week_end'   => ['required', 'date', 'after_or_equal:week_start'],
            'activo'     => ['nullable', 'boolean'],
            'notas'      => ['nullable', 'string', 'max:255'],
        ]);

        $start = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $end   = Carbon::parse($start)->addDays(6)->toDateString();

        GruaGuardia::where('week_start', $start)
            ->where('week_end', $end)
            ->update(['activo' => 0]);

        GruaGuardia::create([
            'grua_id' => $data['grua_id'],
            'week_start' => $start,
            'week_end' => $end,
            'activo' => isset($data['activo']) ? (int)$data['activo'] : 1,
            'notas' => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('grua-guardias.index')
            ->with('success', 'Guardia semanal guardada.');
    }

    public function show($id)
    {
        $grua = Grua::with([
            'tramos' => function ($q) {
                $q->wherePivot('activo', 1)
                  ->orderBy('grua_tramo.prioridad');
            },
            'guardias' => function ($q) {
                $q->where('activo', 1)
                  ->orderByDesc('week_start');
            },
        ])->findOrFail($id);

        return view('gruas.show', compact('grua'));
    }

    public function edit(GruaGuardia $grua_guardia)
    {
        $gruas = Grua::orderBy('nombre')->get();

        return view('grua_guardias.edit', ['guardia' => $grua_guardia, 'gruas' => $gruas]);
    }

    public function update(Request $request, GruaGuardia $grua_guardia)
    {
        $data = $request->validate([
            'grua_id'    => ['required', 'exists:gruas,id'],
            'week_start' => ['required', 'date'],
            'week_end'   => ['required', 'date', 'after_or_equal:week_start'],
            'activo'     => ['nullable', 'boolean'],
            'notas'      => ['nullable', 'string', 'max:255'],
        ]);

        $start = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $end   = Carbon::parse($start)->addDays(6)->toDateString();

        if (($grua_guardia->week_start !== $start) || ($grua_guardia->week_end !== $end)) {
            GruaGuardia::where('week_start', $start)
                ->where('week_end', $end)
                ->where('id', '!=', $grua_guardia->id)
                ->update(['activo' => 0]);
        }

        $grua_guardia->update([
            'grua_id' => $data['grua_id'],
            'week_start' => $start,
            'week_end' => $end,
            'activo' => isset($data['activo']) ? (int)$data['activo'] : 1,
            'notas' => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('grua-guardias.index')
            ->with('success', 'Guardia semanal actualizada.');
    }

    public function destroy(GruaGuardia $grua_guardia)
    {
        $grua_guardia->delete();

        return redirect()
            ->route('grua-guardias.index')
            ->with('success', 'Guardia eliminada.');
    }
}
