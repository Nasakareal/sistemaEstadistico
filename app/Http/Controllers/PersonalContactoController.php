<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonalContactoController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'tipo' => 'required|string|max:30',
            'valor' => 'required|string|max:191',
            'es_principal' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            $esPrincipal = (bool)($request->input('es_principal', false));

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
        $validated = $request->validate([
            'tipo' => 'required|string|max:30',
            'valor' => 'required|string|max:191',
            'es_principal' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            if ((int)$contacto->personal_id !== (int)$personal->id) {
                return redirect()->back()->withErrors('Ese contacto no pertenece a este elemento.');
            }

            $esPrincipal = (bool)($request->input('es_principal', false));

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
