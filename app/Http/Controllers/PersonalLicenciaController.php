<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalLicencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PersonalLicenciaController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $validated = $this->validar($request);

        try {
            $personal->licencias()->create($this->datosParaGuardar($validated));

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Licencia registrada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al registrar licencia de personal: ' . $e->getMessage(), [
                'personal_id' => $personal->id,
            ]);

            return back()
                ->withErrors('Hubo un error al registrar la licencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalLicencia $licencia)
    {
        if ((int) $licencia->personal_id !== (int) $personal->id) {
            abort(404);
        }

        $validated = $this->validar($request);
        $data = $this->datosParaGuardar($validated, $licencia);

        try {
            $licencia->update($data);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Licencia actualizada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar licencia de personal: ' . $e->getMessage(), [
                'personal_id' => $personal->id,
                'licencia_id' => $licencia->id,
            ]);

            return back()
                ->withErrors('Hubo un error al actualizar la licencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalLicencia $licencia)
    {
        if ((int) $licencia->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            $licencia->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Licencia eliminada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar licencia de personal: ' . $e->getMessage(), [
                'personal_id' => $personal->id,
                'licencia_id' => $licencia->id,
            ]);

            return back()->withErrors('Hubo un error al eliminar la licencia.');
        }
    }

    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'max:50', Rule::in(array_keys(PersonalLicencia::tipos()))],
            'numero' => ['nullable', 'string', 'max:80'],
            'vigencia' => ['required', 'date'],
            'permanente' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (['tipo', 'numero', 'observaciones'] as $campo) {
            if (array_key_exists($campo, $validated) && is_string($validated[$campo])) {
                $validated[$campo] = trim($validated[$campo]);

                if ($validated[$campo] === '') {
                    $validated[$campo] = null;
                }
            }
        }

        return $validated;
    }

    private function datosParaGuardar(array $validated, ?PersonalLicencia $licencia = null): array
    {
        $permanente = (bool) ($validated['permanente'] ?? false);
        $vigencia = $permanente
            ? PersonalLicencia::PERMANENT_VIGENCIA
            : $validated['vigencia'];

        $data = [
            'tipo' => $validated['tipo'],
            'numero' => $validated['numero'] ?? null,
            'vigencia' => $vigencia,
            'permanente' => $permanente,
            'activo' => (bool) ($validated['activo'] ?? true),
            'observaciones' => $validated['observaciones'] ?? null,
        ];

        if (
            !$licencia
            || optional($licencia->vigencia)->toDateString() !== $vigencia
            || (bool) $licencia->permanente !== $permanente
            || (bool) $licencia->activo !== (bool) $data['activo']
        ) {
            $data['vencimiento_notificado_at'] = null;
        }

        return $data;
    }
}
