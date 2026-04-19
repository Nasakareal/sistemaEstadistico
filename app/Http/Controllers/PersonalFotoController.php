<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PersonalFotoController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $archivos = [];

        if ($request->hasFile('foto')) {
            $archivos[] = $request->file('foto');
        }

        foreach ((array) $request->file('fotos', []) as $archivo) {
            if ($archivo) {
                $archivos[] = $archivo;
            }
        }

        if (!$archivos) {
            throw ValidationException::withMessages([
                'foto' => 'Selecciona al menos una foto.',
            ]);
        }

        try {
            $primeraRuta = null;

            foreach ($archivos as $archivo) {
                $ruta = $archivo->store('personals/fotos', 'public');
                if ($primeraRuta === null) {
                    $primeraRuta = $ruta;
                }

                $personal->fotos()->create([
                    'ruta' => $ruta,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getClientMimeType(),
                    'tamano' => $archivo->getSize(),
                ]);
            }

            if ($primeraRuta) {
                $personal->update(['foto' => $primeraRuta]);
            }

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Foto registrada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar foto de personal: ' . $e->getMessage());

            return back()
                ->withErrors('Hubo un error al registrar la foto. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalFoto $foto)
    {
        if ((int) $foto->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }

            $rutaEliminada = $foto->ruta;
            $foto->delete();

            if ($personal->foto === $rutaEliminada) {
                $nuevaPrincipal = $personal->fotos()
                    ->latest('id')
                    ->first();

                $personal->update([
                    'foto' => $nuevaPrincipal ? $nuevaPrincipal->ruta : null,
                ]);
            }

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Foto eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar foto de personal: ' . $e->getMessage());

            return back()->withErrors('Hubo un error al eliminar la foto.');
        }
    }
}
