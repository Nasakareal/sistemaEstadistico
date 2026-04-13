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

class PuestaDisposicionController extends Controller
{
    private function esSuperadmin($usuario): bool
    {
        return $usuario && $usuario->hasRole('Superadmin');
    }

    private function queryVisibleByUser($usuario)
    {
        $query = PuestaDisposicion::query()->with(['unidad','delegacion','destacamento','creador']);

        if ($this->esSuperadmin($usuario)) return $query;

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

        DB::beginTransaction();

        try {
            $anioActual=now()->year;

            $ultimo=PuestaDisposicion::where('anio',$anioActual)
                ->where('unidad_id',$usuario->unidad_id)
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
                'area'=>$this->obtenerNombreUnidad($usuario->unidad_id),
                'fecha_puesta'=>$request->fecha_puesta,
                'hora_puesta'=>$request->hora_puesta,
                'archivo_puesta'=>$archivo,
                'unidad_id'=>$usuario->unidad_id,
                'delegacion_id'=>$usuario->delegacion_id,
                'destacamento_id'=>$usuario->destacamento_id,
                'created_by'=>$usuario->id,
            ]);

            DB::commit();

            return response()->json($puesta,201);

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
