<?php

namespace App\Http\Controllers;

use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PatrullaController extends Controller
{
    private function actor()
    {
        return Auth::user();
    }

    private function actorEsSuperadmin(): bool
    {
        $actor = $this->actor();
        return $actor && $actor->hasRole('Superadmin');
    }

    private function unidadIdActor(): ?int
    {
        return optional($this->actor())->unidad_id;
    }

    private function queryPatrullasVisibles()
    {
        $actor = $this->actor();

        return Patrulla::query()
            ->with(['unidad', 'turno'])
            ->when(!$this->actorEsSuperadmin(), function ($q) use ($actor) {
                if (!empty($actor->unidad_id)) {
                    $q->where('unidad_id', (int) $actor->unidad_id);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
    }

    private function buscarPatrullaVisibleOFail($id): Patrulla
    {
        return $this->queryPatrullasVisibles()->findOrFail($id);
    }

    private function unidadesDisponibles()
    {
        return Unidad::query()
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('id', $this->unidadIdActor());
            })
            ->orderBy('nombre')
            ->get();
    }

    public function index()
    {
        $patrullas = $this->queryPatrullasVisibles()
            ->orderByDesc('activa')
            ->orderBy('numero_economico')
            ->get();

        return view('admin.settings.patrullas.index', compact('patrullas'));
    }

    public function create()
    {
        $unidades = $this->unidadesDisponibles();
        $turnos = Turno::query()->orderBy('nombre')->get();

        return view('admin.settings.patrullas.create', compact('unidades', 'turnos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_economico' => 'required|string|max:20|unique:patrullas,numero_economico',
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'activa' => 'required|boolean',
            'tipo' => 'nullable|string|max:30',
            'marca' => 'nullable|string|max:80',
            'linea' => 'nullable|string|max:120',
            'modelo' => 'nullable|integer|min:1900|max:2100',
            'placas' => 'nullable|string|max:20|unique:patrullas,placas',
            'serie' => 'nullable|string|max:60|unique:patrullas,serie',
            'color' => 'nullable|string|max:50',
            'no_motor' => 'nullable|string|max:60',
            'observaciones' => 'nullable|string',
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (!$this->actorEsSuperadmin()) {
            $validated['unidad_id'] = $this->unidadIdActor();
        }

        try {
            $validated['placas'] = $this->normalizarPlacas($validated['placas'] ?? null);
            $validated['serie'] = $this->normalizarSerie($validated['serie'] ?? null);
            $validated['tipo'] = $this->normalizarTexto($validated['tipo'] ?? null);
            $validated['marca'] = $this->normalizarTexto($validated['marca'] ?? null);
            $validated['linea'] = $this->normalizarTexto($validated['linea'] ?? null);
            $validated['color'] = $this->normalizarTexto($validated['color'] ?? null);

            if ($request->hasFile('foto')) {
                $validated['foto'] = $request->file('foto')->store('patrullas', 'public');
            }

            $patrulla = Patrulla::create([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id' => $validated['unidad_id'],
                'turno_id' => $validated['turno_id'] ?? null,
                'activa' => $validated['activa'],
                'tipo' => $validated['tipo'] ?? null,
                'marca' => $validated['marca'] ?? null,
                'linea' => $validated['linea'] ?? null,
                'modelo' => $validated['modelo'] ?? null,
                'placas' => $validated['placas'] ?? null,
                'serie' => $validated['serie'] ?? null,
                'color' => $validated['color'] ?? null,
                'no_motor' => $validated['no_motor'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'foto' => $validated['foto'] ?? null,
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

    public function show($id)
    {
        $patrulla = $this->buscarPatrullaVisibleOFail($id);
        $patrulla->load(['unidad', 'turno']);

        return view('admin.settings.patrullas.show', compact('patrulla'));
    }

    public function edit($id)
    {
        $patrulla = $this->buscarPatrullaVisibleOFail($id);

        $unidades = $this->unidadesDisponibles();
        $turnos = Turno::query()->orderBy('nombre')->get();

        return view('admin.settings.patrullas.edit', compact('patrulla', 'unidades', 'turnos'));
    }

    public function update(Request $request, $id)
    {
        $patrulla = $this->buscarPatrullaVisibleOFail($id);

        $validated = $request->validate([
            'numero_economico' => 'required|string|max:20|unique:patrullas,numero_economico,' . $patrulla->id,
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'activa' => 'required|boolean',
            'tipo' => 'nullable|string|max:30',
            'marca' => 'nullable|string|max:80',
            'linea' => 'nullable|string|max:120',
            'modelo' => 'nullable|integer|min:1900|max:2100',
            'placas' => 'nullable|string|max:20|unique:patrullas,placas,' . $patrulla->id,
            'serie' => 'nullable|string|max:60|unique:patrullas,serie,' . $patrulla->id,
            'color' => 'nullable|string|max:50',
            'no_motor' => 'nullable|string|max:60',
            'observaciones' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (!$this->actorEsSuperadmin()) {
            $validated['unidad_id'] = $this->unidadIdActor();
        }

        try {
            $validated['placas'] = $this->normalizarPlacas($validated['placas'] ?? null);
            $validated['serie'] = $this->normalizarSerie($validated['serie'] ?? null);
            $validated['tipo'] = $this->normalizarTexto($validated['tipo'] ?? null);
            $validated['marca'] = $this->normalizarTexto($validated['marca'] ?? null);
            $validated['linea'] = $this->normalizarTexto($validated['linea'] ?? null);
            $validated['color'] = $this->normalizarTexto($validated['color'] ?? null);

            if ($request->hasFile('foto')) {
                if ($patrulla->foto && Storage::disk('public')->exists($patrulla->foto)) {
                    Storage::disk('public')->delete($patrulla->foto);
                }

                $validated['foto'] = $request->file('foto')->store('patrullas', 'public');
            }

            $patrulla->update([
                'numero_economico' => $validated['numero_economico'],
                'unidad_id' => $validated['unidad_id'],
                'turno_id' => $validated['turno_id'] ?? null,
                'activa' => $validated['activa'],
                'tipo' => $validated['tipo'] ?? null,
                'marca' => $validated['marca'] ?? null,
                'linea' => $validated['linea'] ?? null,
                'modelo' => $validated['modelo'] ?? null,
                'placas' => $validated['placas'] ?? null,
                'serie' => $validated['serie'] ?? null,
                'color' => $validated['color'] ?? null,
                'no_motor' => $validated['no_motor'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'foto' => $validated['foto'] ?? $patrulla->foto,
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

    public function destroy($id)
    {
        $patrulla = $this->buscarPatrullaVisibleOFail($id);

        try {
            $numero = $patrulla->numero_economico;

            if ($patrulla->foto && Storage::disk('public')->exists($patrulla->foto)) {
                Storage::disk('public')->delete($patrulla->foto);
            }

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
        if ($placas === null) {
            return null;
        }

        $placas = trim($placas);

        if ($placas === '') {
            return null;
        }

        $placas = str_replace([' ', '-', "\t", "\n", "\r"], '', $placas);

        return mb_strtoupper($placas, 'UTF-8');
    }

    private function normalizarSerie(?string $serie): ?string
    {
        if ($serie === null) {
            return null;
        }

        $serie = trim($serie);

        if ($serie === '') {
            return null;
        }

        $serie = str_replace([' ', "\t", "\n", "\r"], '', $serie);

        return mb_strtoupper($serie, 'UTF-8');
    }

    private function normalizarTexto(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        $texto = trim($texto);

        if ($texto === '') {
            return null;
        }

        return mb_strtoupper($texto, 'UTF-8');
    }
}
