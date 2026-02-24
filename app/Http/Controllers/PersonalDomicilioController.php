<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalDomicilio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonalDomicilioController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'calle' => 'required|string|max:191',
            'numero_ext' => 'required|string|max:30',
            'numero_int' => 'nullable|string|max:30',
            'colonia' => 'required|string|max:191',
            'municipio' => 'required|string|max:191',
            'estado' => 'required|string|max:191',
            'cp' => 'required|string|max:10',
            'referencias' => 'nullable|string|max:255',
            'es_actual' => 'nullable|boolean',
        ]);

        try {
            $esActual = (bool)($request->input('es_actual', true));

            if ($esActual) {
                PersonalDomicilio::query()
                    ->where('personal_id', $personal->id)
                    ->update(['es_actual' => 0]);
            }

            PersonalDomicilio::create([
                'personal_id' => $personal->id,
                'calle' => $validated['calle'],
                'numero_ext' => $validated['numero_ext'],
                'numero_int' => $validated['numero_int'] ?? null,
                'colonia' => $validated['colonia'],
                'municipio' => $validated['municipio'],
                'estado' => $validated['estado'],
                'cp' => $validated['cp'],
                'referencias' => $validated['referencias'] ?? null,
                'es_actual' => $esActual ? 1 : 0,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Domicilio registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar domicilio de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al registrar el domicilio. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalDomicilio $domicilio)
    {
        $validated = $request->validate([
            'calle' => 'required|string|max:191',
            'numero_ext' => 'required|string|max:30',
            'numero_int' => 'nullable|string|max:30',
            'colonia' => 'required|string|max:191',
            'municipio' => 'required|string|max:191',
            'estado' => 'required|string|max:191',
            'cp' => 'required|string|max:10',
            'referencias' => 'nullable|string|max:255',
            'es_actual' => 'nullable|boolean',
        ]);

        try {
            if ((int)$domicilio->personal_id !== (int)$personal->id) {
                return redirect()->back()->withErrors('Ese domicilio no pertenece a este elemento.');
            }

            $esActual = (bool)($request->input('es_actual', false));

            if ($esActual) {
                PersonalDomicilio::query()
                    ->where('personal_id', $personal->id)
                    ->where('id', '!=', $domicilio->id)
                    ->update(['es_actual' => 0]);
            }

            $domicilio->update([
                'calle' => $validated['calle'],
                'numero_ext' => $validated['numero_ext'],
                'numero_int' => $validated['numero_int'] ?? null,
                'colonia' => $validated['colonia'],
                'municipio' => $validated['municipio'],
                'estado' => $validated['estado'],
                'cp' => $validated['cp'],
                'referencias' => $validated['referencias'] ?? null,
                'es_actual' => $esActual ? 1 : 0,
            ]);

            return redirect()->route('personal.show', $personal->id)
                ->with('success', 'Domicilio actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar domicilio de personal: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al actualizar el domicilio. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalDomicilio $domicilio)
    {
        try {
            if ((int)$domicilio->personal_id !== (int)$personal->id) {
                return redirect()
                    ->back()
                    ->withErrors('Ese domicilio no pertenece a este elemento.');
            }

            $eraActual = (bool)$domicilio->es_actual;

            $domicilio->delete();

            if ($eraActual) {
                $nuevoActual = PersonalDomicilio::query()
                    ->where('personal_id', $personal->id)
                    ->orderByDesc('id')
                    ->first();

                if ($nuevoActual) {
                    PersonalDomicilio::query()
                        ->where('personal_id', $personal->id)
                        ->update(['es_actual' => 0]);

                    $nuevoActual->es_actual = 1;
                    $nuevoActual->save();
                }
            }

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Domicilio eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar domicilio de personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el domicilio. Inténtelo nuevamente.');
        }
    }
}
