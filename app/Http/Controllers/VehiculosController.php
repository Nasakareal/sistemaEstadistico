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
        // 1) Validación básica de todos los campos
        $validated = $request->validate([
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'nullable|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',
            'placas'                     => 'required|string|max:15',
            'estado_placas'              => 'nullable|string|max:15',
            'serie'                      => 'nullable|string|max:17',
            'capacidad_personas'         => 'required|integer|min:0',
            'tipo_servicio'              => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'grua'                       => 'nullable|string|max:50',
            'corralon'                   => 'nullable|string|max:50',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',
            'fotos'                      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'conductor_nombre'           => 'nullable|string|max:255',
            'telefono'                   => 'nullable|digits:10',
            'domicilio'                  => 'nullable|string|max:255',
            'sexo'                       => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion'                  => 'nullable|string|max:255',
            'edad'                       => 'nullable|integer|min:0|max:100',
            'tipo_licencia'              => 'nullable|string|max:50',
            'estado_licencia'            => 'nullable|string|max:100',
            'vigencia_licencia'          => 'nullable|date',
            'numero_licencia'            => 'nullable|string|max:50',
            'permanente'                 => 'sometimes',
            'danos_patrimoniales'        => 'nullable|string',
            'propiedad'                  => 'nullable|string|max:255',
            'monto_danos_patrimoniales'  => 'nullable|numeric|min:0',
        ]);

        // 2) Normalizar ANTES de checar duplicados
        $validated['placas']             = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']              = strtoupper(str_replace('-', '', $validated['serie'] ?? ''));
        $validated['conductor_nombre']   = strtoupper($validated['conductor_nombre'] ?? '');

        // 3) Validación personalizada: no repetir en el mismo hecho
        $placaRepetida = $hecho->vehiculos()->where('placas', $validated['placas'])->exists();
        $serieRepetida = !empty($validated['serie']) && $hecho->vehiculos()->where('serie', $validated['serie'])->exists();
        $conductorRepetido = !empty($validated['conductor_nombre']) &&
            $hecho->vehiculos()
                  ->whereHas('conductores', fn($q) => $q->where('nombre', $validated['conductor_nombre']))
                  ->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida)     $errors['placas']            = 'Ya existe un vehículo con estas placas en este hecho.';
            if ($serieRepetida)     $errors['serie']             = 'Ya existe un vehículo con esta serie en este hecho.';
            if ($conductorRepetido) $errors['conductor_nombre']  = 'Este conductor ya está registrado en este hecho.';

            return back()->withErrors($errors)->withInput();
        }

        // 4) Resto de normalizaciones de formato
        $validated['marca']                      = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                       = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                      = ucfirst(strtolower($validated['linea']));
        $validated['color']                      = ucfirst(strtolower($validated['color']));
        $validated['estado_placas']              = strtoupper($validated['estado_placas'] ?? '');
        $validated['tipo_servicio']              = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper($validated['tarjeta_circulacion_nombre'] ?? '');
        $validated['grua']                       = strtoupper($validated['grua'] ?? '');
        $validated['corralon']                   = strtoupper($validated['corralon'] ?? '');
        $validated['partes_danadas']             = strtoupper($validated['partes_danadas']);

        // 5) Procesar foto si se subió
        if ($request->hasFile('fotos') && $request->file('fotos')->isValid()) {
            $validated['fotos'] = $request->file('fotos')->store('vehiculos', 'public');
        }

        // 6) Crear y asociar vehículo
        $vehiculo = Vehiculo::create($validated);
        $hecho->vehiculos()->attach($vehiculo->id);

        // 7) Crear conductor y asociar (si proporcionó datos)
        if (!empty($validated['conductor_nombre']) || !empty($validated['telefono']) || !empty($validated['domicilio'])) {
            $conductor = Conductor::create([
                'nombre'            => $validated['conductor_nombre'],
                'telefono'          => $validated['telefono'] ?? null,
                'domicilio'         => strtoupper($validated['domicilio'] ?? ''),
                'sexo'              => strtoupper($validated['sexo'] ?? ''),
                'ocupacion'         => strtoupper($validated['ocupacion'] ?? ''),
                'edad'              => $validated['edad'] ?? null,
                'tipo_licencia'     => strtoupper($validated['tipo_licencia'] ?? ''),
                'estado_licencia'   => strtoupper($validated['estado_licencia'] ?? ''),
                'vigencia_licencia' => $request->has('permanente') ? null : ($validated['vigencia_licencia'] ?? null),
                'numero_licencia'   => strtoupper($validated['numero_licencia'] ?? ''),
                'permanente'        => $request->has('permanente'),
            ]);

            $vehiculo->conductores()->attach($conductor->id);
        }

        // 8) Actualizar daños en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales'] ?? ''),
            'propiedades_afectadas'     => strtoupper($validated['propiedad'] ?? ''),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
        ]);

        // 9) Redirect con mensaje
        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo, conductor, foto y daños patrimoniales agregados exitosamente.');
    }

    public function edit(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos->contains($vehiculo->id)) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }
        $conductor = $vehiculo->conductores()->first();
        return view('vehiculos.edit', compact('hecho', 'vehiculo', 'conductor'));
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        // 1) Quitar 'fotos' si no viene archivo
        if (! $request->hasFile('fotos')) {
            $request->request->remove('fotos');
        }

        // 2) Validación básica
        $validated = $request->validate([
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'nullable|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',
            'placas'                     => 'required|string|max:15',
            'estado_placas'              => 'nullable|string|max:15',
            'serie'                      => 'nullable|string|max:17',
            'capacidad_personas'         => 'required|integer|min:0',
            'tipo_servicio'              => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'grua'                       => 'nullable|string|max:50',
            'corralon'                   => 'nullable|string|max:50',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',
            'fotos'                      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'conductor_nombre'           => 'nullable|string|max:255',
            'telefono'                   => 'nullable|digits:10',
            'domicilio'                  => 'nullable|string|max:255',
            'sexo'                       => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion'                  => 'nullable|string|max:255',
            'edad'                       => 'nullable|integer|min:0|max:100',
            'tipo_licencia'              => 'nullable|string|max:50',
            'estado_licencia'            => 'nullable|string|max:100',
            'vigencia_licencia'          => 'nullable|date',
            'numero_licencia'            => 'nullable|string|max:50',
            'permanente'                 => 'sometimes',
            'danos_patrimoniales'        => 'nullable|string',
            'propiedad'                  => 'nullable|string|max:255',
            'monto_danos_patrimoniales'  => 'nullable|numeric|min:0',
        ]);

        // 3) Normalizar ANTES de checar duplicados
        $validated['placas']           = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']            = strtoupper(str_replace('-', '', $validated['serie']));
        $validated['conductor_nombre'] = strtoupper($validated['conductor_nombre']);

        // 4) Validar duplicados dentro del mismo hecho (excepto este vehículo)
        $placaRepetida = $hecho->vehiculos()
            ->where('placas', $validated['placas'])
            ->where('vehiculos.id', '!=', $vehiculo->id)
            ->exists();

        $serieRepetida = !empty($validated['serie']) && $hecho->vehiculos()
            ->where('serie', $validated['serie'])
            ->where('vehiculos.id', '!=', $vehiculo->id)
            ->exists();

        $conductorRepetido = $hecho->vehiculos()
            ->where('vehiculos.id', '!=', $vehiculo->id)
            ->whereHas('conductores', fn($q) => $q->where('nombre', $validated['conductor_nombre']))
            ->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida)     $errors['placas']           = 'Ya existe un vehículo con estas placas en este hecho.';
            if ($serieRepetida)     $errors['serie']            = 'Ya existe un vehículo con esta serie en este hecho.';
            if ($conductorRepetido) $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            return back()->withErrors($errors)->withInput();
        }

        // 5) Resto de normalizaciones de formato
        $validated['marca']                      = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                       = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                      = ucfirst(strtolower($validated['linea']));
        $validated['color']                      = ucfirst(strtolower($validated['color']));
        $validated['estado_placas']              = strtoupper($validated['estado_placas']);
        $validated['tipo_servicio']              = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper($validated['tarjeta_circulacion_nombre']);
        $validated['grua']                       = strtoupper($validated['grua'] ?? '');
        $validated['corralon']                   = strtoupper($validated['corralon'] ?? '');
        $validated['partes_danadas']             = strtoupper($validated['partes_danadas']);

        // 6) Procesar foto
        if ($request->hasFile('fotos')) {
            $validated['fotos'] = $request->file('fotos')->store('vehiculos', 'public');
        } else {
            $validated['fotos'] = $vehiculo->fotos;
        }

        // 7) Actualizar vehículo
        $vehiculo->update($validated);

        // 8) Actualizar conductor
        if ($conductor = $vehiculo->conductores()->first()) {
            $conductor->update([
                'nombre'            => $validated['conductor_nombre'],
                'telefono'          => $validated['telefono'] ?? null,
                'domicilio'         => strtoupper($validated['domicilio']),
                'sexo'              => strtoupper($validated['sexo']),
                'ocupacion'         => strtoupper($validated['ocupacion'] ?? ''),
                'edad'              => $validated['edad'],
                'tipo_licencia'     => strtoupper($validated['tipo_licencia'] ?? ''),
                'estado_licencia'   => strtoupper($validated['estado_licencia'] ?? ''),
                'vigencia_licencia' => $request->has('permanente') ? null : ($validated['vigencia_licencia'] ?? null),
                'numero_licencia'   => strtoupper($validated['numero_licencia'] ?? ''),
                'permanente'        => $request->has('permanente'),
            ]);
        }

        // 9) Actualizar daños en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales'] ?? ''),
            'propiedades_afectadas'     => strtoupper($validated['propiedad'] ?? ''),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
        ]);

        // 10) Redirect con éxito
        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo, conductor, foto y daños patrimoniales actualizados correctamente.');
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        $vehiculo->delete();
        return back()->with('success', 'Vehículo eliminado correctamente.');
    }
}
