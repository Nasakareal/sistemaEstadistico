<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use App\Models\PuestaDisposicionVehiculo;
use App\Models\PuestaDisposicionObjeto;
use App\Models\PuestaDisposicionFoto;
use App\Models\Unidad;
use App\Models\Hechos;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Services\Documentos\DocumentoArchivoStorage;
use App\Services\Fotos\HechoFotoStorage;
use App\Support\HechoAccess;
use App\Support\PuestaDisposicionRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $query = PuestaDisposicion::query()->with(['unidad','delegacion','destacamento','creador','fotos']);

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
            ->with(['personas','vehiculos','objetos','fotos','unidad','delegacion','destacamento','creador','actualizador'])
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

    private function mensajePuestaDebeSerVinculada(): string
    {
        return 'Para hechos de tránsito de Delegaciones, crea primero el hecho turnado y después registra la puesta vinculada desde el hecho.';
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

    private function unidadIdDesdeHecho(?Hechos $hecho): ?int
    {
        if (!$hecho) return null;

        $unidadId=(int)($hecho->unidad_org_id ?: optional($hecho->creator)->unidad_id);
        return $unidadId>0 ? $unidadId : null;
    }

    private function resolverHechoOrigen(Request $request,$usuario): ?Hechos
    {
        $hechoId=(int)$request->input('hecho_id');
        $hechoClientUuid=trim((string)$request->input('hecho_client_uuid',''));

        $query=Hechos::query()->with(['creator','vehiculos']);

        if ($hechoId>0) {
            $query->whereKey($hechoId);
        } elseif ($hechoClientUuid!=='') {
            $query->where('client_uuid',$hechoClientUuid);
        } else {
            return null;
        }

        HechoAccess::applyVisibilityScope($query,$usuario);
        $hecho=$query->first();

        if (!$hecho) {
            throw ValidationException::withMessages([
                'hecho_id'=>'No se encontró un hecho válido para vincular la puesta a disposición.',
            ]);
        }

        return $hecho;
    }

    private function resolverVehiculoRelacionadoId(?Hechos $hecho,array $vehiculo): ?int
    {
        if (!$hecho) return null;

        $hecho->loadMissing('vehiculos');
        $vehiculos=$hecho->vehiculos;
        $vehiculoId=(int)($vehiculo['vehiculo_id'] ?? 0);

        if ($vehiculoId>0 && $vehiculos->contains('id',$vehiculoId)) {
            return $vehiculoId;
        }

        $sourceKey=trim((string)($vehiculo['source_key'] ?? ''));
        if (preg_match('/^vehiculo:(\d+)$/',$sourceKey,$matches)) {
            $sourceVehiculoId=(int)$matches[1];
            if ($vehiculos->contains('id',$sourceVehiculoId)) {
                return $sourceVehiculoId;
            }
        }

        $serie=strtoupper(trim((string)($vehiculo['serie'] ?? '')));
        if ($serie!=='') {
            $match=$vehiculos->first(fn ($item) => strtoupper(trim((string)$item->serie))===$serie);
            if ($match) return (int)$match->id;
        }

        $placas=strtoupper(trim((string)($vehiculo['placas'] ?? '')));
        if ($placas!=='') {
            $match=$vehiculos->first(fn ($item) => strtoupper(trim((string)$item->placas))===$placas);
            if ($match) return (int)$match->id;
        }

        return null;
    }

    private function normalizarTextoLargoNullable($valor): ?string
    {
        if ($valor===null || trim((string)$valor)==='') return null;
        return strtoupper(trim((string)$valor));
    }

    private function prepararRequestStore(Request $request,int $unidadRegistroId): void
    {
        $request->merge([
            'tipo_puesta'=>$this->normalizarTextoRequerido($request->input('tipo_puesta')),
            'motivo'=>$this->normalizarTextoRequerido($request->input('motivo')),
            'estatus'=>'ACTIVA',
            'nombre_policia'=>$this->normalizarTextoRequerido($request->input('nombre_policia')),
            'nombre_mp'=>$this->normalizarTextoNullable($request->input('nombre_mp')),
            'autoridad_receptora'=>$this->normalizarTextoNullable($request->input('autoridad_receptora')),
            'area'=>$this->obtenerNombreUnidad($unidadRegistroId),
            'carpeta_investigacion'=>$this->normalizarTextoNullable($request->input('carpeta_investigacion')),
            'oficio'=>$this->normalizarTextoNullable($request->input('oficio')),
            'lugar_puesta'=>$this->normalizarTextoNullable($request->input('lugar_puesta')),
            'narrativa'=>$this->normalizarTextoLargoNullable($request->input('narrativa')),
            'observaciones'=>$this->normalizarTextoLargoNullable($request->input('observaciones')),
        ]);
    }

    private function validarStore(Request $request,$usuario): void
    {
        $request->validate([
            'hecho_id'=>'nullable|integer|exists:hechos,id',
            'hecho_client_uuid'=>'nullable|string|max:100',
            'tipo_puesta'=>'required|string|max:100',
            'motivo'=>'required|string|max:150',
            'estatus'=>'nullable|string|max:100',
            'nombre_policia'=>'required|string|max:255',
            'unidad_id'=>$this->puedeSeleccionarUnidadRegistro($usuario) ? 'nullable|integer|exists:unidades,id' : 'nullable',
            'nombre_mp'=>'nullable|string|max:255',
            'autoridad_receptora'=>'nullable|string|max:255',
            'area'=>'nullable|string|max:255',
            'carpeta_investigacion'=>'nullable|string|max:255',
            'oficio'=>'nullable|string|max:255',
            'fecha_puesta'=>'required|date',
            'hora_puesta'=>'nullable|date_format:H:i',
            'lugar_puesta'=>'nullable|string|max:255',
            'narrativa'=>'nullable|string',
            'observaciones'=>'nullable|string',
            'archivo_puesta'=>'nullable|file|mimes:pdf|max:20480',
            'fotos'=>'nullable|array|max:10',
            'fotos.*'=>'image|mimes:jpg,jpeg,png,webp|max:5120',

            'personas'=>'nullable|array',
            'personas.*.nombre_completo'=>'nullable|string|max:255',
            'personas.*.alias'=>'nullable|string|max:255',
            'personas.*.edad'=>'nullable|integer|min:0|max:150',
            'personas.*.sexo'=>'nullable|string|max:20',
            'personas.*.fecha_nacimiento'=>'nullable|date',
            'personas.*.curp'=>'nullable|string|max:50',
            'personas.*.rfc'=>'nullable|string|max:30',
            'personas.*.domicilio'=>'nullable|string',
            'personas.*.calidad'=>'nullable|string|max:100',
            'personas.*.delito_o_motivo'=>'nullable|string|max:255',
            'personas.*.orden_aprehension'=>'nullable|boolean',
            'personas.*.mandamiento_judicial'=>'nullable|string|max:255',
            'personas.*.observaciones'=>'nullable|string',

            'vehiculos'=>'nullable|array',
            'vehiculos.*.vehiculo_id'=>'nullable|integer|exists:vehiculos,id',
            'vehiculos.*.source_key'=>'nullable|string|max:100',
            'vehiculos.*.tipo'=>'nullable|string|max:100',
            'vehiculos.*.marca'=>'nullable|string|max:100',
            'vehiculos.*.submarca'=>'nullable|string|max:100',
            'vehiculos.*.modelo'=>'nullable|string|max:20',
            'vehiculos.*.color'=>'nullable|string|max:100',
            'vehiculos.*.placas'=>'nullable|string|max:50',
            'vehiculos.*.serie'=>'nullable|string|max:100',
            'vehiculos.*.calidad'=>'nullable|string|max:100',
            'vehiculos.*.motivo_relacion'=>'nullable|string|max:255',
            'vehiculos.*.con_reporte_robo'=>'nullable|boolean',
            'vehiculos.*.numero_reporte_robo'=>'nullable|string|max:255',
            'vehiculos.*.observaciones'=>'nullable|string',

            'objetos'=>'nullable|array',
            'objetos.*.tipo_objeto'=>'nullable|string|max:100',
            'objetos.*.descripcion'=>'nullable|string',
            'objetos.*.cantidad'=>'nullable|numeric|min:0',
            'objetos.*.unidad_medida'=>'nullable|string|max:50',
            'objetos.*.cadena_custodia'=>'nullable|string|max:255',
            'objetos.*.observaciones'=>'nullable|string',
        ]);
    }

    private function guardarDetalles(PuestaDisposicion $puesta, Request $request): void
    {
        $hechoOrigen=$puesta->hecho_id
            ? $puesta->hecho()->with('vehiculos')->first()
            : null;

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
                'vehiculo_id'=>$this->resolverVehiculoRelacionadoId($hechoOrigen,$vehiculo),
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

        return response()->json(
            $query->get()->map(fn (PuestaDisposicion $puesta) => $this->withFotoUrls($puesta))->values()
        );
    }

    public function store(Request $request)
    {
        $usuario=Auth::user();
        $hechoOrigen=$this->resolverHechoOrigen($request,$usuario);
        if ($hechoOrigen && PuestaDisposicion::query()->where('hecho_id',$hechoOrigen->id)->exists()) {
            throw ValidationException::withMessages([
                'hecho_id'=>'Este hecho ya tiene una puesta a disposición vinculada.',
            ]);
        }

        $unidadRegistroId=$this->unidadIdDesdeHecho($hechoOrigen)
            ?: $this->resolverUnidadRegistro($request,$usuario);
        $request->merge(['hecho_id'=>$hechoOrigen ? $hechoOrigen->id : null]);
        $this->prepararRequestStore($request,$unidadRegistroId);

        if (PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
            $unidadRegistroId,
            $request->input('motivo'),
            $hechoOrigen !== null
        )) {
            throw ValidationException::withMessages([
                'hecho_id'=>$this->mensajePuestaDebeSerVinculada(),
            ]);
        }

        $this->validarStore($request,$usuario);

        Log::info('Puesta a disposicion API: solicitud recibida', [
            'user_id'=>$usuario ? $usuario->id : null,
            'unidad_id'=>$unidadRegistroId,
            'tipo_puesta'=>$request->input('tipo_puesta'),
            'personas'=>count($request->input('personas', [])),
            'vehiculos'=>count($request->input('vehiculos', [])),
            'objetos'=>count($request->input('objetos', [])),
            'tiene_archivo'=>$request->hasFile('archivo_puesta'),
        ]);

        $fotoPaths=[];

        try {
            DB::beginTransaction();

            $anioActual=now()->year;

            $ultimo=PuestaDisposicion::where('anio',$anioActual)
                ->where('unidad_id',$unidadRegistroId)
                ->lockForUpdate()
                ->orderByDesc('numero_puesta')
                ->first();

            $numero=$ultimo?($ultimo->numero_puesta+1):1;

            $archivo=null;
            if ($request->hasFile('archivo_puesta')) {
                $archivo=$this->documentos()->putUploadedFile($request->file('archivo_puesta'),'puestas_disposicion');
            }

            $puesta=PuestaDisposicion::create([
                'hecho_id'=>$hechoOrigen ? $hechoOrigen->id : null,
                'numero_puesta'=>$numero,
                'anio'=>$anioActual,
                'tipo_puesta'=>$request->input('tipo_puesta'),
                'motivo'=>$request->input('motivo'),
                'estatus'=>'ACTIVA',
                'nombre_policia'=>$request->input('nombre_policia'),
                'nombre_mp'=>$request->input('nombre_mp'),
                'autoridad_receptora'=>$request->input('autoridad_receptora'),
                'area'=>$this->obtenerNombreUnidad($unidadRegistroId),
                'carpeta_investigacion'=>$request->input('carpeta_investigacion'),
                'oficio'=>$request->input('oficio'),
                'fecha_puesta'=>$request->input('fecha_puesta'),
                'hora_puesta'=>$request->input('hora_puesta'),
                'lugar_puesta'=>$request->input('lugar_puesta'),
                'narrativa'=>$request->input('narrativa'),
                'observaciones'=>$request->input('observaciones'),
                'archivo_puesta'=>$archivo,
                'unidad_id'=>$unidadRegistroId,
                'delegacion_id'=>$hechoOrigen ? ($hechoOrigen->delegacion_id ?: $usuario->delegacion_id) : $usuario->delegacion_id,
                'destacamento_id'=>$usuario->destacamento_id,
                'created_by'=>$usuario->id,
            ]);

            foreach ($request->file('fotos', []) as $orden => $foto) {
                $path=$this->fotos()->putPuestaDisposicionFile($foto,$puesta);
                $fotoPaths[]=$path;
                PuestaDisposicionFoto::create([
                    'puesta_disposicion_id'=>$puesta->id,
                    'ruta'=>$path,
                    'orden'=>$orden,
                    'created_by'=>$usuario->id,
                ]);
            }

            $this->guardarDetalles($puesta, $request);

            DB::commit();

            app(DelegacionesWhatsAppAlertService::class)->notificarPuestaDisposicion($puesta);

            return response()->json(
                $this->withFotoUrls($puesta->load(['personas','vehiculos','objetos','fotos','unidad'])),
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($fotoPaths as $path) {
                $this->fotos()->delete($path);
            }
            Log::error('Puesta a disposicion API: error al guardar', [
                'user_id'=>$usuario ? $usuario->id : null,
                'unidad_id'=>$unidadRegistroId,
                'error'=>$e->getMessage(),
                'trace'=>$e->getTraceAsString(),
            ]);

            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function show(PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $data=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);
        return response()->json($this->withFotoUrls($data));
    }

    public function archivo(PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $puesta=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);

        abort_unless($puesta->archivo_puesta, 404);

        $nombre='puesta_disposicion_' . $puesta->numero_puesta . '_' . $puesta->anio . '.pdf';

        return $this->documentos()->response($puesta->archivo_puesta,$nombre);
    }

    public function update(Request $request,PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $puesta=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);
        $motivo=$this->normalizarTextoRequerido($request->motivo);

        if (PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
            (int)$puesta->unidad_id,
            $motivo,
            !empty($puesta->hecho_id)
        )) {
            throw ValidationException::withMessages([
                'hecho_id'=>$this->mensajePuestaDebeSerVinculada(),
            ]);
        }

        $puesta->update([
            'tipo_puesta'=>$this->normalizarTextoRequerido($request->tipo_puesta),
            'motivo'=>$motivo,
            'nombre_policia'=>$this->normalizarTextoRequerido($request->nombre_policia),
        ]);

        return response()->json($puesta);
    }

    public function destroy(PuestaDisposicion $puestaDisposicion)
    {
        $usuario=Auth::user();
        $puesta=$this->findVisibleOrFail($puestaDisposicion->id,$usuario);

        $puesta->loadMissing('fotos');
        $archivo=$puesta->archivo_puesta;
        $fotos=$puesta->fotos->pluck('ruta')->all();
        $puesta->delete();

        if ($archivo) {
            $this->documentos()->delete($archivo);
        }

        foreach ($fotos as $foto) {
            $this->fotos()->delete($foto);
        }

        return response()->json(['ok'=>true]);
    }

    private function documentos(): DocumentoArchivoStorage
    {
        return app(DocumentoArchivoStorage::class);
    }

    private function fotos(): HechoFotoStorage
    {
        return app(HechoFotoStorage::class);
    }

    private function withFotoUrls(PuestaDisposicion $puesta): array
    {
        $puesta->loadMissing('fotos');
        $data=$puesta->toArray();
        $data['fotos']=$puesta->fotos->map(function (PuestaDisposicionFoto $foto) {
            return [
                'id'=>$foto->id,
                'ruta'=>$foto->ruta,
                'orden'=>$foto->orden,
                'url'=>$this->fotos()->url($foto->ruta),
            ];
        })->values()->all();

        return $data;
    }
}
