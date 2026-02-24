<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalEmergencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonalEmergenciaController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:191',
            'parentesco' => 'nullable|string|max:80',
            'telefono' => 'required|string|max:30',
            'telefono_2' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            PersonalEmergencia::create([
                'personal_id' => $personal->id,
                'nombre' => $validated['nombre'],
                'parentesco' => $validated['parentesco'] ?? null,
                'telefono' => $validated['telefono'],
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
        $validated = $request->validate([
            'nombre' => 'required|string|max:191',
            'parentesco' => 'nullable|string|max:80',
            'telefono' => 'required|string|max:30',
            'telefono_2' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            if ((int)$emergencia->personal_id !== (int)$personal->id) {
                return redirect()->back()->withErrors('Ese contacto de emergencia no pertenece a este elemento.');
            }

            $emergencia->update([
                'nombre' => $validated['nombre'],
                'parentesco' => $validated['parentesco'] ?? null,
                'telefono' => $validated['telefono'],
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
