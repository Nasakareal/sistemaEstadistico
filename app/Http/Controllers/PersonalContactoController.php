<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PersonalContactoController extends Controller
{
    private function datosValidados(Request $request): array
    {
        $validated = $request->validate([
            'tipo' => 'nullable|string|max:30',
            'valor' => 'nullable|string|max:191',
            'telefono_personal' => 'nullable|string|max:20',
            'telefono_secundario' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|email|max:191',
            'es_principal' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:255',
        ]);

        foreach (['tipo', 'valor', 'telefono_personal', 'telefono_secundario', 'correo_electronico', 'observaciones'] as $campo) {
            if (array_key_exists($campo, $validated) && is_string($validated[$campo])) {
                $validated[$campo] = trim($validated[$campo]);
                if ($validated[$campo] === '') {
                    $validated[$campo] = null;
                }
            }
        }

        $valorPrincipal = $validated['valor']
            ?? $validated['telefono_personal']
            ?? $validated['telefono_secundario']
            ?? $validated['correo_electronico']
            ?? null;

        if (!$valorPrincipal) {
            throw ValidationException::withMessages([
                'valor' => 'Registra al menos un teléfono, correo o valor de contacto.',
            ]);
        }

        $tipo = $validated['tipo'] ?? null;

        if (!$tipo) {
            if (!empty($validated['telefono_personal'])) {
                $tipo = 'TELEFONO_PERSONAL';
            } elseif (!empty($validated['telefono_secundario'])) {
                $tipo = 'TELEFONO_SECUNDARIO';
            } elseif (!empty($validated['correo_electronico'])) {
                $tipo = 'CORREO';
            } else {
                $tipo = 'OTRO';
            }
        }

        $validated['tipo'] = $tipo;
        $validated['valor'] = $valorPrincipal;
        $validated['es_principal'] = (bool) $request->input('es_principal', false);

        return $validated;
    }

    public function store(Request $request, Personal $personal)
    {
        $validated = $this->datosValidados($request);

        try {
            $esPrincipal = (bool) $validated['es_principal'];

            if ($esPrincipal) {
                PersonalContacto::query()
                    ->where('personal_id', $personal->id)
                    ->update(['es_principal' => 0]);
            }

            PersonalContacto::create([
                'personal_id' => $personal->id,
                'tipo' => $validated['tipo'],
                'valor' => $validated['valor'],
                'es_principal' => $esPrincipal ? 1 : 0,
                'telefono_personal' => $validated['telefono_personal'] ?? null,
                'telefono_secundario' => $validated['telefono_secundario'] ?? null,
                'correo_electronico' => $validated['correo_electronico'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Contacto agregado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al agregar contacto de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al agregar el contacto. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalContacto $contacto)
    {
        $validated = $this->datosValidados($request);

        try {
            if ((int)$contacto->personal_id !== (int)$personal->id) {
                return redirect()->back()->withErrors('Ese contacto no pertenece a este elemento.');
            }

            $esPrincipal = (bool) $validated['es_principal'];

            if ($esPrincipal) {
                PersonalContacto::query()
                    ->where('personal_id', $personal->id)
                    ->where('id', '!=', $contacto->id)
                    ->update(['es_principal' => 0]);
            }

            $contacto->update([
                'tipo' => $validated['tipo'],
                'valor' => $validated['valor'],
                'es_principal' => $esPrincipal ? 1 : 0,
                'telefono_personal' => $validated['telefono_personal'] ?? null,
                'telefono_secundario' => $validated['telefono_secundario'] ?? null,
                'correo_electronico' => $validated['correo_electronico'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return redirect()->route('personal.show', $personal->id)
                ->with('success', 'Contacto actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar contacto de personal: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al actualizar el contacto. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalContacto $contacto)
    {
        try {
            if ((int)$contacto->personal_id !== (int)$personal->id) {
                return redirect()
                    ->back()
                    ->withErrors('Ese contacto no pertenece a este elemento.');
            }

            $contacto->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Contacto eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar contacto de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el contacto. Inténtelo nuevamente.');
        }
    }
}
