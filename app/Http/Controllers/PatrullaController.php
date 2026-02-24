<?php

namespace App\Http\Controllers;

use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PatrullaController extends Controller
{
    public function index()
    {
        $actor = Auth::user();

        $patrullas = Patrulla::query()
            ->with(['unidad', 'turno'])
            ->orderByDesc('activa')
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

            'tipo'         => 'nullable|string|max:30',
            'marca'        => 'nullable|string|max:80',
            'linea'        => 'nullable|string|max:120',
            'modelo'       => 'nullable|integer|min:1900|max:2100',

            'placas'       => 'nullable|string|max:20|unique:patrullas,placas',
            'serie'        => 'nullable|string|max:60|unique:patrullas,serie',

            'color'        => 'nullable|string|max:50',
            'no_motor'     => 'nullable|string|max:60',
            'observaciones'=> 'nullable|string',
        ]);

        try {
            $validated['placas'] = $this->normalizarPlacas($validated['placas'] ?? null);
            $validated['serie']  = $this->normalizarSerie($validated['serie'] ?? null);
            $validated['tipo']   = $this->normalizarTexto($validated['tipo'] ?? null);
            $validated['marca']  = $this->normalizarTexto($validated['marca'] ?? null);

            $patrulla = Patrulla::create([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id'        => $validated['unidad_id'],
                'turno_id'         => $validated['turno_id'] ?? null,
                'activa'           => $validated['activa'],

                'tipo'             => $validated['tipo'] ?? null,
                'marca'            => $validated['marca'] ?? null,
                'linea'            => $validated['linea'] ?? null,
                'modelo'           => $validated['modelo'] ?? null,

                'placas'           => $validated['placas'] ?? null,
                'serie'            => $validated['serie'] ?? null,

                'color'            => $validated['color'] ?? null,
                'no_motor'         => $validated['no_motor'] ?? null,
                'observaciones'    => $validated['observaciones'] ?? null,
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

        return view('admin.settings.patrullas.edit', compact('patrulla', 'unidades', 'turnos'));
    }

    public function update(Request $request, Patrulla $patrulla)
    {
        $validated = $request->validate([
            'numero_economico' => 'required|string|max:20|unique:patrullas,numero_economico,' . $patrulla->id,
            'unidad_id'        => 'required|exists:unidades,id',
            'turno_id'         => 'nullable|exists:turnos,id',
            'activa'           => 'required|boolean',

            'tipo'         => 'nullable|string|max:30',
            'marca'        => 'nullable|string|max:80',
            'linea'        => 'nullable|string|max:120',
            'modelo'       => 'nullable|integer|min:1900|max:2100',

            'placas'       => 'nullable|string|max:20|unique:patrullas,placas,' . $patrulla->id,
            'serie'        => 'nullable|string|max:60|unique:patrullas,serie,' . $patrulla->id,

            'color'        => 'nullable|string|max:50',
            'no_motor'     => 'nullable|string|max:60',
            'observaciones'=> 'nullable|string',
        ]);

        try {
            $validated['placas'] = $this->normalizarPlacas($validated['placas'] ?? null);
            $validated['serie']  = $this->normalizarSerie($validated['serie'] ?? null);
            $validated['tipo']   = $this->normalizarTexto($validated['tipo'] ?? null);
            $validated['marca']  = $this->normalizarTexto($validated['marca'] ?? null);

            $patrulla->update([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id'        => $validated['unidad_id'],
                'turno_id'         => $validated['turno_id'] ?? null,
                'activa'           => $validated['activa'],

                'tipo'             => $validated['tipo'] ?? null,
                'marca'            => $validated['marca'] ?? null,
                'linea'            => $validated['linea'] ?? null,
                'modelo'           => $validated['modelo'] ?? null,

                'placas'           => $validated['placas'] ?? null,
                'serie'            => $validated['serie'] ?? null,

                'color'            => $validated['color'] ?? null,
                'no_motor'         => $validated['no_motor'] ?? null,
                'observaciones'    => $validated['observaciones'] ?? null,
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

    private function normalizarPlacas(?string $placas): ?string
    {
        if ($placas === null) return null;

        $placas = trim($placas);
        if ($placas === '') return null;

        $placas = str_replace([' ', '-', "\t", "\n", "\r"], '', $placas);
        return mb_strtoupper($placas, 'UTF-8');
    }

    private function normalizarSerie(?string $serie): ?string
    {
        if ($serie === null) return null;

        $serie = trim($serie);
        if ($serie === '') return null;

        $serie = str_replace([' ', "\t", "\n", "\r"], '', $serie);
        return mb_strtoupper($serie, 'UTF-8');
    }

    private function normalizarTexto(?string $texto): ?string
    {
        if ($texto === null) return null;

        $texto = trim($texto);
        if ($texto === '') return null;

        return mb_strtoupper($texto, 'UTF-8');
    }
}
