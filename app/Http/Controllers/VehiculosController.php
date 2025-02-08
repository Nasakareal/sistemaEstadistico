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
        // Agregar validación para 'numero_licencia'
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
            'numero_licencia'            => 'required|string|max:50',  // Se agrega la validación
        ]);

        // Convertir campos a mayúsculas
        $camposMayusculas = [
            'marca', 'modelo', 'tipo', 'linea', 'color', 'estado_placas',
            'serie', 'tipo_servicio', 'tarjeta_circulacion_nombre', 'grua', 'corralon',
        ];
        foreach ($camposMayusculas as $campo) {
            if (isset($validated[$campo])) {
                $validated[$campo] = strtoupper($validated[$campo]);
            }
        }

        // Preparar los datos del conductor
        $conductorData = [
            'nombre'           => $validated['conductor_nombre'],
            'telefono'         => $validated['telefono'],
            'domicilio'        => $validated['domicilio'],
            'sexo'             => strtoupper($validated['sexo']),
            'ocupacion'        => strtoupper($validated['ocupacion']),
            'edad'             => $validated['edad'],
            'tipo_licencia'    => strtoupper($validated['tipo_licencia']),
            'estado_licencia'  => strtoupper($validated['estado_licencia']),
            'vigencia_licencia'=> $validated['vigencia_licencia'],
            'numero_licencia'  => strtoupper($validated['numero_licencia']),  // Se incluye en los datos
        ];

        // Eliminar los campos del conductor del arreglo del vehículo
        unset(
            $validated['conductor_nombre'], $validated['telefono'], $validated['domicilio'],
            $validated['sexo'], $validated['ocupacion'], $validated['edad'],
            $validated['tipo_licencia'], $validated['estado_licencia'], $validated['vigencia_licencia'],
            $validated['numero_licencia']
        );

        // Crear el vehículo y el conductor
        $vehiculo = $hecho->vehiculos()->create($validated);
        $conductor = Conductor::create($conductorData);

        // Asociar el conductor al vehículo
        $vehiculo->conductores()->attach($conductor->id);

        return redirect()->route('vehiculos.index', $hecho->id)
                         ->with('success', 'Vehículo y conductor agregados exitosamente.');
    }


    public function show(Vehiculo $vehiculo)
    {
        //
    }

    public function edit(Vehiculo $vehiculo)
    {
        //
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        //
    }

    public function destroy(Vehiculo $vehiculo)
    {
        //
    }
}
