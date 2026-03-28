<?php

namespace App\Http\Controllers;

use App\Models\Croquis;
use App\Models\Hechos;
use Illuminate\Http\Request;

class CroquisController extends Controller
{
    public function show(Hechos $hecho)
    {
        $croquis = Croquis::where('hecho_id', $hecho->id)
            ->latest('id')
            ->first();

        return view('croquis.show', compact('hecho', 'croquis'));
    }

    public function store(Request $request, Hechos $hecho)
    {
        $request->validate([
            'json_dibujo' => ['nullable', 'string'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'plantilla' => ['nullable', 'string', 'max:255'],
            'orientacion' => ['nullable', 'string', 'max:255'],
            'escala' => ['nullable', 'string', 'max:255'],
            'imagen_preview' => ['nullable', 'string'],
            'pdf_path' => ['nullable', 'string'],
        ]);

        Croquis::updateOrCreate(
            ['hecho_id' => $hecho->id],
            [
                'titulo' => $request->titulo,
                'plantilla' => $request->plantilla,
                'orientacion' => $request->orientacion,
                'escala' => $request->escala,
                'json_dibujo' => $request->json_dibujo,
                'imagen_preview' => $request->imagen_preview,
                'pdf_path' => $request->pdf_path,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis guardado correctamente.');
    }

    public function update(Request $request, Hechos $hecho)
    {
        $request->validate([
            'json_dibujo' => ['nullable', 'string'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'plantilla' => ['nullable', 'string', 'max:255'],
            'orientacion' => ['nullable', 'string', 'max:255'],
            'escala' => ['nullable', 'string', 'max:255'],
            'imagen_preview' => ['nullable', 'string'],
            'pdf_path' => ['nullable', 'string'],
        ]);

        $croquis = Croquis::where('hecho_id', $hecho->id)->first();

        if (!$croquis) {
            return $this->store($request, $hecho);
        }

        $croquis->update([
            'titulo' => $request->titulo,
            'plantilla' => $request->plantilla,
            'orientacion' => $request->orientacion,
            'escala' => $request->escala,
            'json_dibujo' => $request->json_dibujo,
            'imagen_preview' => $request->imagen_preview,
            'pdf_path' => $request->pdf_path,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis actualizado correctamente.');
    }

    public function destroy(Hechos $hecho)
    {
        $croquis = Croquis::where('hecho_id', $hecho->id)->first();

        if ($croquis) {
            $croquis->delete();
        }

        return redirect()
            ->route('croquis.show', $hecho->id)
            ->with('success', 'Croquis eliminado correctamente.');
    }
}
