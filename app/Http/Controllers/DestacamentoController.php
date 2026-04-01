<?php

namespace App\Http\Controllers;

use App\Models\Destacamento;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DestacamentoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:ver destacamentos']);
    }

    private function unidadCarreterasId(): int
    {
        $id = Unidad::query()->where('slug', 'carreteras')->value('id');
        return $id ? (int) $id : 4;
    }

    private function actorEsSuperadmin(User $actor): bool
    {
        return $actor->hasRole('Superadmin');
    }

    private function actorPuedeAcceder(User $actor): bool
    {
        if ($this->actorEsSuperadmin($actor)) {
            return true;
        }

        return (int) ($actor->unidad_id ?? 0) === $this->unidadCarreterasId();
    }

    private function queryVisiblesParaActor(User $actor)
    {
        return Destacamento::query()
            ->with('unidad')
            ->when(!$this->actorEsSuperadmin($actor), function ($q) {
                $q->where('unidad_id', $this->unidadCarreterasId());
            })
            ->orderBy('nombre');
    }

    private function assertAccess(): void
    {
        $actor = Auth::user();
        abort_unless($actor && $this->actorPuedeAcceder($actor), 403);
    }

    private function assertCanTouchDestacamento(Destacamento $destacamento): void
    {
        $actor = Auth::user();
        abort_unless($actor && $this->actorPuedeAcceder($actor), 403);

        if (!$this->actorEsSuperadmin($actor)) {
            abort_unless((int) $destacamento->unidad_id === $this->unidadCarreterasId(), 403);
        }
    }

    public function index()
    {
        $this->assertAccess();

        $actor = Auth::user();

        $destacamentos = $this->queryVisiblesParaActor($actor)->get();

        return view('admin.settings.destacamentos.index', compact('destacamentos'));
    }

    public function mapa()
    {
        $this->authorize('ver mapa destacamentos');
        $this->assertAccess();

        $actor = Auth::user();

        $destacamentos = $this->queryVisiblesParaActor($actor)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        return view('admin.settings.destacamentos.mapa', compact('destacamentos'));
    }

    public function create()
    {
        $this->authorize('crear destacamentos');
        $this->assertAccess();

        return view('admin.settings.destacamentos.create');
    }

    public function store(Request $request)
    {
        $this->authorize('crear destacamentos');
        $this->assertAccess();

        $validated = $request->validate([
            'clave' => 'nullable|string|max:20',
            'nombre' => 'required|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'responsable' => 'nullable|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'activa' => 'nullable|boolean',
        ]);

        $carreterasId = $this->unidadCarreterasId();

        Destacamento::create([
            'unidad_id' => $carreterasId,
            'clave' => $validated['clave'] ?? null,
            'nombre' => mb_strtoupper((string) $validated['nombre'], 'UTF-8'),
            'municipio' => !empty($validated['municipio'])
                ? mb_strtoupper((string) $validated['municipio'], 'UTF-8')
                : null,
            'lat' => isset($validated['lat']) && $validated['lat'] !== '' ? (float) $validated['lat'] : null,
            'lng' => isset($validated['lng']) && $validated['lng'] !== '' ? (float) $validated['lng'] : null,
            'direccion' => $validated['direccion'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'responsable' => !empty($validated['responsable'])
                ? mb_strtoupper((string) $validated['responsable'], 'UTF-8')
                : null,
            'referencia' => $validated['referencia'] ?? null,
            'activo' => (bool) ($validated['activa'] ?? true),
        ]);

        return redirect()->route('destacamentos.index')->with('success', 'Destacamento creado correctamente.');
    }

    public function show(Destacamento $destacamento)
    {
        $this->assertCanTouchDestacamento($destacamento);

        $destacamento->load([
            'unidad',
            'redApoyos' => function ($q) {
                $q->orderBy('tipo_apoyo')->orderBy('institucion');
            },
        ]);

        return view('admin.settings.destacamentos.show', compact('destacamento'));
    }

    public function edit(Destacamento $destacamento)
    {
        $this->authorize('editar destacamentos');
        $this->assertCanTouchDestacamento($destacamento);

        return view('admin.settings.destacamentos.edit', compact('destacamento'));
    }

    public function update(Request $request, Destacamento $destacamento)
    {
        $this->authorize('editar destacamentos');
        $this->assertCanTouchDestacamento($destacamento);

        $validated = $request->validate([
            'clave' => 'nullable|string|max:20',
            'nombre' => 'required|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'responsable' => 'nullable|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'activa' => 'nullable|boolean',
        ]);

        $carreterasId = $this->unidadCarreterasId();

        $destacamento->update([
            'unidad_id' => $carreterasId,
            'clave' => $validated['clave'] ?? null,
            'nombre' => mb_strtoupper((string) $validated['nombre'], 'UTF-8'),
            'municipio' => !empty($validated['municipio'])
                ? mb_strtoupper((string) $validated['municipio'], 'UTF-8')
                : null,
            'lat' => isset($validated['lat']) && $validated['lat'] !== '' ? (float) $validated['lat'] : null,
            'lng' => isset($validated['lng']) && $validated['lng'] !== '' ? (float) $validated['lng'] : null,
            'direccion' => $validated['direccion'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'responsable' => !empty($validated['responsable'])
                ? mb_strtoupper((string) $validated['responsable'], 'UTF-8')
                : null,
            'referencia' => $validated['referencia'] ?? null,
            'activo' => (bool) ($validated['activa'] ?? $destacamento->activo),
        ]);

        return redirect()->route('destacamentos.index')->with('success', 'Destacamento actualizado correctamente.');
    }

    public function destroy(Destacamento $destacamento)
    {
        $this->authorize('eliminar destacamentos');
        $this->assertCanTouchDestacamento($destacamento);

        $tieneUsuarios = User::query()
            ->where('destacamento_id', $destacamento->id)
            ->exists();

        if ($tieneUsuarios) {
            return back()->with('error', 'No se puede eliminar: hay usuarios asignados a este destacamento.');
        }

        $destacamento->delete();

        return back()->with('success', 'Destacamento eliminado correctamente.');
    }
}
