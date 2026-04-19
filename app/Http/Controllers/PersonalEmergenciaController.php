<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalEmergencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PersonalEmergenciaController extends Controller
{
    private function datosValidados(Request $request): array
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:191',
            'nombre_contacto' => 'nullable|string|max:191',
            'parentesco' => 'nullable|string|max:80',
            'telefono' => 'nullable|string|max:30',
            'telefono_emergencia' => 'nullable|string|max:20',
            'telefono_2' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
        ]);

        foreach (['nombre', 'nombre_contacto', 'parentesco', 'telefono', 'telefono_emergencia', 'telefono_2', 'direccion', 'observaciones'] as $campo) {
            if (array_key_exists($campo, $validated) && is_string($validated[$campo])) {
                $validated[$campo] = trim($validated[$campo]);
                if ($validated[$campo] === '') {
                    $validated[$campo] = null;
                }
            }
        }

        $nombre = $validated['nombre_contacto'] ?? $validated['nombre'] ?? null;
        $telefono = $validated['telefono_emergencia'] ?? $validated['telefono'] ?? null;

        $errores = [];

        if (!$nombre) {
            $errores['nombre_contacto'] = 'El nombre del contacto de emergencia es obligatorio.';
        }

        if (!$telefono) {
            $errores['telefono_emergencia'] = 'El teléfono de emergencia es obligatorio.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        $validated['nombre'] = $nombre;
        $validated['nombre_contacto'] = $nombre;
        $validated['telefono'] = $telefono;
        $validated['telefono_emergencia'] = $telefono;

        return $validated;
    }

    public function store(Request $request, Personal $personal)
    {
        $validated = $this->datosValidados($request);

        try {
            PersonalEmergencia::create([
                'personal_id' => $personal->id,
                'nombre' => $validated['nombre'],
                'nombre_contacto' => $validated['nombre_contacto'],
                'parentesco' => $validated['parentesco'] ?? null,
                'telefono' => $validated['telefono'],
                'telefono_emergencia' => $validated['telefono_emergencia'],
                'telefono_2' => $validated['telefono_2'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Contacto de emergencia agregado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al agregar emergencia de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al agregar el contacto de emergencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalEmergencia $emergencia)
    {
        $validated = $this->datosValidados($request);

        try {
            if ((int)$emergencia->personal_id !== (int)$personal->id) {
                return redirect()->back()->withErrors('Ese contacto de emergencia no pertenece a este elemento.');
            }

            $emergencia->update([
                'nombre' => $validated['nombre'],
                'nombre_contacto' => $validated['nombre_contacto'],
                'parentesco' => $validated['parentesco'] ?? null,
                'telefono' => $validated['telefono'],
                'telefono_emergencia' => $validated['telefono_emergencia'],
                'telefono_2' => $validated['telefono_2'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return redirect()->route('personal.show', $personal->id)
                ->with('success', 'Contacto de emergencia actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar emergencia de personal: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al actualizar el contacto de emergencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalEmergencia $emergencia)
    {
        try {
            if ((int)$emergencia->personal_id !== (int)$personal->id) {
                return redirect()
                    ->back()
                    ->withErrors('Ese contacto de emergencia no pertenece a este elemento.');
            }

            $emergencia->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Contacto de emergencia eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar emergencia de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el contacto de emergencia. Inténtelo nuevamente.');
        }
    }
}
