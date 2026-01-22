<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Grua;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class VehiculoController extends Controller
{
    public function index(Hechos $hecho)
    {
        try {
            return $this->ok('Vehículos del hecho.', $hecho->vehiculos()->with('conductores')->get());
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al cargar los vehículos.', 500);
        }
    }

    public function store(Request $request, Hechos $hecho)
    {
        try {
            $validated = $this->validateRequest($request);
            $validated = $this->normalize($request, $validated);

            $validated['grua'] = 'N/A';
            if (!empty($validated['grua_id'])) {
                $tmp = Grua::where('id', $validated['grua_id'])->value('nombre');
                if (!empty($tmp)) {
                    $validated['grua'] = strtoupper($tmp);
                }
            }

            if ($this->hayDuplicados($hecho, $validated)) {
                return $this->fail('Placas, NIV/serie o conductor ya registrados en este hecho.', 409);
            }

            return DB::transaction(function () use ($validated, $hecho) {
                $vehiculo = Vehiculo::create($this->onlyVehiculo($validated));
                $hecho->vehiculos()->attach($vehiculo->id);

                if (!empty($validated['grua_id'])) {
                    DB::table('servicios')->insert([
                        'vehiculo_id'   => $vehiculo->id,
                        'grua_id'       => $validated['grua_id'],
                        'tipo_vehiculo' => $validated['tipo'],
                        'aseguradora'   => $validated['aseguradora'] ?? '',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }

                if ($this->hayDatosConductor($validated)) {
                    $conductor = Conductor::create($this->onlyConductor($validated));
                    $vehiculo->conductores()->attach($conductor->id);
                }

                return $this->created('Vehículo creado correctamente.', $vehiculo->load('conductores'));
            });

        } catch (QueryException $e) {
            return $this->fail('No se pudo guardar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al crear el vehículo.', 500);
        }
    }

    public function show(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return $this->ok('Vehículo encontrado.', $vehiculo->load('conductores'));
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar el vehículo.', 500);
        }
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            $validated = $this->validateRequest($request, $vehiculo->id);
            $validated = $this->normalize($request, $validated);

            $validated['grua'] = 'N/A';
            if (!empty($validated['grua_id'])) {
                $tmp = Grua::where('id', $validated['grua_id'])->value('nombre');
                if (!empty($tmp)) {
                    $validated['grua'] = strtoupper($tmp);
                }
            }

            if ($this->hayDuplicados($hecho, $validated, $vehiculo->id)) {
                return $this->fail('Duplicado dentro del hecho (placas / NIV/serie / conductor).', 409);
            }

            return DB::transaction(function () use ($validated, $vehiculo) {
                $vehiculo->update($this->onlyVehiculo($validated));

                if (!empty($validated['grua_id'])) {
                    DB::table('servicios')->updateOrInsert(
                        ['vehiculo_id' => $vehiculo->id],
                        [
                            'grua_id'       => $validated['grua_id'],
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
                        $vehiculo->conductores()->attach($conductor->id);
                    }
                }

                return $this->ok('Vehículo actualizado correctamente.', $vehiculo->fresh()->load('conductores'));
            });

        } catch (QueryException $e) {
            return $this->fail('No se pudo actualizar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al actualizar el vehículo.', 500);
        }
    }

    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return DB::transaction(function () use ($hecho, $vehiculo) {
                if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
                    Storage::disk('public')->delete($vehiculo->fotos);
                }

                $hecho->vehiculos()->detach($vehiculo->id);

                DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();

                $vehiculo->conductores()->detach();
                $vehiculo->delete();

                return $this->ok('Vehículo eliminado correctamente.', null);
            });

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al eliminar el vehículo.', 500);
        }
    }

    public function foto(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return $this->ok('Foto del vehículo.', [
                'vehiculo_id' => $vehiculo->id,
                'fotos'       => $vehiculo->fotos,
                'url'         => $vehiculo->fotos ? asset('storage/' . $vehiculo->fotos) : null,
            ]);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar la foto.', 500);
        }
    }

    public function fotoUpdate(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
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

            return DB::transaction(function () use ($request, $vehiculo) {
                if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
                    Storage::disk('public')->delete($vehiculo->fotos);
                }

                $path = $request->file('foto')->store('vehiculos', 'public');

                $vehiculo->update([
                    'fotos' => $path,
                ]);

                return $this->created('Foto guardada correctamente.', [
                    'vehiculo_id' => $vehiculo->id,
                    'fotos'       => $vehiculo->fotos,
                    'url'         => asset('storage/' . $vehiculo->fotos),
                ]);
            });

        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al guardar la foto.', 500);
        }
    }

    public function fotoDestroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        try {
            if (!$this->vehiculoPerteneceAlHecho($hecho, $vehiculo)) {
                return $this->fail('No se encontró el vehículo dentro de este hecho.', 404);
            }

            return DB::transaction(function () use ($vehiculo) {
                if (!empty($vehiculo->fotos) && Storage::disk('public')->exists($vehiculo->fotos)) {
                    Storage::disk('public')->delete($vehiculo->fotos);
                }

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

    private function validateRequest(Request $request, ?int $vehiculoId = null): array
    {
        $data = $this->sanitize($request->all());

        $uniquePlacas = Rule::unique('vehiculos', 'placas');
        $uniqueSerie  = Rule::unique('vehiculos', 'serie');

        if ($vehiculoId) {
            $uniquePlacas->ignore($vehiculoId);
            $uniqueSerie->ignore($vehiculoId);
        }

        // OJO:
        // - Mantengo placas como required como tú lo tienes.
        // - Pero agrego que si hay placas, estado_placas sea obligatorio (required_with).
        $rules = [
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'nullable|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',

            'placas'                     => ['required','string','max:15',$uniquePlacas],
            'estado_placas'              => 'nullable|string|max:15|required_with:placas',

            // Serie = NIV (máx 17)
            'serie'                      => ['nullable','string','max:17',$uniqueSerie],

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
        ];

        $messages = $this->validationMessages();
        $attributes = $this->validationAttributes();

        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            // 422 con JSON consistente
            // (Flutter ya puede mostrar message + errors)
            abort(response()->json([
                'ok'      => false,
                'message' => 'Datos inválidos. Revisa los campos marcados.',
                'errors'  => $validator->errors()->toArray(),
            ], 422));
        }

        return $validator->validated();
    }

    private function normalize(Request $request, array $data): array
    {
        $upper = [
            'marca','modelo','tipo','linea','color','estado_placas','tipo_servicio',
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
            $data['placas'] = strtoupper(str_replace('-', '', $data['placas']));
        }

        if (array_key_exists('serie', $data)) {
            $serie = strtoupper(str_replace('-', '', (string)($data['serie'] ?? '')));
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

    private function hayDuplicados(Hechos $hecho, array $v, ?int $ignoreId = null): bool
    {
        $q = $hecho->vehiculos();
        if ($ignoreId) $q->where('vehiculos.id', '!=', $ignoreId);
        $dupPlaca = $q->where('placas', $v['placas'])->exists();

        $dupSerie = false;
        if (!empty($v['serie'])) {
            $q2 = $hecho->vehiculos();
            if ($ignoreId) $q2->where('vehiculos.id', '!=', $ignoreId);
            $dupSerie = $q2->where('serie', $v['serie'])->exists();
        }

        $dupConductor = false;
        if (!empty($v['conductor_nombre'])) {
            $q3 = $hecho->vehiculos();
            if ($ignoreId) $q3->where('vehiculos.id', '!=', $ignoreId);
            $dupConductor = $q3->whereHas('conductores', function ($q) use ($v) {
                $q->where('nombre', $v['conductor_nombre']);
            })->exists();
        }

        return $dupPlaca || $dupSerie || $dupConductor;
    }

    private function hayDatosConductor(array $v): bool
    {
        return !empty($v['conductor_nombre']) || !empty($v['telefono']) || !empty($v['domicilio']);
    }

    private function onlyVehiculo(array $v): array
    {
        return [
            'marca'                      => $v['marca'] ?? null,
            'modelo'                     => $v['modelo'] ?? null,
            'tipo'                       => $v['tipo'] ?? null,
            'linea'                      => $v['linea'] ?? null,
            'color'                      => $v['color'] ?? null,
            'placas'                     => $v['placas'] ?? null,
            'estado_placas'              => $v['estado_placas'] ?? null,
            'serie'                      => $v['serie'] ?? null,
            'capacidad_personas'         => $v['capacidad_personas'] ?? 0,
            'tipo_servicio'              => $v['tipo_servicio'] ?? null,
            'tarjeta_circulacion_nombre' => $v['tarjeta_circulacion_nombre'] ?? null,
            'grua'                       => $v['grua'] ?? 'N/A',
            'corralon'                   => $v['corralon'] ?? null,
            'aseguradora'                => $v['aseguradora'] ?? null,
            'monto_danos'                => $v['monto_danos'] ?? 0,
            'partes_danadas'             => $v['partes_danadas'] ?? null,
            'antecedente_vehiculo'       => $v['antecedente_vehiculo'] ?? false,
            'fotos'                      => $v['fotos'] ?? null,
        ];
    }

    private function onlyConductor(array $v): array
    {
        return [
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

    private function removeAccents(string $s): string
    {
        return strtr($s, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'à'=>'A','è'=>'E','ì'=>'I','ò'=>'O','ù'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ]);
    }

    /**
     * Normaliza strings: trim y "" -> null
     * Esto ayuda a que required_with funcione bien.
     */
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

    /**
     * Mensajes claros para validación
     */
    private function validationMessages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Este campo debe ser texto.',
            'integer' => 'Este campo debe ser un número entero.',
            'numeric' => 'Este campo debe ser numérico.',
            'max' => 'No debe exceder :max caracteres.',
            'min' => 'Debe ser mínimo :min.',
            'digits' => 'Debe tener exactamente :digits dígitos.',
            'in' => 'El valor no es válido.',
            'date' => 'La fecha no es válida.',
            'exists' => 'El valor seleccionado no existe.',
            'unique' => 'Este valor ya está registrado.',
            'required_with' => 'Este campo es obligatorio cuando se captura :values.',

            // Específicos importantes
            'serie.max' => 'El NIV/serie no debe superar 17 caracteres.',
            'estado_placas.required_with' => 'Si capturas placas, también debes capturar el estado de placas.',
            'placas.unique' => 'Estas placas ya están registradas.',
            'serie.unique' => 'Este NIV/serie ya está registrado.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos.',
        ];
    }

    /**
     * Nombres bonitos para que no se vea "estado_placas"
     */
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

    /**
     * Respuestas JSON consistentes
     */
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

    private function validationFailed(array $errors, string $message = 'Datos inválidos. Revisa los campos marcados.')
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    private function fail(string $message, int $status)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ], $status);
    }
}
