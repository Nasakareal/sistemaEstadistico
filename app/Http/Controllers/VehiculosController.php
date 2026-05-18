<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use Illuminate\Support\Facades\DB;
use App\Models\Grua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Support\GruaEditGuard;

class VehiculosController extends Controller
{
    public function index(Hechos $hecho)
    {
        $vehiculos = $hecho->vehiculos;
        $gruaBloqueada = GruaEditGuard::locksHecho(Auth::user(), $hecho);

        return view('vehiculos.index', compact('hecho', 'vehiculos', 'gruaBloqueada'));
    }

    public function create(Hechos $hecho)
    {
        $usuario = Auth::user();

        $queryGruas = Grua::query()->orderBy('nombre');

        if (!GruaEditGuard::canViewFullGruaCatalog($usuario)) {
            if ((int) $usuario->unidad_id === 1) {
                $queryGruas->whereHas('unidades', function ($q) {
                    $q->where('unidades.id', 1);
                });
            } elseif ((int) $usuario->unidad_id === 2) {
                $delegacionIds = [];

                if (!empty($usuario->delegacion_id)) {
                    $delegacionIds[] = (int) $usuario->delegacion_id;
                }

                $idsPivot = DB::table('delegacion_user')
                    ->where('user_id', $usuario->id)
                    ->pluck('delegacion_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->toArray();

                $delegacionIds = array_values(array_unique(array_merge($delegacionIds, $idsPivot)));

                if (empty($delegacionIds)) {
                    $queryGruas->whereRaw('1 = 0');
                } else {
                    $queryGruas->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                        $q->whereIn('delegaciones.id', $delegacionIds);
                    });
                }
            } else {
                $queryGruas->whereRaw('1 = 0');
            }
        }

        $gruas = $queryGruas->get();
        $corralones = $this->corralonesDesdeGruas($gruas);

        return view('vehiculos.create', compact('hecho', 'gruas', 'corralones'));
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
            'corralon'                   => 'nullable|string|max:255',
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

        $validated['placas'] = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie'] = strtoupper(str_replace('-', '', (string) ($validated['serie'] ?? '')));
        $validated['conductor_nombre'] = strtoupper((string) ($validated['conductor_nombre'] ?? ''));
        $validated['tarjeta_circulacion_nombre'] = (string) ($validated['tarjeta_circulacion_nombre'] ?? '');
        $validated['antecedente_vehiculo'] = $request->has('antecedente_vehiculo');
        $validated['cinturon'] = $request->has('cinturon');
        $validated['antecedente_conductor'] = $request->has('antecedente_conductor');
        $validated['certificado_lesiones'] = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia'] = $request->has('certificado_alcoholemia');
        $validated['aliento_etilico'] = $request->has('aliento_etilico');
        $validated['permanente'] = $request->boolean('permanente');

        $placaRepetida = $hecho->vehiculos()->where('placas', $validated['placas'])->exists();
        $serieRepetida = $validated['serie'] !== '' && $hecho->vehiculos()->where('serie', $validated['serie'])->exists();
        $conductorRepetido = $validated['conductor_nombre'] !== '' && $hecho->vehiculos()->whereHas('conductores', function ($q) use ($validated) {
            $q->where('nombre', $validated['conductor_nombre']);
        })->exists();

        if ($placaRepetida || $serieRepetida || $conductorRepetido) {
            $errors = [];
            if ($placaRepetida) {
                $errors['placas'] = 'Ya existe un vehículo con estas placas en este hecho.';
            }
            if ($serieRepetida) {
                $errors['serie'] = 'Ya existe un vehículo con esta serie en este hecho.';
            }
            if ($conductorRepetido) {
                $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            }
            return back()->withErrors($errors)->withInput();
        }

        $validated['marca'] = ucfirst(strtolower($validated['marca']));
        $validated['tipo'] = ucfirst(strtolower($validated['tipo']));
        $validated['linea'] = ucfirst(strtolower($validated['linea']));
        $validated['color'] = ucfirst(strtolower($validated['color']));
        $validated['estado_placas'] = strtoupper((string) ($validated['estado_placas'] ?? ''));
        $validated['tipo_servicio'] = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper($validated['tarjeta_circulacion_nombre']);
        $validated['corralon'] = strtoupper((string) ($validated['corralon'] ?? ''));
        $validated['aseguradora'] = strtoupper((string) ($validated['aseguradora'] ?? ''));
        $validated['partes_danadas'] = strtoupper($validated['partes_danadas']);

        $nombreGrua = 'N/A';
        $gruaSeleccionada = null;
        if (!empty($validated['grua_id'])) {
            $gruaSeleccionada = Grua::find($validated['grua_id']);

            if ($gruaSeleccionada && !empty($gruaSeleccionada->nombre)) {
                $nombreGrua = strtoupper($gruaSeleccionada->nombre);
            }

            if ($validated['corralon'] === '' && $gruaSeleccionada && !empty($gruaSeleccionada->ubicacion_corralon)) {
                $validated['corralon'] = strtoupper($gruaSeleccionada->ubicacion_corralon);
            }
        }

        DB::transaction(function () use ($validated, $hecho, $nombreGrua) {
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
                'grua_id'                    => $validated['grua_id'] ?? null,
                'corralon'                   => $validated['corralon'] !== '' ? $validated['corralon'] : 'N/A',
                'aseguradora'                => $validated['aseguradora'] !== '' ? $validated['aseguradora'] : null,
                'monto_danos'                => $validated['monto_danos'],
                'partes_danadas'             => $validated['partes_danadas'],
                'antecedente_vehiculo'       => $validated['antecedente_vehiculo'],
            ]);

            $hecho->vehiculos()->attach($vehiculo->id);

            $fechaServicio = now()->format('Y-m-d H:i:s');

            if (!empty($hecho->fecha)) {
                $fechaBase = \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d');
                $horaBase = !empty($hecho->hora)
                    ? \Carbon\Carbon::parse($hecho->hora)->format('H:i:s')
                    : '12:00:00';

                $fechaServicio = $fechaBase . ' ' . $horaBase;
            }

            if (!empty($validated['grua_id'])) {
                $unidadId = 1;
                $delegacionId = null;

                if (!empty($hecho->delegacion_id)) {
                    $unidadId = 2;
                    $delegacionId = $hecho->delegacion_id;
                }

                DB::table('servicios')->insert([
                    'vehiculo_id'   => $vehiculo->id,
                    'grua_id'       => $validated['grua_id'],
                    'unidad_id'     => $unidadId,
                    'delegacion_id' => $delegacionId,
                    'tipo_vehiculo' => $validated['tipo'],
                    'aseguradora'   => $validated['aseguradora'],
                    'created_at'    => $fechaServicio,
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
                    'domicilio'               => strtoupper((string) ($validated['domicilio'] ?? '')),
                    'sexo'                    => strtoupper((string) ($validated['sexo'] ?? '')),
                    'ocupacion'               => strtoupper((string) ($validated['ocupacion'] ?? '')),
                    'edad'                    => $validated['edad'] ?? null,
                    'tipo_licencia'           => strtoupper((string) ($validated['tipo_licencia'] ?? '')),
                    'estado_licencia'         => strtoupper((string) ($validated['estado_licencia'] ?? '')),
                    'vigencia_licencia'       => $validated['permanente'] ? null : ($validated['vigencia_licencia'] ?? null),
                    'numero_licencia'         => strtoupper((string) ($validated['numero_licencia'] ?? '')),
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
                'danos_patrimoniales'       => strtoupper((string) ($validated['danos_patrimoniales'] ?? '')),
                'propiedades_afectadas'     => strtoupper((string) ($validated['propiedad'] ?? '')),
                'monto_danos_patrimoniales' => $validated['monto_danos_patrimoniales'] ?? null,
            ]);

            $hecho->actualizarEstadoCaptura();
        });

        return redirect()
            ->route('vehiculos.index', $hecho->id)
            ->with('success', 'Vehículo agregado exitosamente.');
    }

    public function edit(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos->contains($vehiculo->id)) {
            abort(404, 'El vehículo no pertenece a este hecho.');
        }

        $usuario = Auth::user();
        $conductor = $vehiculo->conductores()->first();

        $queryGruas = Grua::query()->orderBy('nombre');

        if (!GruaEditGuard::canViewFullGruaCatalog($usuario)) {
            if ((int) $usuario->unidad_id === 1) {
                $queryGruas->whereHas('unidades', function ($q) {
                    $q->where('unidades.id', 1);
                });
            } elseif ((int) $usuario->unidad_id === 2) {
                $delegacionIds = [];

                if (!empty($usuario->delegacion_id)) {
                    $delegacionIds[] = (int) $usuario->delegacion_id;
                }

                $idsPivot = DB::table('delegacion_user')
                    ->where('user_id', $usuario->id)
                    ->pluck('delegacion_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->toArray();

                $delegacionIds = array_values(array_unique(array_merge($delegacionIds, $idsPivot)));

                if (empty($delegacionIds)) {
                    $queryGruas->whereRaw('1 = 0');
                } else {
                    $queryGruas->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                        $q->whereIn('delegaciones.id', $delegacionIds);
                    });
                }
            } else {
                $queryGruas->whereRaw('1 = 0');
            }
        }

        $gruas = $queryGruas->get();
        $servicioActual = $vehiculo->servicio;
        $gruaActualId = optional($servicioActual)->grua_id;
        $gruaBloqueada = GruaEditGuard::locksHecho($usuario, $hecho);
        $gruaCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasGrua($vehiculo);
        $corralonCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasCorralon($vehiculo);

        if (!$gruaActualId && !empty($vehiculo->grua) && strtoupper(trim($vehiculo->grua)) !== 'N/A') {
            $gruaActualId = optional($gruas->first(function ($grua) use ($vehiculo) {
                return strtoupper(trim($grua->nombre)) === strtoupper(trim($vehiculo->grua));
            }))->id;
        }

        if ($gruaActualId && !$gruas->contains('id', (int) $gruaActualId)) {
            $gruaActual = Grua::find($gruaActualId);

            if ($gruaActual) {
                $gruas = $gruas->push($gruaActual)->sortBy('nombre')->values();
            }
        }

        $corralones = $this->corralonesDesdeGruas($gruas, $vehiculo->corralon);

        return view('vehiculos.edit', compact(
            'hecho',
            'vehiculo',
            'conductor',
            'gruas',
            'corralones',
            'servicioActual',
            'gruaActualId',
            'gruaBloqueada',
            'gruaCampoBloqueado',
            'corralonCampoBloqueado'
        ));
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
            'corralon'                   => 'nullable|string|max:255',
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

        $validated['placas'] = strtoupper(str_replace('-', '', $validated['placas']));
        $validated['serie'] = strtoupper(str_replace('-', '', (string)($validated['serie'] ?? '')));
        $validated['conductor_nombre'] = strtoupper((string)($validated['conductor_nombre'] ?? ''));
        $validated['antecedente_vehiculo'] = $request->has('antecedente_vehiculo');
        $validated['cinturon'] = $request->has('cinturon');
        $validated['antecedente_conductor'] = $request->has('antecedente_conductor');
        $validated['certificado_lesiones'] = $request->has('certificado_lesiones');
        $validated['certificado_alcoholemia'] = $request->has('certificado_alcoholemia');
        $validated['aliento_etilico'] = $request->has('aliento_etilico');
        $validated['permanente'] = $request->boolean('permanente');

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
            if ($placaRepetida) {
                $errors['placas'] = 'Ya existe un vehículo con estas placas en este hecho.';
            }
            if ($serieRepetida) {
                $errors['serie'] = 'Ya existe un vehículo con esta serie en este hecho.';
            }
            if ($conductorRepetido) {
                $errors['conductor_nombre'] = 'Este conductor ya está registrado en este hecho.';
            }
            return back()->withErrors($errors)->withInput();
        }

        $gruaBloqueada = GruaEditGuard::locksHecho($request->user(), $hecho);
        $gruaCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasGrua($vehiculo);
        $corralonCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasCorralon($vehiculo);
        $erroresGruaBloqueada = $gruaBloqueada
            ? $this->erroresCambioGruaBloqueada($request, $vehiculo)
            : [];

        if (!empty($erroresGruaBloqueada)) {
            return back()->withErrors($erroresGruaBloqueada)->withInput();
        }

        $validated['marca'] = ucfirst(strtolower($validated['marca']));
        $validated['tipo'] = ucfirst(strtolower($validated['tipo']));
        $validated['linea'] = ucfirst(strtolower((string)($validated['linea'] ?? '')));
        $validated['color'] = ucfirst(strtolower((string)($validated['color'] ?? '')));
        $validated['estado_placas'] = strtoupper((string)($validated['estado_placas'] ?? ''));
        $validated['tipo_servicio'] = strtoupper($validated['tipo_servicio']);
        $validated['tarjeta_circulacion_nombre'] = strtoupper((string)($validated['tarjeta_circulacion_nombre'] ?? ''));
        $validated['corralon'] = strtoupper((string)($validated['corralon'] ?? ''));
        $validated['aseguradora'] = strtoupper((string)($validated['aseguradora'] ?? ''));
        $validated['partes_danadas'] = strtoupper($validated['partes_danadas']);

        $nombreGrua = 'N/A';
        $gruaSeleccionada = null;
        if (!empty($validated['grua_id'])) {
            $gruaSeleccionada = Grua::find($validated['grua_id']);

            if ($gruaSeleccionada && !empty($gruaSeleccionada->nombre)) {
                $nombreGrua = strtoupper($gruaSeleccionada->nombre);
            }

            if ($validated['corralon'] === '' && $gruaSeleccionada && !empty($gruaSeleccionada->ubicacion_corralon)) {
                $validated['corralon'] = strtoupper($gruaSeleccionada->ubicacion_corralon);
            }
        }

        $fechaServicio = now()->format('Y-m-d H:i:s');

        if (!empty($hecho->fecha)) {
            $fechaBase = \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d');
            $horaBase = !empty($hecho->hora)
                ? \Carbon\Carbon::parse($hecho->hora)->format('H:i:s')
                : '12:00:00';

            $fechaServicio = $fechaBase . ' ' . $horaBase;
        }

        DB::transaction(function () use ($validated, $vehiculo, $hecho, $nombreGrua, $fechaServicio, $gruaCampoBloqueado, $corralonCampoBloqueado) {
            $vehiculoPayload = [
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
                'grua_id'                    => $validated['grua_id'] ?? null,
                'corralon'                   => $validated['corralon'] !== '' ? $validated['corralon'] : 'N/A',
                'aseguradora'                => $validated['aseguradora'] !== '' ? $validated['aseguradora'] : null,
                'monto_danos'                => $validated['monto_danos'],
                'partes_danadas'             => $validated['partes_danadas'],
                'antecedente_vehiculo'       => $validated['antecedente_vehiculo'],
            ];

            if ($gruaCampoBloqueado) {
                unset($vehiculoPayload['grua'], $vehiculoPayload['grua_id']);
            }

            if ($corralonCampoBloqueado) {
                unset($vehiculoPayload['corralon']);
            }

            $vehiculo->update($vehiculoPayload);

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

            if ($gruaCampoBloqueado) {
                if ($servicio) {
                    DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->update([
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora'   => $validated['aseguradora'],
                        'updated_at'    => now(),
                    ]);
                }
            } elseif (!empty($validated['grua_id'])) {
                $unidadId = 1;
                $delegacionId = null;

                if (!empty($hecho->delegacion_id)) {
                    $unidadId = 2;
                    $delegacionId = $hecho->delegacion_id;
                }

                if ($servicio) {
                    DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->update([
                        'grua_id'       => $validated['grua_id'],
                        'unidad_id'     => $unidadId,
                        'delegacion_id' => $delegacionId,
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora'   => $validated['aseguradora'],
                        'created_at'    => $fechaServicio,
                        'updated_at'    => now(),
                    ]);
                } else {
                    DB::table('servicios')->insert([
                        'vehiculo_id'   => $vehiculo->id,
                        'grua_id'       => $validated['grua_id'],
                        'unidad_id'     => $unidadId,
                        'delegacion_id' => $delegacionId,
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

            $hecho->actualizarEstadoCaptura();
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

        if (
            GruaEditGuard::locksHecho(Auth::user(), $hecho)
            && GruaEditGuard::vehicleHasGruaData($vehiculo)
        ) {
            return back()->with('error', 'La grúa o corralón de este vehículo está bloqueado. Solicita autorización de un Administrador.');
        }

        DB::transaction(function () use ($hecho, $vehiculo) {
            if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
                Storage::disk('public')->delete($vehiculo->fotos);
            }

            foreach ($vehiculo->conductores as $conductor) {
                $vehiculo->conductores()->detach($conductor->id);
                $conductor->delete();
            }

            $vehiculo->hechos()->detach();
            $vehiculo->delete();

            $hecho->actualizarEstadoCaptura();
        });

        return back()->with('success', 'Vehículo y conductor(es) eliminados correctamente.');
    }

    private function erroresCambioGruaBloqueada(Request $request, Vehiculo $vehiculo): array
    {
        $errors = [];

        if ($request->exists('grua_id')) {
            $gruaSolicitadaId = $request->filled('grua_id') ? (int) $request->input('grua_id') : null;

            if (!GruaEditGuard::requestedGruaMatchesCurrent($vehiculo, $gruaSolicitadaId)) {
                $errors['grua_id'] = 'La grúa ya quedó fija. Solicita autorización de un Administrador para cambiarla o quitarla.';
            }
        }

        if ($request->exists('corralon')) {
            $corralonActual = GruaEditGuard::normalizeProtectedText($vehiculo->corralon ?? null);
            $corralonSolicitado = GruaEditGuard::normalizeProtectedText($request->input('corralon'));

            if ($corralonActual !== '' && $corralonActual !== $corralonSolicitado) {
                $errors['corralon'] = 'El corralón ya quedó fijo. Solicita autorización de un Administrador para cambiarlo o quitarlo.';
            }
        }

        return $errors;
    }

    private function corralonesDesdeGruas($gruas, ?string $extra = null)
    {
        return collect($gruas)
            ->map(function ($grua) {
                return trim((string) ($grua->ubicacion_corralon ?: $grua->nombre));
            })
            ->when($extra !== null, function ($corralones) use ($extra) {
                return $corralones->push(trim((string) $extra));
            })
            ->filter(function ($corralon) {
                return GruaEditGuard::normalizeProtectedText($corralon) !== '';
            })
            ->unique(function ($corralon) {
                return GruaEditGuard::normalizeProtectedText($corralon);
            })
            ->sortBy(function ($corralon) {
                return GruaEditGuard::normalizeProtectedText($corralon);
            })
            ->values();
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
