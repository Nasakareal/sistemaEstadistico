<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use Illuminate\Support\Facades\DB;
use App\Models\Grua;
use App\Models\Servicios;
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
        $gruas = Grua::orderBy('nombre')->get();

        return view('vehiculos.create', compact('hecho', 'conductores', 'gruas'));
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
            'grua_id'                    => 'nullable|exists:gruas,id',
            'corralon'                   => 'nullable|string|max:50',
            'aseguradora'                => 'nullable|string|max:100',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',
            'antecedente_vehiculo'       => 'sometimes|boolean',
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
            'permanente'                 => 'sometimes|boolean',
            'cinturon'                   => 'sometimes|boolean',
            'antecedente_conductor'      => 'sometimes|boolean',
            'certificado_lesiones'       => 'sometimes|boolean',
            'certificado_alcoholemia'    => 'sometimes|boolean',
            'aliento_etilico'            => 'sometimes|boolean',
            'danos_patrimoniales'        => 'nullable|string',
            'propiedad'                  => 'nullable|string|max:255',
            'monto_danos_patrimoniales'  => 'nullable|numeric|min:0',
        ]);

        // 2) Normalizar ANTES de checar duplicados
        $validated['placas']           = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']            = strtoupper(str_replace('-', '', $validated['serie'] ?? ''));
        $validated['conductor_nombre'] = strtoupper($validated['conductor_nombre'] ?? '');

        // Mapear checkboxes a booleanos
        $validated['antecedente_vehiculo']   = $request->has('antecedente_vehiculo');
        $validated['cinturon']               = $request->has('cinturon');
        $validated['antecedente_conductor']  = $request->has('antecedente_conductor');
        $validated['certificado_lesiones']   = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia']= $request->has('certificado_alcoholemia');
        $validated['aliento_etilico']        = $request->has('aliento_etilico');
        $validated['permanente']             = $request->has('permanente');

        // 3) Validación personalizada: no repetir en el mismo hecho
        $placaRepetida = $hecho->vehiculos()
                               ->where('placas', $validated['placas'])
                               ->exists();
        $serieRepetida = !empty($validated['serie']) &&
                         $hecho->vehiculos()
                               ->where('serie', $validated['serie'])
                               ->exists();
        $conductorRepetido = !empty($validated['conductor_nombre']) &&
                            $hecho->vehiculos()
                                  ->whereHas('conductores', function($q) use ($validated) {
                                      $q->where('nombre', $validated['conductor_nombre']);
                                  })->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida)     $errors['placas']           = 'Ya existe un vehículo con estas placas en este hecho.';
            if ($serieRepetida)     $errors['serie']            = 'Ya existe un vehículo con esta serie en este hecho.';
            if ($conductorRepetido) $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            return back()->withErrors($errors)->withInput();
        }

        // 4) Normalizaciones adicionales de formato
        $validated['marca']                       = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                        = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                       = ucfirst(strtolower($validated['linea']));
        $validated['color']                       = ucfirst(strtolower($validated['color']));
        $validated['estado_placas']               = strtoupper($validated['estado_placas'] ?? '');
        $validated['tipo_servicio']               = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre']  = strtoupper($validated['tarjeta_circulacion_nombre'] ?? '');
        $validated['grua']                        = strtoupper($validated['grua'] ?? '');
        $validated['corralon']                    = strtoupper($validated['corralon'] ?? '');
        $validated['aseguradora']                 = strtoupper($validated['aseguradora'] ?? '');
        $validated['partes_danadas']              = strtoupper($validated['partes_danadas']);

        // 5) Procesar foto (descomenta si la usas)
        /*
        if ($request->hasFile('fotos') && $request->file('fotos')->isValid()) {
            $validated['fotos'] = $request->file('fotos')->store('vehiculos', 'public');
        }
        */

        // 6) Crear y asociar Vehículo
        $vehiculo = Vehiculo::create([
            'marca'                     => $validated['marca'],
            'modelo'                    => $validated['modelo'] ? strtoupper($validated['modelo']) : null,
            'tipo'                      => $validated['tipo'],
            'linea'                     => $validated['linea'],
            'color'                     => $validated['color'],
            'placas'                    => $validated['placas'],
            'estado_placas'             => $validated['estado_placas'],
            'serie'                     => $validated['serie'],
            'capacidad_personas'        => $validated['capacidad_personas'],
            'tipo_servicio'             => $validated['tipo_servicio'],
            'tarjeta_circulacion_nombre'=> $validated['tarjeta_circulacion_nombre'],
            'grua_id'                   => $validated['grua_id'],
            'corralon'                  => $validated['corralon'],
            'aseguradora'               => $validated['aseguradora'],
            'monto_danos'               => $validated['monto_danos'],
            'partes_danadas'            => $validated['partes_danadas'],
            'antecedente_vehiculo'      => $validated['antecedente_vehiculo'],
            // 'fotos'                  => $validated['fotos'] ?? null,
        ]);
        $hecho->vehiculos()->attach($vehiculo->id);

        // 7) Registrar servicio de grúa
        if (!empty($validated['grua_id'])) {
            DB::table('servicios')->insert([
                'vehiculo_id'   => $vehiculo->id,
                'grua_id'       => $validated['grua_id'],
                'tipo_vehiculo' => $validated['tipo'],
                'aseguradora'   => $validated['aseguradora'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }


        // 8) Crear Conductor y asociar (si hay datos)
        if (!empty($validated['conductor_nombre']) || !empty($validated['telefono']) || !empty($validated['domicilio'])) {
            $conductor = Conductor::create([
                'nombre'                   => $validated['conductor_nombre'],
                'telefono'                 => $validated['telefono'] ?? null,
                'domicilio'                => strtoupper($validated['domicilio'] ?? ''),
                'sexo'                     => strtoupper($validated['sexo'] ?? ''),
                'ocupacion'                => strtoupper($validated['ocupacion'] ?? ''),
                'edad'                     => $validated['edad'] ?? null,
                'tipo_licencia'            => strtoupper($validated['tipo_licencia'] ?? ''),
                'estado_licencia'          => strtoupper($validated['estado_licencia'] ?? ''),
                'vigencia_licencia'        => $validated['permanente'] ? null : $validated['vigencia_licencia'],
                'numero_licencia'          => strtoupper($validated['numero_licencia'] ?? ''),
                'permanente'               => $validated['permanente'],
                'cinturon'                 => $validated['cinturon'],
                'antecedentes'             => $validated['antecedente_conductor'],
                'certificado_lesiones'     => $validated['certificado_lesiones'],
                'certificado_alcoholemia'  => $validated['certificado_alcoholemia'],
                'aliento_etilico'          => $validated['aliento_etilico'],
            ]);
            $vehiculo->conductores()->attach($conductor->id);
        }

        // 9) Actualizar daños en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales'] ?? ''),
            'propiedades_afectadas'     => strtoupper($validated['propiedad'] ?? ''),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
        ]);

        // 10) Redireccionar con mensaje de éxito
        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo agregado exitosamente.');
    }

    public function edit(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos->contains($vehiculo->id)) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        $conductor = $vehiculo->conductores()->first();
        $gruas = Grua::orderBy('nombre')->get();

        return view('vehiculos.edit', compact('hecho', 'vehiculo', 'conductor', 'gruas'));
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        // 1) Validación básica, incluyendo grua_id en lugar de grua
        $validated = $request->validate([
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'nullable|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'nullable|string|max:50',
            'color'                      => 'nullable|string|max:30',
            'placas'                     => 'required|string|max:15',
            'estado_placas'              => 'nullable|string|max:15',
            'serie'                      => 'nullable|string|max:17',
            'capacidad_personas'         => 'required|integer|min:0',
            'tipo_servicio'              => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'grua_id'                    => 'nullable|exists:gruas,id',
            'corralon'                   => 'nullable|string|max:50',
            'aseguradora'                => 'nullable|string|max:100',
            'monto_danos'                => 'required|numeric|min:0',
            'partes_danadas'             => 'required|string',
            'antecedente_vehiculo'       => 'sometimes|boolean',
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
            'permanente'                 => 'sometimes|boolean',
            'cinturon'                   => 'sometimes|boolean',
            'antecedente_conductor'      => 'sometimes|boolean',
            'certificado_lesiones'       => 'sometimes|boolean',
            'certificado_alcoholemia'    => 'sometimes|boolean',
            'aliento_etilico'            => 'sometimes|boolean',
            'danos_patrimoniales'        => 'nullable|string',
            'propiedad'                  => 'nullable|string|max:255',
            'monto_danos_patrimoniales'  => 'nullable|numeric|min:0',
        ]);

        // 2) Normalizar antes de validación de duplicados
        $validated['placas']           = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']            = strtoupper(str_replace('-', '', $validated['serie'] ?? ''));
        $validated['conductor_nombre'] = strtoupper($validated['conductor_nombre'] ?? '');

        // 3) Mapear checkboxes
        $validated['antecedente_vehiculo']   = $request->has('antecedente_vehiculo');
        $validated['cinturon']               = $request->has('cinturon');
        $validated['antecedente_conductor']  = $request->has('antecedente_conductor');
        $validated['certificado_lesiones']   = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia']= $request->has('certificado_alcoholemia');
        $validated['aliento_etilico']        = $request->has('aliento_etilico');
        $validated['permanente']             = $request->has('permanente');

        // 4) Validar duplicados (salvando el vehículo actual)
        $placaRepetida = $hecho->vehiculos()
                              ->where('placas', $validated['placas'])
                              ->where('vehiculos.id', '!=', $vehiculo->id)
                              ->exists();
        $serieRepetida = !empty($validated['serie']) &&
                         $hecho->vehiculos()
                               ->where('serie', $validated['serie'])
                               ->where('vehiculos.id', '!=', $vehiculo->id)
                               ->exists();
        $conductorRepetido = !empty($validated['conductor_nombre']) &&
                            $hecho->vehiculos()
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

        // 5) Formateos finales
        $validated['marca']                      = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                       = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                      = ucfirst(strtolower($validated['linea']));
        $validated['color']                      = ucfirst(strtolower($validated['color']));
        $validated['estado_placas']              = strtoupper($validated['estado_placas'] ?? '');
        $validated['tipo_servicio']              = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper($validated['tarjeta_circulacion_nombre'] ?? '');
        $validated['corralon']                   = strtoupper($validated['corralon'] ?? '');
        $validated['aseguradora']                = strtoupper($validated['aseguradora'] ?? '');
        $validated['partes_danadas']             = strtoupper($validated['partes_danadas']);

        // 6) (Opcional) Foto...

        // 7) Actualizar Vehículo con grua_id
        $vehiculo->update([
            'marca'                     => $validated['marca'],
            'modelo'                    => $validated['modelo'] ? strtoupper($validated['modelo']) : null,
            'tipo'                      => $validated['tipo'],
            'linea'                     => $validated['linea'],
            'color'                     => $validated['color'],
            'placas'                    => $validated['placas'],
            'estado_placas'             => $validated['estado_placas'],
            'serie'                     => $validated['serie'],
            'capacidad_personas'        => $validated['capacidad_personas'],
            'tipo_servicio'             => $validated['tipo_servicio'],
            'tarjeta_circulacion_nombre'=> $validated['tarjeta_circulacion_nombre'],
            'grua_id'                   => $validated['grua_id'],
            'corralon'                  => $validated['corralon'],
            'aseguradora'               => $validated['aseguradora'],
            'monto_danos'               => $validated['monto_danos'],
            'partes_danadas'            => $validated['partes_danadas'],
            'antecedente_vehiculo'      => $validated['antecedente_vehiculo'],
            // 'fotos'                  => $validated['fotos'] ?? $vehiculo->fotos,
        ]);

        // 8) Actualizar Conductor
        if ($conductor = $vehiculo->conductores()->first()) {
            $conductor->update([
                'nombre'                   => $validated['conductor_nombre'],
                'telefono'                 => $validated['telefono'] ?? null,
                'domicilio'                => strtoupper($validated['domicilio'] ?? ''),
                'sexo'                     => strtoupper($validated['sexo'] ?? ''),
                'ocupacion'                => strtoupper($validated['ocupacion'] ?? ''),
                'edad'                     => $validated['edad'] ?? null,
                'tipo_licencia'            => strtoupper($validated['tipo_licencia'] ?? ''),
                'estado_licencia'          => strtoupper($validated['estado_licencia'] ?? ''),
                'vigencia_licencia'        => $validated['permanente'] ? null : $validated['vigencia_licencia'],
                'numero_licencia'          => strtoupper($validated['numero_licencia'] ?? ''),
                'permanente'               => $validated['permanente'],
                'cinturon'                 => $validated['cinturon'],
                'antecedentes'             => $validated['antecedente_conductor'],
                'certificado_lesiones'     => $validated['certificado_lesiones'],
                'certificado_alcoholemia'  => $validated['certificado_alcoholemia'],
                'aliento_etilico'          => $validated['aliento_etilico'],
            ]);
        }

       // 9) Actualizar o crear servicio de grúa
    $servicio = DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->first();

    if ($servicio) {
        // Solo actualizar campos
        DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->update([
            'grua_id'       => $validated['grua_id'],
            'tipo_vehiculo' => $validated['tipo'],
            'aseguradora'   => $validated['aseguradora'],
            'updated_at'    => now(),
        ]);
    } else {
        // Insertar nuevo con created_at
        DB::table('servicios')->insert([
            'vehiculo_id'   => $vehiculo->id,
            'grua_id'       => $validated['grua_id'],
            'tipo_vehiculo' => $validated['tipo'],
            'aseguradora'   => $validated['aseguradora'],
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }



        // 10) Actualizar daños en el hecho
        $hecho->update([
            'danos_patrimoniales'       => strtoupper($validated['danos_patrimoniales'] ?? ''),
            'propiedades_afectadas'     => strtoupper($validated['propiedad'] ?? ''),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
        ]);

        // 11) Redirección
        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        $vehiculo->delete();
        return back()->with('success', 'Vehículo eliminado correctamente.');
    }
}
