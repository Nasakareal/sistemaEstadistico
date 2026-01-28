<?php

namespace App\Http\Controllers;

use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PatrullaController extends Controller
{
    public function index()
    {
        $actor = Auth::user();

        $patrullas = Patrulla::query()
            ->with(['unidad', 'turno'])
            ->orderBy('numero_economico')
            ->get();

        return view('admin.settings.patrullas.index', compact('patrullas'));
    }

    public function create()
    {
        $unidades = Unidad::query()->orderBy('nombre')->get();
        $turnos   = Turno::query()->orderBy('nombre')->get();

        return view('admin.settings.patrullas.create', compact('unidades', 'turnos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_economico' => 'required|string|max:20|unique:patrullas,numero_economico',
            'unidad_id'        => 'required|exists:unidades,id',
            'turno_id'         => 'nullable|exists:turnos,id',
            'activa'           => 'required|boolean',
        ]);

        try {
            $patrulla = Patrulla::create([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id'        => $validated['unidad_id'],
                'turno_id'         => $validated['turno_id'] ?? null,
                'activa'           => $validated['activa'],
            ]);

            Log::info("Patrulla creada: {$patrulla->numero_economico}");

            return redirect()
                ->route('patrullas.index')
                ->with('success', 'Patrulla creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear patrulla: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Ocurrió un error al crear la patrulla.')
                ->withInput();
        }
    }

    public function show(Patrulla $patrulla)
    {
        $patrulla->load(['unidad', 'turno']);

        return view('admin.settings.patrullas.show', compact('patrulla'));
    }

    public function edit(Patrulla $patrulla)
    {
        $unidades = Unidad::query()->orderBy('nombre')->get();
        $turnos   = Turno::query()->orderBy('nombre')->get();

        return view(
            'admin.settings.patrullas.edit',
            compact('patrulla', 'unidades', 'turnos')
        );
    }

    public function update(Request $request, Patrulla $patrulla)
    {
        $validated = $request->validate([
            'numero_economico' => 'required|string|max:20|unique:patrullas,numero_economico,' . $patrulla->id,
            'unidad_id'        => 'required|exists:unidades,id',
            'turno_id'         => 'nullable|exists:turnos,id',
            'activa'           => 'required|boolean',
        ]);

        try {
            $patrulla->update([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id'        => $validated['unidad_id'],
                'turno_id'         => $validated['turno_id'] ?? null,
                'activa'           => $validated['activa'],
            ]);

            Log::info("Patrulla actualizada: {$patrulla->numero_economico}");

            return redirect()
                ->route('patrullas.index')
                ->with('success', 'Patrulla actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar patrulla: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Ocurrió un error al actualizar la patrulla.')
                ->withInput();
        }
    }

    public function destroy(Patrulla $patrulla)
    {
        try {
            $numero = $patrulla->numero_economico;
            $patrulla->delete();

            Log::info("Patrulla eliminada: {$numero}");

            return redirect()
                ->route('patrullas.index')
                ->with('success', 'Patrulla eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar patrulla: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No se pudo eliminar la patrulla.');
        }
    }
}
