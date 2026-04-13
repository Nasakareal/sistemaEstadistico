<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use App\Models\PuestaDisposicionVehiculo;
use App\Models\PuestaDisposicionObjeto;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PuestaDisposicionController extends Controller
{
    private function esSuperadmin($usuario): bool
    {
        return $usuario && $usuario->hasRole('Superadmin');
    }

    private function puedeVerTodasLasUnidades($usuario): bool
    {
        return $this->esSuperadmin($usuario) || (int)($usuario->unidad_id ?? 0) === 3;
    }

    private function puedeSeleccionarUnidadRegistro($usuario): bool
    {
        return $this->esSuperadmin($usuario) || empty($usuario->unidad_id);
    }

    private function queryVisibleByUser($usuario)
    {
        $query = PuestaDisposicion::query()->with(['unidad','delegacion','destacamento','creador']);

        if ($this->puedeVerTodasLasUnidades($usuario)) return $query;

        if ($usuario->unidad_id) {
            $query->where('unidad_id',$usuario->unidad_id);
        } else {
            $query->whereRaw('1=0');
        }

        if (!is_null($usuario->delegacion_id)) $query->where('delegacion_id',$usuario->delegacion_id);
        if (!is_null($usuario->destacamento_id)) $query->where('destacamento_id',$usuario->destacamento_id);

        return $query;
    }

    private function findVisibleOrFail($id,$usuario): PuestaDisposicion
    {
        return $this->queryVisibleByUser($usuario)
            ->with(['personas','vehiculos','objetos','unidad','delegacion','destacamento','creador','actualizador'])
            ->findOrFail($id);
    }

    private function normalizarTextoNullable($valor): ?string
    {
        if ($valor===null) return null;
        $valor=trim((string)$valor);
        return $valor===''?null:strtoupper($valor);
    }

    private function normalizarTextoRequerido($valor): string
    {
        return strtoupper(trim((string)$valor));
    }

    private function obtenerNombreUnidad(?int $unidadId): string
    {
        if (!$unidadId) return 'SIN ASIGNAR';
        return Unidad::where('id',$unidadId)->value('nombre')?:'SIN ASIGNAR';
    }

    private function resolverUnidadRegistro(Request $request,$usuario): int
    {
        $unidadId=$this->puedeSeleccionarUnidadRegistro($usuario)
            ? (int)$request->input('unidad_id')
            : (int)($usuario->unidad_id ?? 0);

        if (!$unidadId || !Unidad::where('id',$unidadId)->where('activa',1)->exists()) {
            throw ValidationException::withMessages([
                'unidad_id'=>'Seleccione una unidad válida para la puesta a disposición.',
            ]);
        }

        return $unidadId;
    }

    private function normalizarTextoLargoNullable($valor): ?string
    {
        if ($valor===null || trim((string)$valor)==='') return null;
        return strtoupper(trim((string)$valor));
    }

    private function guardarDetalles(PuestaDisposicion $puesta, Request $request): void
    {
        foreach ($request->input('personas', []) as $persona) {
            if (empty(trim((string)($persona['nombre_completo'] ?? '')))) continue;

            PuestaDisposicionPersona::create([
                'puesta_disposicion_id'=>$puesta->id,
                'nombre_completo'=>$this->normalizarTextoRequerido($persona['nombre_completo'] ?? ''),
                'alias'=>$this->normalizarTextoNullable($persona['alias'] ?? null),
                'edad'=>$persona['edad'] ?? null,
                'sexo'=>$this->normalizarTextoNullable($persona['sexo'] ?? null),
                'fecha_nacimiento'=>$persona['fecha_nacimiento'] ?? null,
                'curp'=>$this->normalizarTextoNullable($persona['curp'] ?? null),
                'rfc'=>$this->normalizarTextoNullable($persona['rfc'] ?? null),
                'domicilio'=>$this->normalizarTextoLargoNullable($persona['domicilio'] ?? null),
                'calidad'=>$this->normalizarTextoRequerido($persona['calidad'] ?? 'SIN DEFINIR'),
                'delito_o_motivo'=>$this->normalizarTextoNullable($persona['delito_o_motivo'] ?? null),
                'orden_aprehension'=>!empty($persona['orden_aprehension']),
                'mandamiento_judicial'=>$this->normalizarTextoNullable($persona['mandamiento_judicial'] ?? null),
                'observaciones'=>$this->normalizarTextoLargoNullable($persona['observaciones'] ?? null),
            ]);
        }

        foreach ($request->input('vehiculos', []) as $vehiculo) {
            if (
                empty(trim((string)($vehiculo['placas'] ?? ''))) &&
                empty(trim((string)($vehiculo['serie'] ?? ''))) &&
                empty(trim((string)($vehiculo['marca'] ?? '')))
            ) continue;

            PuestaDisposicionVehiculo::create([
                'puesta_disposicion_id'=>$puesta->id,
                'vehiculo_id'=>null,
                'tipo'=>$this->normalizarTextoNullable($vehiculo['tipo'] ?? null),
                'marca'=>$this->normalizarTextoNullable($vehiculo['marca'] ?? null),
                'submarca'=>$this->normalizarTextoNullable($vehiculo['submarca'] ?? null),
                'modelo'=>$this->normalizarTextoNullable($vehiculo['modelo'] ?? null),
                'color'=>$this->normalizarTextoNullable($vehiculo['color'] ?? null),
                'placas'=>$this->normalizarTextoNullable($vehiculo['placas'] ?? null),
                'serie'=>$this->normalizarTextoNullable($vehiculo['serie'] ?? null),
                'calidad'=>$this->normalizarTextoRequerido($vehiculo['calidad'] ?? 'SIN DEFINIR'),
                'motivo_relacion'=>$this->normalizarTextoNullable($vehiculo['motivo_relacion'] ?? null),
                'con_reporte_robo'=>!empty($vehiculo['con_reporte_robo']),
                'numero_reporte_robo'=>$this->normalizarTextoNullable($vehiculo['numero_reporte_robo'] ?? null),
                'observaciones'=>$this->normalizarTextoLargoNullable($vehiculo['observaciones'] ?? null),
            ]);
        }

        foreach ($request->input('objetos', []) as $objeto) {
            if (
                empty(trim((string)($objeto['tipo_objeto'] ?? ''))) &&
                empty(trim((string)($objeto['descripcion'] ?? '')))
            ) continue;

            PuestaDisposicionObjeto::create([
                'puesta_disposicion_id'=>$puesta->id,
                'tipo_objeto'=>$this->normalizarTextoRequerido($objeto['tipo_objeto'] ?? 'SIN DEFINIR'),
                'descripcion'=>$this->normalizarTextoRequerido($objeto['descripcion'] ?? 'SIN DESCRIPCION'),
                'cantidad'=>$objeto['cantidad'] ?? null,
                'unidad_medida'=>$this->normalizarTextoNullable($objeto['unidad_medida'] ?? null),
                'cadena_custodia'=>$this->normalizarTextoNullable($objeto['cadena_custodia'] ?? null),
                'observaciones'=>$this->normalizarTextoLargoNullable($objeto['observaciones'] ?? null),
            ]);
        }
    }

    public function index(Request $request)
    {
        $usuario=Auth::user();

        $anio=$request->get('anio',now()->year);

        $query=$this->queryVisibleByUser($usuario)
            ->where('anio',$anio)
            ->orderByDesc('numero_puesta');

        if ($request->filled('motivo')) $query->where('motivo',strtoupper(trim($request->motivo)));
        if ($request->filled('tipo_puesta')) $query->where('tipo_puesta',strtoupper(trim($request->tipo_puesta)));

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $usuario=Auth::user();
        $unidadRegistroId=$this->resolverUnidadRegistro($request,$usuario);

        DB::beginTransaction();

        try {
            $anioActual=now()->year;

            $ultimo=PuestaDisposicion::where('anio',$anioActual)
                ->where('unidad_id',$unidadRegistroId)
                ->lockForUpdate()
                ->orderByDesc('numero_puesta')
                ->first();

            $numero=$ultimo?($ultimo->numero_puesta+1):1;

            $archivo=null;
            if ($request->hasFile('archivo_puesta')) {
                $archivo=$request->file('archivo_puesta')->store('puestas_disposicion','public');
            }

            $puesta=PuestaDisposicion::create([
                'numero_puesta'=>$numero,
                'anio'=>$anioActual,
                'tipo_puesta'=>$this->normalizarTextoRequerido($request->tipo_puesta),
                'motivo'=>$this->normalizarTextoRequerido($request->motivo),
                'estatus'=>'ACTIVA',
                'nombre_policia'=>$this->normalizarTextoRequerido($request->nombre_policia),
                'nombre_mp'=>$this->normalizarTextoNullable($request->nombre_mp),
                'autoridad_receptora'=>$this->normalizarTextoNullable($request->autoridad_receptora),
                'area'=>$this->obtenerNombreUnidad($unidadRegistroId),
                'carpeta_investigacion'=>$this->normalizarTextoNullable($request->carpeta_investigacion),
                'oficio'=>$this->normalizarTextoNullable($request->oficio),
                'fecha_puesta'=>$request->fecha_puesta,
                'hora_puesta'=>$request->hora_puesta,
                'lugar_puesta'=>$this->normalizarTextoNullable($request->lugar_puesta),
                'narrativa'=>$this->normalizarTextoLargoNullable($request->narrativa),
                'observaciones'=>$this->normalizarTextoLargoNullable($request->observaciones),
                'archivo_puesta'=>$archivo,
                'unidad_id'=>$unidadRegistroId,
                'delegacion_id'=>$usuario->delegacion_id,
                'destacamento_id'=>$usuario->destacamento_id,
                'created_by'=>$usuario->id,
            ]);

            $this->guardarDetalles($puesta, $request);

            DB::commit();

            return response()->json($puesta->load(['personas','vehiculos','objetos','unidad']),201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function show(PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $data=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);
        return response()->json($data);
    }

    public function update(Request $request,PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $puesta=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);

        $puesta->update([
            'tipo_puesta'=>$this->normalizarTextoRequerido($request->tipo_puesta),
            'motivo'=>$this->normalizarTextoRequerido($request->motivo),
            'nombre_policia'=>$this->normalizarTextoRequerido($request->nombre_policia),
        ]);

        return response()->json($puesta);
    }

    public function destroy(PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $puesta=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);

        $puesta->delete();

        return response()->json(['ok'=>true]);
    }
}
