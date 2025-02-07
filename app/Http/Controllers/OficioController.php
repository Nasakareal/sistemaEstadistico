<?php

namespace App\Http\Controllers;

use App\Models\Oficio;
use Illuminate\Http\Request;

class OficioController extends Controller
{
    public function index()
    {
        $oficios = Oficio::all();
        return view('oficios.index', compact('oficios'));
    }

    public function create()
    {
        return view('oficios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_oficio' => 'required|unique:oficios,numero_oficio|max:255',
            'descripcion' => 'nullable|string',
            'pdf_path' => 'required|file|mimes:pdf|max:2048',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('pdf_path')) {
            $validated['pdf_path'] = $request->file('pdf_path')->store('oficios/pdf', 'public');
        }

        if ($request->hasFile('fotos')) {
            $validated['fotos'] = array_map(fn($foto) => $foto->store('oficios/fotos', 'public'), $request->file('fotos'));
        }

        Oficio::create($validated);

        return redirect()->route('oficios.index')->with('success', 'Oficio creado exitosamente.');
    }

    public function show(Oficio $oficio)
    {
        return view('oficios.show', compact('oficio'));
    }

    public function edit(Oficio $oficio)
    {
        return view('oficios.edit', compact('oficio'));
    }

    public function update(Request $request, Oficio $oficio)
    {
        $validated = $request->validate([
            'numero_oficio' => 'required|max:255|unique:oficios,numero_oficio,' . $oficio->id,
            'descripcion' => 'nullable|string',
            'pdf_path' => 'nullable|file|mimes:pdf|max:2048',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('pdf_path')) {
            $validated['pdf_path'] = $request->file('pdf_path')->store('oficios/pdf', 'public');
        }

        if ($request->hasFile('fotos')) {
            $validated['fotos'] = array_map(fn($foto) => $foto->store('oficios/fotos', 'public'), $request->file('fotos'));
        }

        $oficio->update($validated);

        return redirect()->route('oficios.index')->with('success', 'Oficio actualizado exitosamente.');
    }

    public function destroy(Oficio $oficio)
    {
        $oficio->delete();
        return redirect()->route('oficios.index')->with('success', 'Oficio eliminado exitosamente.');
    }
}
