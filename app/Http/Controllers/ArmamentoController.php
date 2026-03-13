<?php

namespace App\Http\Controllers;

use App\Models\Armamento;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArmamentoController extends Controller
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
        $actor = $this->actor();

        return $actor ? $actor->unidad_id : null;
    }

    private function queryArmamentoVisible()
    {
        $actor = $this->actor();

        return Armamento::query()
            ->with(['unidad'])
            ->when(!$this->actorEsSuperadmin(), function ($q) use ($actor) {
                if (!empty($actor->unidad_id)) {
                    $q->where('unidad_id', (int) $actor->unidad_id);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
    }

    private function buscarArmamentoVisibleOFail($id): Armamento
    {
        return $this->queryArmamentoVisible()->findOrFail($id);
    }

    private function unidadesDisponibles()
    {
        return Unidad::query()
            ->where('activa', 1)
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('id', $this->unidadIdActor());
            })
            ->orderBy('nombre')
            ->get();
    }

    public function index()
    {
        $armamentos = $this->queryArmamentoVisible()
            ->orderByDesc('estatus')
            ->orderBy('tipo')
            ->orderBy('clase')
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        return view('admin.settings.armamentos.index', compact('armamentos'));
    }

    public function create()
    {
        $unidades = $this->unidadesDisponibles();

        return view('admin.settings.armamentos.create', compact('unidades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',

            'tipo' => 'required|string|max:100',
            'clase' => 'nullable|string|max:100',

            'marca' => 'nullable|string|max:120',
            'modelo' => 'nullable|string|max:120',

            'matricula' => 'nullable|string|max:120|unique:armamentos,matricula',
            'serie' => 'nullable|string|max:120|unique:armamentos,serie',

            'calibre' => 'nullable|string|max:50',

            'estatus' => 'required|string|max:30',

            'observaciones' => 'nullable|string',

            'cargadores_cantidad' => 'nullable|integer|min:0|max:255',
            'cartuchos_cantidad' => 'nullable|integer|min:0|max:65535',
        ]);

        if (!$this->actorEsSuperadmin()) {
            $validated['unidad_id'] = $this->unidadIdActor();
        }

        if (!array_key_exists('cargadores_cantidad', $validated) || $validated['cargadores_cantidad'] === null) {
            $validated['cargadores_cantidad'] = 2;
        }

        if (!array_key_exists('cartuchos_cantidad', $validated) || $validated['cartuchos_cantidad'] === null) {
            $validated['cartuchos_cantidad'] = 60;
        }

        try {
            Armamento::create($validated);

            return redirect()
                ->route('armamentos.index')
                ->with('success', 'Armamento creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear armamento: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al crear el armamento. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function show($id)
    {
        $armamento = $this->buscarArmamentoVisibleOFail($id);
        $armamento->load(['unidad']);

        return view('admin.settings.armamentos.show', compact('armamento'));
    }

    public function edit($id)
    {
        $armamento = $this->buscarArmamentoVisibleOFail($id);

        $unidades = $this->unidadesDisponibles();

        return view('admin.settings.armamentos.edit', compact('armamento', 'unidades'));
    }

    public function update(Request $request, $id)
    {
        $armamento = $this->buscarArmamentoVisibleOFail($id);

        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',

            'tipo' => 'required|string|max:100',
            'clase' => 'nullable|string|max:100',

            'marca' => 'nullable|string|max:120',
            'modelo' => 'nullable|string|max:120',

            'matricula' => 'nullable|string|max:120|unique:armamentos,matricula,' . $armamento->id,
            'serie' => 'nullable|string|max:120|unique:armamentos,serie,' . $armamento->id,

            'calibre' => 'nullable|string|max:50',

            'estatus' => 'required|string|max:30',

            'observaciones' => 'nullable|string',

            'cargadores_cantidad' => 'nullable|integer|min:0|max:255',
            'cartuchos_cantidad' => 'nullable|integer|min:0|max:65535',
        ]);

        if (!$this->actorEsSuperadmin()) {
            $validated['unidad_id'] = $this->unidadIdActor();
        }

        if (!array_key_exists('cargadores_cantidad', $validated) || $validated['cargadores_cantidad'] === null) {
            $validated['cargadores_cantidad'] = 2;
        }

        if (!array_key_exists('cartuchos_cantidad', $validated) || $validated['cartuchos_cantidad'] === null) {
            $validated['cartuchos_cantidad'] = 60;
        }

        try {
            $armamento->update($validated);

            return redirect()
                ->route('armamentos.index')
                ->with('success', 'Armamento actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar armamento: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al actualizar el armamento. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $armamento = $this->buscarArmamentoVisibleOFail($id);

        try {
            $armamento->delete();

            return redirect()
                ->route('armamentos.index')
                ->with('success', 'Armamento eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar armamento: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el armamento. Inténtelo nuevamente.');
        }
    }
}
