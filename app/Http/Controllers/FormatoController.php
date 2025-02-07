<?php

namespace App\Http\Controllers;

use App\Models\Formato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormatoController extends Controller
{
    public function index()
    {
        $formatos = Formato::all();
        return view('formatos.index', compact('formatos'));
    }

    public function create()
    {
        return view('formatos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo_pdf' => 'required|mimes:pdf|max:2048',
        ]);

        $rutaArchivo = $request->file('archivo_pdf')->store('formatos', 'public');

        Formato::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'archivo_pdf' => $rutaArchivo,
        ]);

        return redirect()->route('formatos.index')->with('success', 'Formato creado exitosamente.');
    }

    public function show(Formato $formato)
    {
        return view('formatos.show', compact('formato'));
    }

    public function edit(Formato $formato)
    {
        return view('formatos.edit', compact('formato'));
    }

    public function update(Request $request, Formato $formato)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo_pdf' => 'nullable|mimes:pdf|max:2048',
        ]);

        if ($request->hasFile('archivo_pdf')) {
            
            if ($formato->archivo_pdf && Storage::disk('public')->exists($formato->archivo_pdf)) {
                Storage::disk('public')->delete($formato->archivo_pdf);
            }

            $rutaArchivo = $request->file('archivo_pdf')->store('formatos', 'public');
            $formato->archivo_pdf = $rutaArchivo;
        }

        $formato->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('formatos.index')->with('success', 'Formato actualizado exitosamente.');
    }

    public function destroy(Formato $formato)
    {
        if ($formato->archivo_pdf && Storage::disk('public')->exists($formato->archivo_pdf)) {
            Storage::disk('public')->delete($formato->archivo_pdf);
        }

        $formato->delete();

        return redirect()->route('formatos.index')->with('success', 'Formato eliminado exitosamente.');
    }
}
