<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Models\Grua;

class ServicioController extends Controller
{
    public function index(Grua $grua)
    {
        $servicios = $grua->servicios;
        return view('servicios.index', compact('grua', 'servicios'));
    }

    public function create(Grua $grua)
    {
        return view('servicios.create', compact('grua'));
    }

    public function store(Request $request, Grua $grua)
    {
        $request->validate([
            'tipo_vehiculo' => 'required|string',
            'aseguradora' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'foto_vehiculo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Subir la foto si se proporciona
        if ($request->hasFile('foto_vehiculo')) {
            $data['foto_vehiculo'] = $request->file('foto_vehiculo')->store('fotos_vehiculos', 'public');
        }

        $grua->servicios()->create($data);

        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio registrado correctamente.');
    }

    public function show(Grua $grua, Servicio $servicio)
    {
        return view('servicios.show', compact('grua', 'servicio'));
    }

    public function edit(Grua $grua, Servicio $servicio)
    {
        return view('servicios.edit', compact('grua', 'servicio'));
    }

    public function update(Request $request, Grua $grua, Servicio $servicio)
    {
        $request->validate([
            'tipo_vehiculo' => 'required|string',
            'aseguradora' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'foto_vehiculo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Subir una nueva foto si se proporciona
        if ($request->hasFile('foto_vehiculo')) {
            $data['foto_vehiculo'] = $request->file('foto_vehiculo')->store('fotos_vehiculos', 'public');
        }

        $servicio->update($data);

        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio actualizado correctamente.');
    }

    public function grafico()
    {
        // Obtener el número de servicios por grúa
        $gruasServicios = Grua::withCount('servicios')->get();

        // Enviar los datos a la vista
        return view('servicios.grafico', compact('gruasServicios'));
    }


    public function destroy(Grua $grua, Servicio $servicio)
    {
        $servicio->delete();
        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio eliminado correctamente.');
    }
}
