<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use Illuminate\Support\Facades\DB;
use App\Models\Grua;
use App\Models\Servicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $validated['placas']           = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']            = strtoupper(str_replace('-', '', (string)($validated['serie'] ?? '')));
        $validated['conductor_nombre'] = strtoupper((string)($validated['conductor_nombre'] ?? ''));
        $validated['tarjeta_circulacion_nombre'] = (string)($validated['tarjeta_circulacion_nombre'] ?? '');
        $validated['antecedente_vehiculo']    = $request->has('antecedente_vehiculo');
        $validated['cinturon']                = $request->has('cinturon');
        $validated['antecedente_conductor']   = $request->has('antecedente_conductor');
        $validated['certificado_lesiones']    = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia'] = $request->has('certificado_alcoholemia');
        $validated['aliento_etilico']         = $request->has('aliento_etilico');
        $validated['permanente']              = $request->boolean('permanente');

        $placaRepetida = $hecho->vehiculos()->where('placas', $validated['placas'])->exists();
        $serieRepetida = $validated['serie'] !== '' && $hecho->vehiculos()->where('serie', $validated['serie'])->exists();
        $conductorRepetido = $validated['conductor_nombre'] !== '' && $hecho->vehiculos()->whereHas('conductores', function ($q) use ($validated) {$q->where('nombre', $validated['conductor_nombre']);})->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida)     $errors['placas']           = 'Ya existe un vehículo con estas placas en este hecho.';
            if ($serieRepetida)     $errors['serie']            = 'Ya existe un vehículo con esta serie en este hecho.';
            if ($conductorRepetido) $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            return back()->withErrors($errors)->withInput();
        }

        $validated['marca']                      = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                       = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                      = ucfirst(strtolower($validated['linea']));
        $validated['color']                      = ucfirst(strtolower($validated['color']));
        $validated['estado_placas']              = strtoupper((string)($validated['estado_placas'] ?? ''));
        $validated['tipo_servicio']              = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper($validated['tarjeta_circulacion_nombre']);
        $validated['corralon']                   = strtoupper((string)($validated['corralon'] ?? ''));
        $validated['aseguradora']                = strtoupper((string)($validated['aseguradora'] ?? ''));
        $validated['partes_danadas']             = strtoupper($validated['partes_danadas']);

        $nombreGrua = 'N/A';
        if (!empty($validated['grua_id'])) {
            $tmp = Grua::where('id', $validated['grua_id'])->value('nombre');
            if (!empty($tmp)) {
                $nombreGrua = strtoupper($tmp);
            }
        }

        $vehiculo = Vehiculo::create([
            'marca'                      => $validated['marca'],
            'modelo'                     => $validated['modelo'] ? strtoupper($validated['modelo']) : null,
            'tipo'                       => $validated['tipo'],
            'linea'                      => $validated['linea'],
            'color'                      => $validated['color'],
            'placas'                     => $validated['placas'],
            'estado_placas'              => $validated['estado_placas'],
            'serie'                      => $validated['serie'],
            'capacidad_personas'         => $validated['capacidad_personas'],
            'tipo_servicio'              => $validated['tipo_servicio'],
            'tarjeta_circulacion_nombre' => $validated['tarjeta_circulacion_nombre'],
            'grua'                       => $nombreGrua,
            'corralon'                   => $validated['corralon'] !== '' ? $validated['corralon'] : 'N/A',
            'aseguradora'                => $validated['aseguradora'] !== '' ? $validated['aseguradora'] : null,
            'monto_danos'                => $validated['monto_danos'],
            'partes_danadas'             => $validated['partes_danadas'],
            'antecedente_vehiculo'       => $validated['antecedente_vehiculo'],
            // 'fotos'                   => $validated['fotos'] ?? null,
        ]);

        $hecho->vehiculos()->attach($vehiculo->id);

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

        if (
            !empty($validated['conductor_nombre']) ||
            !empty($validated['telefono']) ||
            !empty($validated['domicilio'])
        ) {
            $conductor = Conductor::create([
                'nombre'                  => $validated['conductor_nombre'],
                'telefono'                => $validated['telefono'] ?? null,
                'domicilio'               => strtoupper((string)($validated['domicilio'] ?? '')),
                'sexo'                    => strtoupper((string)($validated['sexo'] ?? '')),
                'ocupacion'               => strtoupper((string)($validated['ocupacion'] ?? '')),
                'edad'                    => $validated['edad'] ?? null,
                'tipo_licencia'           => strtoupper((string)($validated['tipo_licencia'] ?? '')),
                'estado_licencia'         => strtoupper((string)($validated['estado_licencia'] ?? '')),
                'vigencia_licencia'       => $validated['permanente'] ? null : ($validated['vigencia_licencia'] ?? null),
                'numero_licencia'         => strtoupper((string)($validated['numero_licencia'] ?? '')),
                'permanente'              => $validated['permanente'],
                'cinturon'                => $validated['cinturon'],
                'antecedentes'            => $validated['antecedente_conductor'],
                'certificado_lesiones'    => $validated['certificado_lesiones'],
                'certificado_alcoholemia' => $validated['certificado_alcoholemia'],
                'aliento_etilico'         => $validated['aliento_etilico'],
            ]);

            $vehiculo->conductores()->attach($conductor->id);
        }

        $hecho->update([
            'danos_patrimoniales'       => strtoupper((string)($validated['danos_patrimoniales'] ?? '')),
            'propiedades_afectadas'     => strtoupper((string)($validated['propiedad'] ?? '')),
            'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
        ]);

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
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

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

        $validated['placas']           = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie']            = strtoupper(str_replace('-', '', (string)($validated['serie'] ?? '')));
        $validated['conductor_nombre'] = strtoupper((string)($validated['conductor_nombre'] ?? ''));
        $validated['antecedente_vehiculo']    = $request->has('antecedente_vehiculo');
        $validated['cinturon']                = $request->has('cinturon');
        $validated['antecedente_conductor']   = $request->has('antecedente_conductor');
        $validated['certificado_lesiones']    = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia'] = $request->has('certificado_alcoholemia');
        $validated['aliento_etilico']         = $request->has('aliento_etilico');
        $validated['permanente']              = $request->boolean('permanente');

        $placaRepetida = $hecho->vehiculos()
            ->where('placas', $validated['placas'])
            ->where('vehiculos.id', '!=', $vehiculo->id)
            ->exists();

        $serieRepetida = $validated['serie'] !== '' &&
            $hecho->vehiculos()
                ->where('serie', $validated['serie'])
                ->where('vehiculos.id', '!=', $vehiculo->id)
                ->exists();

        $conductorRepetido = $validated['conductor_nombre'] !== '' &&
            $hecho->vehiculos()
                ->where('vehiculos.id', '!=', $vehiculo->id)
                ->whereHas('conductores', function ($q) use ($validated) {
                    $q->where('nombre', $validated['conductor_nombre']);
                })->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida)     $errors['placas']           = 'Ya existe un vehículo con estas placas en este hecho.';
            if ($serieRepetida)     $errors['serie']            = 'Ya existe un vehículo con esta serie en este hecho.';
            if ($conductorRepetido) $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            return back()->withErrors($errors)->withInput();
        }

        $validated['marca']                      = ucfirst(strtolower($validated['marca']));
        $validated['tipo']                       = ucfirst(strtolower($validated['tipo']));
        $validated['linea']                      = ucfirst(strtolower((string)($validated['linea'] ?? '')));
        $validated['color']                      = ucfirst(strtolower((string)($validated['color'] ?? '')));
        $validated['estado_placas']              = strtoupper((string)($validated['estado_placas'] ?? ''));
        $validated['tipo_servicio']              = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper((string)($validated['tarjeta_circulacion_nombre'] ?? ''));
        $validated['corralon']                   = strtoupper((string)($validated['corralon'] ?? ''));
        $validated['aseguradora']                = strtoupper((string)($validated['aseguradora'] ?? ''));
        $validated['partes_danadas']             = strtoupper($validated['partes_danadas']);

        $nombreGrua = 'N/A';
        if (!empty($validated['grua_id'])) {
            $tmp = Grua::where('id', $validated['grua_id'])->value('nombre');
            if (!empty($tmp)) {
                $nombreGrua = strtoupper($tmp);
            }
        }

        $fechaServicio = null;
        if (!empty($hecho->fecha)) {
            $hora = !empty($hecho->hora) ? $hecho->hora : '12:00:00';
            $fechaServicio = $hecho->fecha . ' ' . $hora;
        } else {
            $fechaServicio = now()->format('Y-m-d H:i:s');
        }

        DB::transaction(function () use ($validated, $vehiculo, $hecho, $nombreGrua, $fechaServicio) {

            $vehiculo->update([
                'marca'                      => $validated['marca'],
                'modelo'                     => $validated['modelo'] ? strtoupper($validated['modelo']) : null,
                'tipo'                       => $validated['tipo'],
                'linea'                      => $validated['linea'],
                'color'                      => $validated['color'],
                'placas'                     => $validated['placas'],
                'estado_placas'              => $validated['estado_placas'],
                'serie'                      => $validated['serie'] !== '' ? $validated['serie'] : null,
                'capacidad_personas'         => $validated['capacidad_personas'],
                'tipo_servicio'              => $validated['tipo_servicio'],
                'tarjeta_circulacion_nombre' => $validated['tarjeta_circulacion_nombre'] !== '' ? $validated['tarjeta_circulacion_nombre'] : null,
                'grua'                       => $nombreGrua,
                'corralon'                   => $validated['corralon'] !== '' ? $validated['corralon'] : 'N/A',
                'aseguradora'                => $validated['aseguradora'] !== '' ? $validated['aseguradora'] : null,
                'monto_danos'                => $validated['monto_danos'],
                'partes_danadas'             => $validated['partes_danadas'],
                'antecedente_vehiculo'       => $validated['antecedente_vehiculo'],
            ]);

            $hayDatosConductor = (
                $validated['conductor_nombre'] !== '' ||
                !empty($validated['telefono']) ||
                !empty($validated['domicilio'])
            );

            $conductor = $vehiculo->conductores()->first();

            if ($conductor) {
                $conductor->update([
                    'nombre'                  => $validated['conductor_nombre'] !== '' ? $validated['conductor_nombre'] : null,
                    'telefono'                => $validated['telefono'] ?? null,
                    'domicilio'               => strtoupper((string)($validated['domicilio'] ?? '')),
                    'sexo'                    => strtoupper((string)($validated['sexo'] ?? '')),
                    'ocupacion'               => strtoupper((string)($validated['ocupacion'] ?? '')),
                    'edad'                    => $validated['edad'] ?? null,
                    'tipo_licencia'           => strtoupper((string)($validated['tipo_licencia'] ?? '')),
                    'estado_licencia'         => strtoupper((string)($validated['estado_licencia'] ?? '')),
                    'vigencia_licencia'       => $validated['permanente'] ? null : ($validated['vigencia_licencia'] ?? null),
                    'numero_licencia'         => strtoupper((string)($validated['numero_licencia'] ?? '')),
                    'permanente'              => $validated['permanente'],
                    'cinturon'                => $validated['cinturon'],
                    'antecedentes'            => $validated['antecedente_conductor'],
                    'certificado_lesiones'    => $validated['certificado_lesiones'],
                    'certificado_alcoholemia' => $validated['certificado_alcoholemia'],
                    'aliento_etilico'         => $validated['aliento_etilico'],
                ]);
            } elseif ($hayDatosConductor) {
                $conductor = Conductor::create([
                    'nombre'                  => $validated['conductor_nombre'],
                    'telefono'                => $validated['telefono'] ?? null,
                    'domicilio'               => strtoupper((string)($validated['domicilio'] ?? '')),
                    'sexo'                    => strtoupper((string)($validated['sexo'] ?? '')),
                    'ocupacion'               => strtoupper((string)($validated['ocupacion'] ?? '')),
                    'edad'                    => $validated['edad'] ?? null,
                    'tipo_licencia'           => strtoupper((string)($validated['tipo_licencia'] ?? '')),
                    'estado_licencia'         => strtoupper((string)($validated['estado_licencia'] ?? '')),
                    'vigencia_licencia'       => $validated['permanente'] ? null : ($validated['vigencia_licencia'] ?? null),
                    'numero_licencia'         => strtoupper((string)($validated['numero_licencia'] ?? '')),
                    'permanente'              => $validated['permanente'],
                    'cinturon'                => $validated['cinturon'],
                    'antecedentes'            => $validated['antecedente_conductor'],
                    'certificado_lesiones'    => $validated['certificado_lesiones'],
                    'certificado_alcoholemia' => $validated['certificado_alcoholemia'],
                    'aliento_etilico'         => $validated['aliento_etilico'],
                ]);

                $vehiculo->conductores()->attach($conductor->id);
            }

            $servicio = DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->first();

            if (!empty($validated['grua_id'])) {

                if ($servicio) {
                    DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->update([
                        'grua_id'       => $validated['grua_id'],
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora'   => $validated['aseguradora'],
                        'created_at'    => $fechaServicio,
                        'updated_at'    => now(),
                    ]);
                } else {
                    DB::table('servicios')->insert([
                        'vehiculo_id'   => $vehiculo->id,
                        'grua_id'       => $validated['grua_id'],
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora'   => $validated['aseguradora'],
                        'created_at'    => $fechaServicio,
                        'updated_at'    => now(),
                    ]);
                }

            } else {
                if ($servicio) {
                    DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();
                }
            }

            $hecho->update([
                'danos_patrimoniales'       => strtoupper((string)($validated['danos_patrimoniales'] ?? '')),
                'propiedades_afectadas'     => strtoupper((string)($validated['propiedad'] ?? '')),
                'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
            ]);
        });

        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
            Storage::disk('public')->delete($vehiculo->fotos);
        }

        foreach ($vehiculo->conductores as $conductor) {
            $vehiculo->conductores()->detach($conductor->id);
            $conductor->delete();
        }

        $vehiculo->hechos()->detach();
        $vehiculo->delete();

        return back()->with('success', 'Vehículo y conductor(es) eliminados correctamente.');
    }

    public function foto(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        return view('vehiculos.foto', compact('hecho', 'vehiculo'));
    }

    public function fotoUpdate(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        $validated = $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
            Storage::disk('public')->delete($vehiculo->fotos);
        }

        $path = $request->file('foto')->store('vehiculos', 'public');
        $vehiculo->update([
            'fotos' => $path,
        ]);

        return redirect()
            ->route('vehiculos.foto', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id])
            ->with('success', 'Foto guardada correctamente.');
    }

    public function fotoDestroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
            Storage::disk('public')->delete($vehiculo->fotos);
        }

        $vehiculo->update([
            'fotos' => null,
        ]);

        return redirect()
            ->route('vehiculos.foto', ['hecho' => $hecho->id, 'vehiculo' => $vehiculo->id])
            ->with('success', 'Foto eliminada correctamente.');
    }
}
