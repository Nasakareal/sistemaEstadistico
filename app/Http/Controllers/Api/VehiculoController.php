<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Grua;
use App\Services\Fotos\HechoFotoStorage;
use App\Support\HechoAccess;
use App\Support\GruaEditGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Throwable;

class VehiculoController extends Controller
{
    public function index(Hechos $hecho)
    {
        try {
            if (!HechoAccess::canView(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para consultar este hecho.', 403);
            }

            return $this->ok('Vehículos del hecho.', $hecho->vehiculos()->with('conductores')->get());
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al cargar los vehículos.', 500);
        }
    }

    public function store(Request $request, ?Hechos $hecho = null)
    {
        try {
            if (!$hecho) {
                if ($request->filled('hecho_client_uuid')) {
                    $hecho = Hechos::where('client_uuid', $request->input('hecho_client_uuid'))->first();
                } elseif ($request->filled('hecho_id')) {
                    $hecho = Hechos::find($request->input('hecho_id'));
                }
            }

            if (!$hecho) {
                return $this->fail('No existe un hecho válido para relacionar el vehículo.', 404);
            }

            if (!HechoAccess::canEdit($request->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            $validated = $this->validateRequest($request);
            $validated = $this->normalize($request, $validated);

            if (!empty($validated['client_uuid'])) {
                $vehiculoExistente = Vehiculo::where('client_uuid', $validated['client_uuid'])->first();

                if ($vehiculoExistente) {
                    if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculoExistente->id)->exists()) {
                        try {
                            $hecho->vehiculos()->attach($vehiculoExistente->id);
                        } catch (QueryException $e) {
                            if (!$this->isDuplicateKey($e)) {
                                throw $e;
                            }
                        }
                    }

                    $hecho->actualizarEstadoCaptura();

                    return response()->json([
                        'ok' => true,
                        'message' => 'Vehículo ya existente.',
                        'data' => $vehiculoExistente->load('conductores'),
                        'meta' => [
                            'id' => $vehiculoExistente->id,
                            'client_uuid' => $vehiculoExistente->client_uuid,
                        ],
                    ], 200);
                }
            }

            $validated['grua'] = 'N/A';
            $gruaSeleccionada = null;
            if (!empty($validated['grua_id'])) {
                $gruaSeleccionada = Grua::find($validated['grua_id']);

                if ($gruaSeleccionada && !empty($gruaSeleccionada->nombre)) {
                    $validated['grua'] = strtoupper($this->removeAccents($gruaSeleccionada->nombre));
                }

                if (empty($validated['corralon']) && $gruaSeleccionada && !empty($gruaSeleccionada->ubicacion_corralon)) {
                    $validated['corralon'] = strtoupper($this->removeAccents($gruaSeleccionada->ubicacion_corralon));
                }
            }

            $dupErrors = $this->duplicadosDentroDelHecho($hecho, $validated);
            if (!empty($dupErrors)) {
                return $this->validationFailed($dupErrors, 'Duplicado dentro del hecho. Revisa los campos marcados.', 409);
            }

            return DB::transaction(function () use ($validated, $hecho) {
                $vehiculo = Vehiculo::create($this->onlyVehiculoForCreate($validated));

                try {
                    $hecho->vehiculos()->attach($vehiculo->id);
                } catch (QueryException $e) {
                    if ($this->isDuplicateKey($e)) {
                        return $this->fail('Ese vehículo ya está registrado dentro de este hecho.', 409);
                    }
                    throw $e;
                }

                if (!empty($validated['grua_id'])) {
                    DB::table('servicios')->insert([
                        'client_uuid' => $validated['servicio_client_uuid'] ?? null,
                        'vehiculo_id' => $vehiculo->id,
                        'grua_id' => $validated['grua_id'],
                        'unidad_id' => $this->servicioUnidadId($hecho),
                        'delegacion_id' => $this->servicioDelegacionId($hecho),
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora' => $validated['aseguradora'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($this->hayDatosConductor($validated)) {
                    $conductor = null;

                    if (!empty($validated['conductor_client_uuid'])) {
                        $conductor = Conductor::where('client_uuid', $validated['conductor_client_uuid'])->first();
                    }

                    if (!$conductor) {
                        $conductor = Conductor::create($this->onlyConductor($validated));
                    }

                    try {
                        $vehiculo->conductores()->attach($conductor->id);
                    } catch (QueryException $e) {
                        if ($this->isDuplicateKey($e)) {
                            return $this->fail('Ese conductor ya está ligado a este vehículo.', 409);
                        }
                        throw $e;
                    }
                }

                $hecho->actualizarEstadoCaptura();

                return response()->json([
                    'ok' => true,
                    'message' => 'Vehículo creado correctamente.',
                    'data' => $vehiculo->load('conductores'),
                    'meta' => [
                        'id' => $vehiculo->id,
                        'client_uuid' => $vehiculo->client_uuid,
                    ],
                ], 201);
            });

        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                return $this->fail('No se pudo guardar: ya existe un registro con esos datos (placas o NIV/serie).', 409);
            }
            return $this->fail('No se pudo guardar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al crear el vehículo.', 500);
        }
    }

    public function show(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canView(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para consultar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            $servicio = DB::table('servicios')
                ->where('vehiculo_id', $vehiculo->id)
                ->first();

            $gruaId = $servicio->grua_id ?? null;

            $gruaNombre = null;
            if (!empty($gruaId)) {
                $gruaNombre = Grua::where('id', $gruaId)->value('nombre');
                $gruaNombre = $gruaNombre ? strtoupper($this->removeAccents($gruaNombre)) : null;
            }

            $data = $vehiculo->load('conductores')->toArray();

            $data['grua_id'] = $gruaId;
            $data['grua_nombre'] = $gruaNombre;
            $data['grua_bloqueada'] = GruaEditGuard::locksHecho(request()->user(), $hecho);
            $data['puede_modificar_grua'] = !$data['grua_bloqueada'];

            return $this->ok('Vehículo encontrado.', $data);

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar el vehículo.', 500);
        }
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit($request->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            $validated = $this->validateRequest($request, $vehiculo->id);
            $validated = $this->normalize($request, $validated);
            $gruaBloqueada = GruaEditGuard::locksHecho($request->user(), $hecho);
            $gruaCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasGrua($vehiculo);
            $corralonCampoBloqueado = $gruaBloqueada && GruaEditGuard::vehicleHasCorralon($vehiculo);

            if ($gruaBloqueada) {
                $erroresGruaBloqueada = $this->erroresCambioGruaBloqueada($request, $vehiculo);

                if (!empty($erroresGruaBloqueada)) {
                    return $this->validationFailed(
                        $erroresGruaBloqueada,
                        'La grúa o corralón ya quedó fijo. Solicita autorización de un Administrador.',
                        403
                    );
                }
            }

            $validated['grua'] = 'N/A';
            $gruaSeleccionada = null;
            if (!empty($validated['grua_id'])) {
                $gruaSeleccionada = Grua::find($validated['grua_id']);

                if ($gruaSeleccionada && !empty($gruaSeleccionada->nombre)) {
                    $validated['grua'] = strtoupper($this->removeAccents($gruaSeleccionada->nombre));
                }

                if (empty($validated['corralon']) && $gruaSeleccionada && !empty($gruaSeleccionada->ubicacion_corralon)) {
                    $validated['corralon'] = strtoupper($this->removeAccents($gruaSeleccionada->ubicacion_corralon));
                }
            }

            $dupErrors = $this->duplicadosDentroDelHecho($hecho, $validated, $vehiculo->id);
            if (!empty($dupErrors)) {
                return $this->validationFailed($dupErrors, 'Duplicado dentro del hecho. Revisa los campos marcados.', 409);
            }

            return DB::transaction(function () use ($validated, $vehiculo, $hecho, $gruaCampoBloqueado, $corralonCampoBloqueado) {
                $payload = $this->onlyVehiculoForUpdate($validated);

                if ($gruaCampoBloqueado) {
                    unset($payload['grua'], $payload['grua_id']);
                }

                if ($corralonCampoBloqueado) {
                    unset($payload['corralon']);
                }

                $vehiculo->update($payload);

                if ($gruaCampoBloqueado) {
                    if (DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->exists()) {
                        DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->update([
                            'tipo_vehiculo' => $validated['tipo'],
                            'aseguradora'   => $validated['aseguradora'] ?? '',
                            'updated_at'    => now(),
                        ]);
                    }
                } elseif (!empty($validated['grua_id'])) {
                    DB::table('servicios')->updateOrInsert(
                        ['vehiculo_id' => $vehiculo->id],
                        [
                            'grua_id'       => $validated['grua_id'],
                            'unidad_id'     => $this->servicioUnidadId($hecho),
                            'delegacion_id' => $this->servicioDelegacionId($hecho),
                            'tipo_vehiculo' => $validated['tipo'],
                            'aseguradora'   => $validated['aseguradora'] ?? '',
                            'updated_at'    => now(),
                            'created_at'    => now(),
                        ]
                    );
                } else {
                    DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();
                }

                if ($this->hayDatosConductor($validated)) {
                    $conductor = $vehiculo->conductores()->first();

                    if ($conductor) {
                        $conductor->update($this->onlyConductor($validated));
                    } else {
                        $conductor = Conductor::create($this->onlyConductor($validated));

                        try {
                            $vehiculo->conductores()->attach($conductor->id);
                        } catch (QueryException $e) {
                            if ($this->isDuplicateKey($e)) {
                                return $this->fail('Ese conductor ya está ligado a este vehículo.', 409);
                            }
                            throw $e;
                        }
                    }
                }

                $hecho->actualizarEstadoCaptura();

                return $this->ok('Vehículo actualizado correctamente.', $vehiculo->fresh()->load('conductores'));
            });

        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                return $this->fail('No se pudo actualizar: ya existe un registro con esos datos (placas o NIV/serie).', 409);
            }
            return $this->fail('No se pudo actualizar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al actualizar el vehículo.', 500);
        }
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            if (
                GruaEditGuard::locksHecho(request()->user(), $hecho)
                && GruaEditGuard::vehicleHasGruaData($vehiculo)
            ) {
                return $this->fail('La grúa o corralón de este vehículo está bloqueado. Solicita autorización de un Administrador.', 403);
            }

            return DB::transaction(function () use ($hecho, $vehiculo) {

                app(HechoFotoStorage::class)->delete($vehiculo->fotos);

                $hecho->vehiculos()->detach($vehiculo->id);

                DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();

                $vehiculo->conductores()->detach();
                $vehiculo->delete();

                $hecho->actualizarEstadoCaptura();

                return $this->ok('Vehículo eliminado correctamente.', null);
            });

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al eliminar el vehículo.', 500);
        }
    }

    public function foto(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return $this->ok('Foto del vehículo.', [
                'vehiculo_id' => $vehiculo->id,
                'fotos'       => $vehiculo->fotos,
                'url'         => $this->hechoFotoUrl($vehiculo->fotos),
            ]);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar la foto.', 500);
        }
    }

    public function fotoUpdate(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit($request->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            $data = $this->sanitize($request->all());

            $validator = Validator::make(
                $data,
                ['foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'],
                [
                    'foto.required' => 'Debes seleccionar una imagen.',
                    'foto.image'    => 'El archivo debe ser una imagen válida.',
                    'foto.mimes'    => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
                    'foto.max'      => 'La imagen no debe pesar más de 2 MB.',
                ],
                ['foto' => 'foto']
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray(), 'Datos inválidos. Revisa la foto.');
            }

            return DB::transaction(function () use ($request, $hecho, $vehiculo) {
                $fotoStorage = app(HechoFotoStorage::class);
                $oldPath = $vehiculo->fotos;
                $path = $fotoStorage->putUploadedFile($request->file('foto'), $hecho, 'vehiculo', $vehiculo);

                $vehiculo->update([
                    'fotos' => $path,
                ]);

                if ($oldPath && $oldPath !== $path) {
                    $fotoStorage->delete($oldPath);
                }

                return $this->created('Foto guardada correctamente.', [
                    'vehiculo_id' => $vehiculo->id,
                    'fotos'       => $vehiculo->fotos,
                    'url'         => $this->hechoFotoUrl($vehiculo->fotos),
                ]);
            });

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al guardar la foto.', 500);
        }
    }

    public function fotoDestroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return DB::transaction(function () use ($vehiculo) {
                app(HechoFotoStorage::class)->delete($vehiculo->fotos);

                $vehiculo->update([
                    'fotos' => null,
                ]);

                return $this->ok('Foto eliminada correctamente.', [
                    'vehiculo_id' => $vehiculo->id,
                    'fotos'       => null,
                    'url'         => null,
                ]);
            });

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al eliminar la foto.', 500);
        }
    }

    public function inventarioGrua(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return $this->ok('Inventario de grúa.', [
                'vehiculo_id' => $vehiculo->id,
                'numero_inventario_grua' => $vehiculo->numero_inventario_grua,
                'foto_inventario_grua' => $vehiculo->foto_inventario_grua,
                'url' => $this->hechoFotoUrl($vehiculo->foto_inventario_grua),
                'fecha_inventario_grua' => $vehiculo->fecha_inventario_grua,
            ]);

        } catch (Throwable $e) {
            return $this->fail('Error al consultar inventario de grúa.', 500);
        }
    }

    public function inventarioGruaUpdate(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit($request->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'numero_inventario_grua' => 'required|string|max:100',
                    'foto_inventario_grua' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                ],
                [
                    'numero_inventario_grua.required' => 'Debes capturar el número de inventario.',
                    'foto_inventario_grua.image' => 'Debe ser una imagen válida.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            return DB::transaction(function () use ($request, $hecho, $vehiculo) {

                $data = [
                    'numero_inventario_grua' => strtoupper($this->removeAccents($request->numero_inventario_grua)),
                    'fecha_inventario_grua' => now(),
                ];
                $oldPath = null;

                if ($request->hasFile('foto_inventario_grua')) {
                    $fotoStorage = app(HechoFotoStorage::class);
                    $oldPath = $vehiculo->foto_inventario_grua;

                    $path = $fotoStorage->putUploadedFile($request->file('foto_inventario_grua'), $hecho, 'inventario_grua', $vehiculo);

                    $data['foto_inventario_grua'] = $path;
                }

                $vehiculo->update($data);

                if (!empty($oldPath) && !empty($data['foto_inventario_grua']) && $oldPath !== $data['foto_inventario_grua']) {
                    app(HechoFotoStorage::class)->delete($oldPath);
                }

                return $this->ok('Inventario de grúa guardado correctamente.', [
                    'vehiculo_id' => $vehiculo->id,
                    'numero_inventario_grua' => $vehiculo->numero_inventario_grua,
                    'url' => $this->hechoFotoUrl($vehiculo->foto_inventario_grua),
                ]);
            });

        } catch (Throwable $e) {
            return $this->fail('Error al guardar inventario de grúa.', 500);
        }
    }

    public function inventarioGruaDestroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!HechoAccess::canEdit(request()->user(), $hecho)) {
                return $this->fail('No tienes permiso para editar este hecho.', 403);
            }

            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return DB::transaction(function () use ($vehiculo) {

                app(HechoFotoStorage::class)->delete($vehiculo->foto_inventario_grua);

                $vehiculo->update([
                    'numero_inventario_grua' => null,
                    'foto_inventario_grua' => null,
                    'fecha_inventario_grua' => null,
                ]);

                return $this->ok('Inventario eliminado correctamente.', null);
            });

        } catch (Throwable $e) {
            return $this->fail('Error al eliminar inventario.', 500);
        }
    }

    private function validateRequest(Request $request, ?int $vehiculoId = null): array
    {
        $data = $this->sanitize($request->all());
        $dataForValidation = $data;

        if (isset($dataForValidation['placas']) && is_string($dataForValidation['placas'])) {
            $p = strtoupper($this->removeAccents($dataForValidation['placas']));
            $p = str_replace(['-', ' ', '.', ',', '_'], '', $p);
            $dataForValidation['placas'] = ($p === '') ? null : $p;
        }

        if (array_key_exists('serie', $dataForValidation)) {
            $s = strtoupper($this->removeAccents((string)($dataForValidation['serie'] ?? '')));
            $s = str_replace(['-', ' ', '.', ',', '_'], '', $s);
            $dataForValidation['serie'] = ($s === '') ? null : $s;
        }

        if (isset($dataForValidation['estado_placas']) && is_string($dataForValidation['estado_placas'])) {
            $ep = strtoupper($this->removeAccents($dataForValidation['estado_placas']));
            $ep = str_replace(['.', ',', '-', '_'], '', $ep);
            $ep = preg_replace('/\s+/', ' ', trim($ep));
            $dataForValidation['estado_placas'] = ($ep === '') ? null : $ep;
        }

        if (
            !empty($dataForValidation['placas']) &&
            $this->esServicioPublicoFederal($dataForValidation['tipo_servicio'] ?? null)
        ) {
            $dataForValidation['estado_placas'] = 'FEDERAL';
        }

        $rules = [
            'client_uuid' => 'nullable|string|max:36',
            'hecho_client_uuid' => 'nullable|string|max:36',
            'hecho_id' => 'nullable|integer',
            'conductor_client_uuid' => 'nullable|string|max:36',
            'servicio_client_uuid' => 'nullable|string|max:36',

            'marca' => 'required|string|max:50',
            'modelo' => 'nullable|string|max:10',
            'tipo' => 'required|string|max:50',
            'linea' => 'required|string|max:50',
            'color' => 'required|string|max:30',

            'placas' => ['nullable','string','max:15','regex:/^[A-Z0-9]{5,15}$/'],

            'estado_placas' => [
                'nullable',
                'string',
                'max:15',
                'required_with:placas',
                'regex:/^[A-Z]{3,15}$/'
            ],
            'permiso_circular' => 'nullable|string|max:60',

            'serie' => ['nullable','string','max:17','regex:/^[A-Z0-9]{6,17}$/'],

            'capacidad_personas' => 'required|integer|min:0',
            'tipo_servicio' => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',

            'grua_id' => 'nullable|exists:gruas,id',
            'corralon' => 'nullable|string|max:255',
            'aseguradora' => 'nullable|string|max:100',
            'monto_danos' => 'required|numeric|min:0',
            'partes_danadas' => 'required|string',

            'antecedente_vehiculo' => 'sometimes|boolean',

            'conductor_nombre' => 'nullable|string|max:255',
            'telefono' => 'nullable|digits:10',
            'domicilio' => 'nullable|string|max:255',
            'sexo' => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion' => 'nullable|string|max:255',
            'edad' => 'nullable|integer|min:0|max:100',
            'tipo_licencia' => 'nullable|string|max:50',
            'estado_licencia' => 'nullable|string|max:100',
            'vigencia_licencia' => 'nullable|date',
            'numero_licencia' => 'nullable|string|max:50',

            'permanente' => 'sometimes|boolean',
            'cinturon' => 'sometimes|boolean',
            'antecedente_conductor' => 'sometimes|boolean',
            'certificado_lesiones' => 'sometimes|boolean',
            'certificado_alcoholemia' => 'sometimes|boolean',
            'aliento_etilico' => 'sometimes|boolean',
        ];

        $messages = $this->validationMessages();
        $attributes = $this->validationAttributes();

        $validator = Validator::make($dataForValidation, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $this->throwValidation($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    private function normalize(Request $request, array $data): array
    {
        $upper = [
            'marca','modelo','tipo','linea','color','estado_placas','permiso_circular','tipo_servicio',
            'tarjeta_circulacion_nombre','corralon','aseguradora','partes_danadas',
            'conductor_nombre','domicilio','sexo','ocupacion','tipo_licencia',
            'estado_licencia','numero_licencia'
        ];

        foreach ($upper as $k) {
            if (array_key_exists($k, $data) && is_string($data[$k])) {
                $data[$k] = strtoupper($this->removeAccents($data[$k]));
            }
        }

        if (isset($data['placas'])) {
            $placa = strtoupper($this->removeAccents((string)$data['placas']));
            $placa = str_replace(['-',' ','.',',','_'], '', $placa);
            $data['placas'] = $placa;
        }

        if (isset($data['estado_placas']) && is_string($data['estado_placas'])) {
            $ep = strtoupper($this->removeAccents($data['estado_placas']));
            $ep = str_replace(['.',',','-','_'], '', $ep);
            $ep = preg_replace('/\s+/', ' ', trim($ep));
            $data['estado_placas'] = $ep;
        }

        if (array_key_exists('serie', $data)) {
            $serie = strtoupper($this->removeAccents((string)($data['serie'] ?? '')));
            $serie = str_replace(['-',' ','.',',','_'], '', $serie);
            $data['serie'] = ($serie !== '') ? $serie : null;
        }

        $data['antecedente_vehiculo']    = $request->boolean('antecedente_vehiculo');
        $data['permanente']              = $request->boolean('permanente');
        $data['cinturon']                = $request->boolean('cinturon');
        $data['antecedente_conductor']   = $request->boolean('antecedente_conductor');
        $data['certificado_lesiones']    = $request->boolean('certificado_lesiones');
        $data['certificado_alcoholemia'] = $request->boolean('certificado_alcoholemia');
        $data['aliento_etilico']         = $request->boolean('aliento_etilico');

        return $data;
    }

    private function duplicadosDentroDelHecho(Hechos $hecho, array $v, ?int $ignoreId = null): array
    {
        $errors = [];

        if (!empty($v['placas'])) {
            $q = $hecho->vehiculos()->where('placas', $v['placas']);
            if ($ignoreId) $q->where('vehiculos.id', '!=', $ignoreId);
            if ($q->exists()) {
                $errors['placas'] = ['Ya existe un vehículo con estas placas en este hecho.'];
            }
        }

        if (!empty($v['serie'])) {
            $q = $hecho->vehiculos()->where('serie', $v['serie']);
            if ($ignoreId) $q->where('vehiculos.id', '!=', $ignoreId);
            if ($q->exists()) {
                $errors['serie'] = ['Ya existe un vehículo con este NIV/serie en este hecho.'];
            }
        }

        if (!empty($v['conductor_nombre'])) {
            $q = $hecho->vehiculos();
            if ($ignoreId) $q->where('vehiculos.id', '!=', $ignoreId);

            $exists = $q->whereHas('conductores', function ($qq) use ($v) {
                $qq->where('nombre', $v['conductor_nombre']);
            })->exists();

            if ($exists) {
                $errors['conductor_nombre'] = ['Este conductor ya está registrado en este hecho.'];
            }
        }

        return $errors;
    }

    private function erroresCambioGruaBloqueada(Request $request, Vehiculo $vehiculo): array
    {
        $errors = [];

        if ($request->exists('grua_id')) {
            $gruaSolicitadaId = $request->filled('grua_id') ? (int) $request->input('grua_id') : null;

            if (!GruaEditGuard::requestedGruaMatchesCurrent($vehiculo, $gruaSolicitadaId)) {
                $errors['grua_id'] = ['La grúa ya quedó fija. Solicita autorización de un Administrador para cambiarla o quitarla.'];
            }
        }

        if ($request->exists('corralon')) {
            $corralonActual = GruaEditGuard::normalizeProtectedText($vehiculo->corralon ?? null);
            $corralonSolicitado = GruaEditGuard::normalizeProtectedText($request->input('corralon'));

            if ($corralonActual !== '' && $corralonActual !== $corralonSolicitado) {
                $errors['corralon'] = ['El corralón ya quedó fijo. Solicita autorización de un Administrador para cambiarlo o quitarlo.'];
            }
        }

        return $errors;
    }

    private function hayDatosConductor(array $v): bool
    {
        return !empty($v['conductor_nombre']) || !empty($v['telefono']) || !empty($v['domicilio']);
    }

    private function onlyVehiculoForCreate(array $v): array
    {
        return [
            'client_uuid'                => $v['client_uuid'] ?? null,
            'marca'                      => $v['marca'] ?? null,
            'modelo'                     => $v['modelo'] ?? null,
            'tipo'                       => $v['tipo'] ?? null,
            'linea'                      => $v['linea'] ?? null,
            'color'                      => $v['color'] ?? null,
            'placas'                     => $v['placas'] ?? null,
            'estado_placas'              => $v['estado_placas'] ?? null,
            'permiso_circular'           => $v['permiso_circular'] ?? null,
            'serie'                      => $v['serie'] ?? null,
            'capacidad_personas'         => $v['capacidad_personas'] ?? 0,
            'tipo_servicio'              => $v['tipo_servicio'] ?? null,
            'tarjeta_circulacion_nombre' => $v['tarjeta_circulacion_nombre'] ?? null,
            'grua'                       => $v['grua'] ?? 'N/A',
            'grua_id'                    => $v['grua_id'] ?? null,
            'corralon'                   => $v['corralon'] ?? null,
            'aseguradora'                => $v['aseguradora'] ?? null,
            'monto_danos'                => $v['monto_danos'] ?? 0,
            'partes_danadas'             => $v['partes_danadas'] ?? null,
            'antecedente_vehiculo'       => $v['antecedente_vehiculo'] ?? false,
            'fotos'                      => $v['fotos'] ?? null,
        ];
    }

    private function onlyVehiculoForUpdate(array $v): array
    {
        return [
            'marca'                      => $v['marca'] ?? null,
            'modelo'                     => $v['modelo'] ?? null,
            'tipo'                       => $v['tipo'] ?? null,
            'linea'                      => $v['linea'] ?? null,
            'color'                      => $v['color'] ?? null,
            'placas'                     => $v['placas'] ?? null,
            'estado_placas'              => $v['estado_placas'] ?? null,
            'permiso_circular'           => $v['permiso_circular'] ?? null,
            'serie'                      => $v['serie'] ?? null,
            'capacidad_personas'         => $v['capacidad_personas'] ?? 0,
            'tipo_servicio'              => $v['tipo_servicio'] ?? null,
            'tarjeta_circulacion_nombre' => $v['tarjeta_circulacion_nombre'] ?? null,
            'grua'                       => $v['grua'] ?? 'N/A',
            'grua_id'                    => $v['grua_id'] ?? null,
            'corralon'                   => $v['corralon'] ?? null,
            'aseguradora'                => $v['aseguradora'] ?? null,
            'monto_danos'                => $v['monto_danos'] ?? 0,
            'partes_danadas'             => $v['partes_danadas'] ?? null,
            'antecedente_vehiculo'       => $v['antecedente_vehiculo'] ?? false,
        ];
    }

    private function onlyConductor(array $v): array
    {
        return [
            'client_uuid'              => $v['conductor_client_uuid'] ?? null,
            'nombre'                  => $v['conductor_nombre'] ?? null,
            'telefono'                => $v['telefono'] ?? null,
            'domicilio'               => $v['domicilio'] ?? null,
            'sexo'                    => $v['sexo'] ?? null,
            'ocupacion'               => $v['ocupacion'] ?? null,
            'edad'                    => $v['edad'] ?? null,
            'tipo_licencia'           => $v['tipo_licencia'] ?? null,
            'estado_licencia'         => $v['estado_licencia'] ?? null,
            'vigencia_licencia'       => ($v['permanente'] ?? false) ? null : ($v['vigencia_licencia'] ?? null),
            'numero_licencia'         => $v['numero_licencia'] ?? null,
            'permanente'              => $v['permanente'] ?? false,
            'cinturon'                => $v['cinturon'] ?? false,
            'antecedentes'            => $v['antecedente_conductor'] ?? false,
            'certificado_lesiones'    => $v['certificado_lesiones'] ?? false,
            'certificado_alcoholemia' => $v['certificado_alcoholemia'] ?? false,
            'aliento_etilico'         => $v['aliento_etilico'] ?? false,
        ];
    }

    private function hechoFotoUrl(?string $path): ?string
    {
        return app(HechoFotoStorage::class)->url($path);
    }

    private function removeAccents(string $s): string
    {
        return strtr($s, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'à'=>'A','è'=>'E','ì'=>'I','ò'=>'O','ù'=>'U',
            'Â'=>'A','Ê'=>'E','Î'=>'I','Ô'=>'O','Û'=>'U',
            'â'=>'A','ê'=>'E','î'=>'I','ô'=>'O','û'=>'U',
            'Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U',
            'ä'=>'A','ë'=>'E','ï'=>'I','ö'=>'O','ü'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ]);
    }

    private function esServicioPublicoFederal($value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $servicio = strtoupper($this->removeAccents((string) $value));
        $servicio = preg_replace('/\s+/', ' ', trim($servicio));
        $compacto = preg_replace('/[^A-Z0-9]/', '', $servicio);

        return str_contains($servicio, 'FEDERAL')
            || str_contains($servicio, 'SCT')
            || $compacto === 'SPF'
            || str_contains($compacto, 'PUBLICOFEDERAL')
            || str_contains($compacto, 'PUBFED');
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                $data[$k] = ($v === '') ? null : $v;
            }
        }
        return $data;
    }

    private function vehiculoPerteneceAlHecho(Hechos $hecho, Vehiculo $vehiculo): bool
    {
        return $hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists();
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $msg = strtolower((string)$e->getMessage());
        return str_contains($msg, 'duplicate entry')
            || str_contains($msg, 'unique constraint')
            || str_contains($msg, 'integrity constraint violation');
    }

    private function validationMessages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string'   => 'Este campo debe ser texto.',
            'integer'  => 'Este campo debe ser un número entero.',
            'numeric'  => 'Este campo debe ser numérico.',
            'max'      => 'No debe exceder :max caracteres.',
            'min'      => 'Debe ser mínimo :min.',
            'digits'   => 'Debe tener exactamente :digits dígitos.',
            'in'       => 'El valor no es válido.',
            'date'     => 'La fecha no es válida.',
            'exists'   => 'El valor seleccionado no existe.',

            'placas.regex' => 'Placas inválidas: no uses espacios, puntos o guiones. Solo letras y números (ej. ABC123).',
            'placas.max'   => 'Placas inválidas: máximo 15 caracteres.',

            'estado_placas.required' => 'Debes capturar el estado de placas (ej. MICHOACAN).',
            'estado_placas.max'      => 'Estado de placas inválido: máximo 15 caracteres.',

            'serie.max'   => 'El NIV/serie no debe superar 17 caracteres.',
            'serie.regex' => 'NIV/serie inválido: no uses espacios, puntos o guiones. Solo letras y números.',

            'telefono.digits' => 'El teléfono debe tener 10 dígitos (sin espacios).',

            'estado_placas.required_with' => 'Si capturas placas, también debes capturar el estado de placas (ej. MICHOACAN).',
            'estado_placas.regex'         => 'Estado de placas inválido: escribe SOLO el estado (sin espacios). Ej: MICHOACAN.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'marca' => 'marca',
            'modelo' => 'modelo',
            'tipo' => 'tipo',
            'linea' => 'línea',
            'color' => 'color',
            'placas' => 'placas',
            'estado_placas' => 'estado de placas',
            'permiso_circular' => 'permiso para circular',
            'serie' => 'NIV/serie',
            'capacidad_personas' => 'capacidad de personas',
            'tipo_servicio' => 'tipo de servicio',
            'tarjeta_circulacion_nombre' => 'nombre en tarjeta de circulación',
            'grua_id' => 'grúa',
            'corralon' => 'corralón',
            'aseguradora' => 'aseguradora',
            'monto_danos' => 'monto de daños',
            'partes_danadas' => 'partes dañadas',
            'conductor_nombre' => 'nombre del conductor',
            'telefono' => 'teléfono',
            'domicilio' => 'domicilio',
            'sexo' => 'sexo',
            'ocupacion' => 'ocupación',
            'edad' => 'edad',
            'tipo_licencia' => 'tipo de licencia',
            'estado_licencia' => 'estado de licencia',
            'vigencia_licencia' => 'vigencia de licencia',
            'numero_licencia' => 'número de licencia',
        ];
    }

    private function servicioUnidadId(Hechos $hecho): ?int
    {
        $unidadId = (int) ($hecho->unidad_org_id ?? $hecho->unidad_id ?? 0);

        return $unidadId > 0 ? $unidadId : null;
    }

    private function servicioDelegacionId(Hechos $hecho): ?int
    {
        $delegacionId = (int) ($hecho->delegacion_id ?? 0);

        return $delegacionId > 0 ? $delegacionId : null;
    }

    private function ok(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    private function created(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    private function validationFailed(array $errors, string $message = 'Datos inválidos. Revisa los campos marcados.', int $status = 422)
    {
        $first = null;
        foreach ($errors as $field => $msgs) {
            if (is_array($msgs) && !empty($msgs[0])) { $first = $msgs[0]; break; }
            if (is_string($msgs)) { $first = $msgs; break; }
        }

        return response()->json([
            'ok' => false,
            'message' => $first ?: $message,
            'errors' => $errors
        ], $status);
    }

    private function fail(string $message, int $status)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ], $status);
    }

    private function throwValidation(array $errors): void
    {
        throw new \Illuminate\Validation\ValidationException(
            Validator::make([], []),
            response()->json([
                'ok' => false,
                'message' => 'Datos inválidos. Revisa los campos marcados.',
                'errors' => $errors,
            ], 422)
        );
    }
}
