<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use Illuminate\Http\Request;

class VehiculosController extends Controller
{
    public function index(Hechos $hecho)
    {
        $vehiculos = $hecho->vehiculos;
        return view('vehiculos.index', compact('hecho', 'vehiculos'));
    }

    public function create(Hechos $hecho)
    {
        $conductores = Conductor::all();
        return view('vehiculos.create', compact('hecho', 'conductores'));
    }

    public function store(Request $request, Hechos $hecho)
    {
        $validated = $request->validate([
            // Datos del vehículo
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'required|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',
            'placas'                     => 'required|string|max:15|unique:vehiculos,placas',
            'estado_placas'              => 'required|string|max:15|unique:vehiculos,estado_placas',
            'serie'                      => 'required|string|max:17|unique:vehiculos,serie',
            'capacidad_personas'         => 'required|integer|min:0',
            'tipo_servicio'              => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'required|string|max:60',
            'grua'                       => 'nullable|string|max:50',
            'corralon'                   => 'nullable|string|max:50',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',

            // Datos del conductor
            'conductor_nombre'           => 'required|string|max:255',
            'telefono'                   => 'required|digits:10',
            'domicilio'                  => 'required|string|max:255',
            'sexo'                       => 'required|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion'                  => 'required|string|max:255',
            'edad'                       => 'required|integer|min:18|max:100',
            'tipo_licencia'              => 'required|string|max:50',
            'estado_licencia'            => 'required|string|max:100',
            'vigencia_licencia'          => 'required|date',
            'numero_licencia'            => 'required|string|max:50',

            // Datos del daño patrimonial
            'danos_patrimoniales'        => 'required|string',
            'propiedad'                  => 'required|string|max:255',
            'monto_danos_patrimoniales'  => 'required|numeric|min:0',
        ]);

        $vehiculo = $hecho->vehiculos()->create($validated);

        // Crear conductor y asociarlo al vehículo
        $conductor = Conductor::create([
            'nombre'           => strtoupper($validated['conductor_nombre']),
            'telefono'         => $validated['telefono'],
            'domicilio'        => strtoupper($validated['domicilio']),
            'sexo'             => strtoupper($validated['sexo']),
            'ocupacion'        => strtoupper($validated['ocupacion']),
            'edad'             => $validated['edad'],
            'tipo_licencia'    => strtoupper($validated['tipo_licencia']),
            'estado_licencia'  => strtoupper($validated['estado_licencia']),
            'vigencia_licencia'=> $validated['vigencia_licencia'],
            'numero_licencia'  => strtoupper($validated['numero_licencia']),
        ]);

        $vehiculo->conductores()->attach($conductor->id);

        // Actualizar daños patrimoniales en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales']),
            'propiedades_afectadas'     => strtoupper($validated['propiedad']),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'],
        ]);

        return redirect()->route('vehiculos.index', $hecho->id)
                         ->with('success', 'Vehículo, conductor y daños patrimoniales agregados exitosamente.');
    }

    public function edit(Hechos $hecho, Vehiculo $vehiculo)
    {
        // Verificar que el vehículo realmente pertenece al hecho
        if (!$hecho->vehiculos->contains($vehiculo->id)) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        // Obtener el conductor asociado al vehículo
        $conductor = $vehiculo->conductores()->first();

        return view('vehiculos.edit', compact('hecho', 'vehiculo', 'conductor'));
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        $validated = $request->validate([
            // Datos del vehículo
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'required|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',
            'placas'                     => 'required|string|max:15',
            'estado_placas'              => 'required|string|max:15',
            'serie'                      => 'required|string|max:17',
            'capacidad_personas'         => 'required|integer|min:0',
            'tipo_servicio'              => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'required|string|max:60',
            'grua'                       => 'nullable|string|max:50',
            'corralon'                   => 'nullable|string|max:50',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',

            // Datos del conductor
            'conductor_nombre'           => 'required|string|max:255',
            'telefono'                   => 'required|digits:10',
            'domicilio'                  => 'required|string|max:255',
            'sexo'                       => 'required|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion'                  => 'required|string|max:255',
            'edad'                       => 'required|integer|min:18|max:100',
            'tipo_licencia'              => 'required|string|max:50',
            'estado_licencia'            => 'required|string|max:100',
            'vigencia_licencia'          => 'required|date',
            'numero_licencia'            => 'required|string|max:50',

            // Datos del daño patrimonial
            'danos_patrimoniales'        => 'required|string',
            'propiedad'                  => 'required|string|max:255',
            'monto_danos_patrimoniales'  => 'required|numeric|min:0',
        ]);

        // Actualizar el vehículo
        $vehiculo->update($validated);

        // Obtener conductor asociado y actualizar sus datos
        $conductor = $vehiculo->conductores()->first();
        if ($conductor) {
            $conductor->update([
                'nombre'           => strtoupper($validated['conductor_nombre']),
                'telefono'         => $validated['telefono'],
                'domicilio'        => strtoupper($validated['domicilio']),
                'sexo'             => strtoupper($validated['sexo']),
                'ocupacion'        => strtoupper($validated['ocupacion']),
                'edad'             => $validated['edad'],
                'tipo_licencia'    => strtoupper($validated['tipo_licencia']),
                'estado_licencia'  => strtoupper($validated['estado_licencia']),
                'vigencia_licencia'=> $validated['vigencia_licencia'],
                'numero_licencia'  => strtoupper($validated['numero_licencia']),
            ]);
        }

        // Actualizar daños patrimoniales en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales']),
            'propiedades_afectadas'     => strtoupper($validated['propiedad']),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'],
        ]);

        return redirect()->route('vehiculos.index', $hecho->id)
                         ->with('success', 'Vehículo, conductor y daños patrimoniales actualizados correctamente.');
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        $vehiculo->delete();
        return back()->with('success', 'Vehículo eliminado correctamente.');
    }
}
