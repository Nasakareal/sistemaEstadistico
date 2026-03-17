<?php

namespace App\Http\Controllers;

use App\Models\Operativo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GuardianesCaminoDispositivoFotoController extends Controller
{
    public function store(Request $request, $operativo, $dispositivo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $archivo = $request->file('foto');
        $ruta = $archivo->store('guardianes_camino/dispositivos', 'public');

        $foto = new OperativoDispositivoFoto();
        $foto->operativo_dispositivo_id = $dispositivo->id;
        $foto->ruta = $ruta;
        $foto->nombre_original = $archivo->getClientOriginalName();
        $foto->mime_type = $archivo->getClientMimeType();
        $foto->peso = $archivo->getSize();
        $foto->observaciones = $request->observaciones;
        $foto->created_by = Auth::id();
        $foto->save();

        return redirect()
            ->route('guardianes_camino.dispositivos.show', [$operativo->id, $dispositivo->id])
            ->with('success', 'Foto subida correctamente.');
    }

    public function destroy($operativo, $dispositivo, $foto)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);

        $foto = OperativoDispositivoFoto::where('operativo_dispositivo_id', $dispositivo->id)->findOrFail($foto);

        if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
            Storage::disk('public')->delete($foto->ruta);
        }

        $foto->delete();

        return redirect()
            ->route('guardianes_camino.dispositivos.show', [$operativo->id, $dispositivo->id])
            ->with('success', 'Foto eliminada correctamente.');
    }
}
