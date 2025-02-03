<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grua;

class GruaController extends Controller
{
    public function index()
    {
        $gruas = Grua::all();
        return view('gruas.index', compact('gruas'));
    }

    public function create()
    {
        return view('gruas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
        ]);

        $data = $request->all();
        $data['nombre'] = strtoupper($data['nombre']);
        $data['direccion'] = strtoupper($data['direccion'] ?? '');
        $data['telefono'] = strtoupper($data['telefono'] ?? '');
        $data['email'] = strtoupper($data['email'] ?? '');

        Grua::create($data);

        return redirect()->route('gruas.index')->with('success', 'Grúa registrada correctamente.');
    }

    public function show($id)
    {
        $grua = Grua::findOrFail($id);
        return view('gruas.show', compact('grua'));
    }

    public function edit($id)
    {
        $grua = Grua::findOrFail($id);
        return view('gruas.edit', compact('grua'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
        ]);

        $grua = Grua::findOrFail($id);

        $data = $request->all();
        $data['nombre'] = strtoupper($data['nombre']);
        $data['direccion'] = strtoupper($data['direccion'] ?? '');
        $data['telefono'] = strtoupper($data['telefono'] ?? '');
        $data['email'] = strtoupper($data['email'] ?? '');

        $grua->update($data);

        return redirect()->route('gruas.index')->with('success', 'Grúa actualizada correctamente.');
    }

    public function destroy($id)
    {
        $grua = Grua::findOrFail($id);
        $grua->delete();

        return redirect()->route('gruas.index')->with('success', 'Grúa eliminada correctamente.');
    }
}
