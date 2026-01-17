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

class VehiculoController extends Controller
{
    public function index(Hechos $hecho)
    {
        return response()->json([
            'data' => $hecho->vehiculos()->with('conductores')->get()
        ]);
    }

    public function store(Request $request, Hechos $hecho)
    {
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
            return response()->json([
                'message' => 'Placas, serie o conductor ya registrados en este hecho'
            ], 409);
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

            return response()->json([
                'message' => 'Vehículo creado',
                'data'    => $vehiculo->load('conductores')
            ], 201);
        });
    }


    public function show(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404);
        }

        return response()->json([
            'data' => $vehiculo->load('conductores')
        ]);
    }

    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404);
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
            return response()->json([
                'message' => 'Duplicado dentro del hecho'
            ], 409);
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

            return response()->json([
                'message' => 'Vehículo actualizado',
                'data'    => $vehiculo->fresh()->load('conductores')
            ]);
        });
    }


    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        if (!$hecho->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404);
        }

        return DB::transaction(function () use ($hecho, $vehiculo) {

            $hecho->vehiculos()->detach($vehiculo->id);

            DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();

            $vehiculo->conductores()->detach();
            $vehiculo->delete();

            return response()->json([
                'message' => 'Vehículo eliminado'
            ]);
        });
    }

    /* ===================== HELPERS ===================== */

    private function validateRequest(Request $request, ?int $vehiculoId = null): array
    {
        $uniquePlacas = Rule::unique('vehiculos', 'placas');
        $uniqueSerie  = Rule::unique('vehiculos', 'serie');

        if ($vehiculoId) {
            $uniquePlacas->ignore($vehiculoId);
            $uniqueSerie->ignore($vehiculoId);
        }

        return $request->validate([
            'marca'                      => 'required|string|max:50',
            'modelo'                     => 'nullable|string|max:10',
            'tipo'                       => 'required|string|max:50',
            'linea'                      => 'required|string|max:50',
            'color'                      => 'required|string|max:30',
            'placas'                     => ['required','string','max:15',$uniquePlacas],
            'estado_placas'              => 'nullable|string|max:15',
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

            // conductor
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
        ]);
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

        // placas y serie sin guiones
        if (isset($data['placas'])) {
            $data['placas'] = strtoupper(str_replace('-', '', $data['placas']));
        }

        // IMPORTANTÍSIMO: si serie viene vacía, que sea NULL (no '')
        if (array_key_exists('serie', $data)) {
            $serie = strtoupper(str_replace('-', '', (string)($data['serie'] ?? '')));
            $data['serie'] = ($serie !== '') ? $serie : null;
        }

        // booleans correctos para JSON
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
        return !empty($v['conductor_nombre'])
            || !empty($v['telefono'])
            || !empty($v['domicilio']);
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
            'vigencia_licencia'       => $v['permanente'] ? null : ($v['vigencia_licencia'] ?? null),
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
}
