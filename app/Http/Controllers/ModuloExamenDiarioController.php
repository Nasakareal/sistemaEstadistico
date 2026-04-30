<?php

namespace App\Http\Controllers;

use App\Models\ModuloExamenDiario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ModuloExamenDiarioController extends Controller
{
    public function index()
    {
        $registros = ModuloExamenDiario::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return view('admin.settings.modulo_examenes_diarios.index', compact('registros'));
    }

    public function create()
    {
        return view('admin.settings.modulo_examenes_diarios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha'            => 'required|date',
            'modulo_nombre'    => 'required|string|max:180',

            'servicio_publico' => 'nullable|integer|min:0',
            'automovilista'    => 'nullable|integer|min:0',
            'chofer'           => 'nullable|integer|min:0',
            'motociclista'     => 'nullable|integer|min:0',
            'permiso'          => 'nullable|integer|min:0',

            'hombres'          => 'nullable|integer|min:0',
            'mujeres'          => 'nullable|integer|min:0',

            'aprobados'        => 'nullable|integer|min:0',
            'reprobados'       => 'nullable|integer|min:0',

            'folios'           => 'nullable|string|max:255',
            'informado_por'    => 'nullable|string|max:180',
        ]);

        try {
            $validated['servicio_publico'] = (int)($validated['servicio_publico'] ?? 0);
            $validated['automovilista']    = (int)($validated['automovilista'] ?? 0);
            $validated['chofer']           = (int)($validated['chofer'] ?? 0);
            $validated['motociclista']     = (int)($validated['motociclista'] ?? 0);
            $validated['permiso']          = (int)($validated['permiso'] ?? 0);

            $validated['hombres']    = $validated['hombres'] !== null ? (int)$validated['hombres'] : null;
            $validated['mujeres']    = $validated['mujeres'] !== null ? (int)$validated['mujeres'] : null;
            $validated['aprobados']  = $validated['aprobados'] !== null ? (int)$validated['aprobados'] : null;
            $validated['reprobados'] = $validated['reprobados'] !== null ? (int)$validated['reprobados'] : null;

            $validated['total'] =
                $validated['servicio_publico'] +
                $validated['automovilista'] +
                $validated['chofer'] +
                $validated['motociclista'] +
                $validated['permiso'];

            if (!isset($validated['informado_por']) || trim((string)$validated['informado_por']) === '') {
                $validated['informado_por'] = optional(Auth::user())->name;
            }

            $registro = ModuloExamenDiario::create($validated);

            Log::info("Módulo Examen Diario creado: ID {$registro->id} (fecha {$registro->fecha})");

            return redirect()
                ->route('modulo_examenes_diarios.index')
                ->with('success', 'Registro guardado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear módulo examen diario: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Ocurrió un error al guardar el registro.')
                ->withInput();
        }
    }

    public function show(ModuloExamenDiario $registro)
    {
        return view('admin.settings.modulo_examenes_diarios.show', [
            'registro' => $registro,
        ]);
    }

    public function edit(ModuloExamenDiario $registro)
    {
        return view('admin.settings.modulo_examenes_diarios.edit', [
            'registro' => $registro,
        ]);
    }

    public function update(Request $request, ModuloExamenDiario $registro)
    {
        $validated = $request->validate([
            'fecha'            => 'required|date',
            'modulo_nombre'    => 'required|string|max:180',

            'servicio_publico' => 'nullable|integer|min:0',
            'automovilista'    => 'nullable|integer|min:0',
            'chofer'           => 'nullable|integer|min:0',
            'motociclista'     => 'nullable|integer|min:0',
            'permiso'          => 'nullable|integer|min:0',

            'hombres'          => 'nullable|integer|min:0',
            'mujeres'          => 'nullable|integer|min:0',

            'aprobados'        => 'nullable|integer|min:0',
            'reprobados'       => 'nullable|integer|min:0',

            'folios'           => 'nullable|string|max:255',
            'informado_por'    => 'nullable|string|max:180',
        ]);

        try {
            $validated['servicio_publico'] = (int)($validated['servicio_publico'] ?? 0);
            $validated['automovilista']    = (int)($validated['automovilista'] ?? 0);
            $validated['chofer']           = (int)($validated['chofer'] ?? 0);
            $validated['motociclista']     = (int)($validated['motociclista'] ?? 0);
            $validated['permiso']          = (int)($validated['permiso'] ?? 0);

            $validated['hombres']    = $validated['hombres'] !== null ? (int)$validated['hombres'] : null;
            $validated['mujeres']    = $validated['mujeres'] !== null ? (int)$validated['mujeres'] : null;
            $validated['aprobados']  = $validated['aprobados'] !== null ? (int)$validated['aprobados'] : null;
            $validated['reprobados'] = $validated['reprobados'] !== null ? (int)$validated['reprobados'] : null;

            $validated['total'] =
                $validated['servicio_publico'] +
                $validated['automovilista'] +
                $validated['chofer'] +
                $validated['motociclista'] +
                $validated['permiso'];

            if (!isset($validated['informado_por']) || trim((string)$validated['informado_por']) === '') {
                $validated['informado_por'] = $registro->informado_por ?? optional(Auth::user())->name;
            }

            $registro->update($validated);

            Log::info("Módulo Examen Diario actualizado: ID {$registro->id}");

            return redirect()
                ->route('modulo_examenes_diarios.index')
                ->with('success', 'Registro actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar módulo examen diario: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Ocurrió un error al actualizar el registro.')
                ->withInput();
        }
    }

    public function destroy(ModuloExamenDiario $registro)
    {
        try {
            $id = $registro->id;
            $registro->delete();

            Log::info("Módulo Examen Diario eliminado: ID {$id}");

            return redirect()
                ->route('modulo_examenes_diarios.index')
                ->with('success', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar módulo examen diario: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No se pudo eliminar el registro.');
        }
    }
}
