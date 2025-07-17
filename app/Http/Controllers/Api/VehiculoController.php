<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiculoController extends Controller
{
    /* ========== LISTAR ========== */
    public function index(Hechos $hecho)
    {
        return response()->json([
            'data' => $hecho->vehiculos()->with('conductores')->get()
        ]);
    }

    /* ========== CREAR ========== */
    public function store(Request $request, Hechos $hecho)
    {
        $validated = $this->validateRequest($request);

        // Normalizar a mayúsculas y quitar acentos
        $validated = $this->normalize($validated);

        // Verificar duplicados dentro del mismo hecho
        if ($this->hayDuplicados($hecho, $validated)) {
            return response()->json([
                'message' => 'Placas, serie o conductor ya registrados en este hecho'
            ], 409);
        }

        // Guardar vehículo
        $vehiculo = Vehiculo::create($this->onlyVehiculo($validated));
        $hecho->vehiculos()->attach($vehiculo->id);

        // Servicio de grúa (si aplica)
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

        // Conductor (si viene en la petición)
        if (!empty($validated['conductor_nombre'])) {
            $conductor = Conductor::create($this->onlyConductor($validated));
            $vehiculo->conductores()->attach($conductor->id);
        }

        return response()->json([
            'message' => 'Vehículo creado',
            'data'    => $vehiculo->load('conductores')
        ], 201);
    }

    /* ========== MOSTRAR UNO ========== */
    public function show(Hechos $hecho, Vehiculo $vehiculo)
    {
        abort_unless($hecho->vehiculos->contains($vehiculo->id), 404);

        return response()->json([
            'data' => $vehiculo->load('conductores')
        ]);
    }

    /* ========== ACTUALIZAR ========== */
    public function update(Request $request, Hechos $hecho, Vehiculo $vehiculo)
    {
        abort_unless($hecho->vehiculos->contains($vehiculo->id), 404);

        $validated = $this->validateRequest($request, $vehiculo->id);
        $validated = $this->normalize($validated);

        if ($this->hayDuplicados($hecho, $validated, $vehiculo->id)) {
            return response()->json([
                'message' => 'Duplicado dentro del hecho'
            ], 409);
        }

        $vehiculo->update($this->onlyVehiculo($validated));

        // actualizar / crear servicio de grúa
        DB::table('servicios')->updateOrInsert(
            ['vehiculo_id' => $vehiculo->id],
            [
                'grua_id'       => $validated['grua_id'],
                'tipo_vehiculo' => $validated['tipo'],
                'aseguradora'   => $validated['aseguradora'] ?? '',
                'updated_at'    => now(),
            ]
        );

        // Conductor (crea o actualiza el primero ligado)
        if (!empty($validated['conductor_nombre'])) {
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
            'data'    => $vehiculo->load('conductores')
        ]);
    }

    /* ========== ELIMINAR ========== */
    public function destroy(Hechos $hecho, Vehiculo $vehiculo)
    {
        abort_unless($hecho->vehiculos->contains($vehiculo->id), 404);
        $vehiculo->delete();

        return response()->json([
            'message' => 'Vehículo eliminado'
        ]);
    }

    /* --------------------------------------------------------------------
       Helpers
    -------------------------------------------------------------------- */

    /** Reglas y mensajes compartidos */
    private function validateRequest(Request $request, ?int $vehiculoId = null): array
    {
        $uniquePlacas = Rule::unique('vehiculos', 'placas');
        $uniqueSerie  = Rule::unique('vehiculos', 'serie');

        if ($vehiculoId) {
            $uniquePlacas->ignore($vehiculoId);
            $uniqueSerie ->ignore($vehiculoId);
        }

        return $request->validate([
            'marca'      => 'required|string|max:50',
            'modelo'     => 'nullable|string|max:10',
            'tipo'       => 'required|string|max:50',
            'linea'      => 'required|string|max:50',
            'color'      => 'required|string|max:30',
            'placas'     => ['required','string','max:15',$uniquePlacas],
            'estado_placas'=>'nullable|string|max:15',
            'serie'      => ['nullable','string','max:17',$uniqueSerie],
            'capacidad_personas'=>'required|integer|min:0',
            'tipo_servicio'=>'required|string|max:50',
            'tarjeta_circulacion_nombre'=>'nullable|string|max:60',
            'grua_id'    => 'nullable|exists:gruas,id',
            'corralon'   => 'nullable|string|max:50',
            'aseguradora'=> 'nullable|string|max:100',
            'monto_danos'=> 'required|numeric|min:0',
            'partes_danadas'=>'required|string',
            'antecedente_vehiculo'=> 'sometimes|boolean',

            /* datos de conductor */
            'conductor_nombre'  => 'nullable|string|max:255',
            'telefono'          => 'nullable|digits:10',
            'domicilio'         => 'nullable|string|max:255',
            'sexo'              => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'ocupacion'         => 'nullable|string|max:255',
            'edad'              => 'nullable|integer|min:0|max:100',
            'tipo_licencia'     => 'nullable|string|max:50',
            'estado_licencia'   => 'nullable|string|max:100',
            'vigencia_licencia' => 'nullable|date',
            'numero_licencia'   => 'nullable|string|max:50',
            'permanente'        => 'sometimes|boolean',
            'cinturon'          => 'sometimes|boolean',
            'antecedente_conductor' => 'sometimes|boolean',
            'certificado_lesiones'  => 'sometimes|boolean',
            'certificado_alcoholemia'=> 'sometimes|boolean',
            'aliento_etilico'   => 'sometimes|boolean',
        ]);
    }

    /** Normaliza strings (mayúsculas + sin acentos) y checkboxes → boolean */
    private function normalize(array $data): array
    {
        $upper = ['marca','modelo','tipo','linea','color','estado_placas','tipo_servicio',
                  'tarjeta_circulacion_nombre','corralon','aseguradora','partes_danadas',
                  'conductor_nombre','domicilio','sexo','ocupacion','tipo_licencia',
                  'estado_licencia','numero_licencia'];

        foreach ($upper as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($this->removeAccents($data[$k]));
            }
        }

        /* placas & serie sin guiones */
        if (isset($data['placas'])) $data['placas'] = strtoupper(str_replace('-', '', $data['placas']));
        if (!empty($data['serie'])) $data['serie']  = strtoupper(str_replace('-', '', $data['serie']));

        /* checkboxes */
        foreach (['antecedente_vehiculo','cinturon','antecedente_conductor',
                  'certificado_lesiones','certificado_alcoholemia','aliento_etilico',
                  'permanente'] as $chk) {
            $data[$chk] = !empty($data[$chk]);
        }

        return $data;
    }

    /** Comprueba duplicados de placas, serie o conductor en el hecho */
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
            $dupConductor = $q3->whereHas('conductores', fn($q) =>
                $q->where('nombre', $v['conductor_nombre'])
            )->exists();
        }

        return $dupPlaca || $dupSerie || $dupConductor;
    }

    /* ----- Separadores de datos ----- */
    private function onlyVehiculo(array $v): array
    {
        return collect($v)->only([
            'marca','modelo','tipo','linea','color','placas','estado_placas',
            'serie','capacidad_personas','tipo_servicio','tarjeta_circulacion_nombre',
            'grua_id','corralon','aseguradora','monto_danos','partes_danadas',
            'antecedente_vehiculo'
        ])->toArray();
    }

    private function onlyConductor(array $v): array
    {
        return collect($v)->only([
            'conductor_nombre as nombre','telefono','domicilio','sexo','ocupacion',
            'edad','tipo_licencia','estado_licencia','vigencia_licencia',
            'numero_licencia','permanente','cinturon','antecedente_conductor as antecedentes',
            'certificado_lesiones','certificado_alcoholemia','aliento_etilico'
        ])->mapWithKeys(function($value, $key){
            // mapWithKeys para cambiar nombre campos
            $map = [
                'conductor_nombre as nombre'        => 'nombre',
                'antecedente_conductor as antecedentes' => 'antecedentes',
            ];
            return [$map[$key] ?? $key => $value];
        })->toArray();
    }

    /* Quitar acentos */
    private function removeAccents(string $s): string
    {
        return strtr($s, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'Ñ'=>'N','ñ'=>'N'
        ]);
    }
}
