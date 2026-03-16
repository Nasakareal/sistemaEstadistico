<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstadisticasCarreterasController extends Controller
{
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct()
    {
        $this->middleware(['auth', 'can:ver estadisticas carreteras', 'unidad:carreteras']);
    }

    public function index(Request $request)
    {
        return view('estadisticas_carreteras.index');
    }

    public function kpis(Request $request)
    {
        return $this->cachedJson($request, 'kpis', function () use ($request) {
            $operativos = $this->baseOperativosQuery($request);
            $this->applySearchOperativos($operativos, $request);

            $puestas = $this->basePuestasQuery($request);
            $this->applySearchPuestas($puestas, $request);

            $totalOperativos = (clone $operativos)
                ->select('operativos.captura_uuid')
                ->distinct()
                ->count('operativos.captura_uuid');

            $totalPuestas = (clone $puestas)->count('puestas_disposicion.id');

            $personas = $this->basePuestasPersonasQuery($request);
            $this->applySearchPuestasPersonas($personas, $request);
            $totalPersonas = (clone $personas)->count('puestas_disposicion_personas.id');

            $vehiculos = $this->basePuestasVehiculosQuery($request);
            $this->applySearchPuestasVehiculos($vehiculos, $request);
            $totalVehiculos = (clone $vehiculos)->count('puestas_disposicion_vehiculos.id');

            $objetos = $this->basePuestasObjetosQuery($request);
            $this->applySearchPuestasObjetos($objetos, $request);
            $totalObjetos = (clone $objetos)->count('puestas_disposicion_objetos.id');

            $porTipoPuesta = (clone $puestas)
                ->selectRaw("COALESCE(NULLIF(TRIM(puestas_disposicion.tipo_puesta), ''), 'NO ESPECIFICADO') as label, COUNT(puestas_disposicion.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            $porMotivo = (clone $puestas)
                ->selectRaw("COALESCE(NULLIF(TRIM(puestas_disposicion.motivo), ''), 'NO ESPECIFICADO') as label, COUNT(puestas_disposicion.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            $porCalidadPersona = (clone $personas)
                ->selectRaw("COALESCE(NULLIF(TRIM(puestas_disposicion_personas.calidad), ''), 'NO ESPECIFICADO') as label, COUNT(puestas_disposicion_personas.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            $porTipoObjeto = (clone $objetos)
                ->selectRaw("COALESCE(NULLIF(TRIM(puestas_disposicion_objetos.tipo_objeto), ''), 'NO ESPECIFICADO') as label, COUNT(puestas_disposicion_objetos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            return [
                'totales' => [
                    'actividades' => 0,
                    'operativos' => (int) $totalOperativos,
                    'puestas_disposicion' => (int) $totalPuestas,
                    'personas' => (int) $totalPersonas,
                    'vehiculos' => (int) $totalVehiculos,
                    'objetos' => (int) $totalObjetos,
                ],
                'top' => [
                    'tipo_puesta' => $porTipoPuesta,
                    'motivo' => $porMotivo,
                    'calidad_persona' => $porCalidadPersona,
                    'tipo_objeto' => $porTipoObjeto,
                ],
            ];
        });
    }

    public function seriesActividades(Request $request)
    {
        return response()->json(['group' => $this->grouping($request), 'series' => []]);
    }

    public function seriesOperativos(Request $request)
    {
        return $this->cachedJson($request, 'seriesOperativos', function () use ($request) {
            $group = $this->grouping($request);

            $q = $this->baseOperativosQuery($request);
            $this->applySearchOperativos($q, $request);

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(operativos.fecha, '%Y-%m-01') as x, COUNT(DISTINCT operativos.captura_uuid) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(operativos.fecha) as x, COUNT(DISTINCT operativos.captura_uuid) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'series' => $rows];
        });
    }

    public function seriesPuestasDisposicion(Request $request)
    {
        return $this->cachedJson($request, 'seriesPuestasDisposicion', function () use ($request) {
            $group = $this->grouping($request);

            $q = $this->basePuestasQuery($request);
            $this->applySearchPuestas($q, $request);

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(puestas_disposicion.fecha_puesta, '%Y-%m-01') as x, COUNT(puestas_disposicion.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(puestas_disposicion.fecha_puesta) as x, COUNT(puestas_disposicion.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'series' => $rows];
        });
    }

    public function actividades(Request $request)
    {
        return response()->json([
            'current_page' => 1,
            'data' => [],
            'from' => null,
            'last_page' => 1,
            'per_page' => (int) $request->query('per', 25),
            'to' => null,
            'total' => 0,
        ]);
    }

    public function operativos(Request $request)
    {
        return $this->cachedJson($request, 'operativos', function () use ($request) {
            $per = (int) $request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = $this->baseOperativosQuery($request);
            $this->applySearchOperativos($q, $request);

            $q->select(
                'operativos.captura_uuid',
                'operativos.fecha',
                'operativos.hora',
                'operativos.unidad_org_id',
                'operativos.delegacion_id',
                'operativos.destacamento_id',
                'operativos.descripcion',
                'operativos.lugar'
            )
            ->selectRaw('COUNT(operativos.id) as total_operativos')
            ->selectRaw('SUM(COALESCE(operativos.dispositivos_realizados,0)) as dispositivos_realizados')
            ->selectRaw('SUM(COALESCE(operativos.vehiculos_inspeccionados,0)) as vehiculos_inspeccionados')
            ->selectRaw('SUM(COALESCE(operativos.personas_inspeccionadas,0)) as personas_inspeccionadas')
            ->selectRaw('SUM(COALESCE(operativos.vehiculos_impactados,0)) as vehiculos_impactados')
            ->selectRaw('SUM(COALESCE(operativos.personas_impactadas,0)) as personas_impactadas')
            ->selectRaw('SUM(COALESCE(operativos.estado_fuerza_participante,0)) as estado_fuerza_participante')
            ->selectRaw('SUM(COALESCE(operativos.kilometros_recorridos,0)) as kilometros_recorridos')
            ->groupBy(
                'operativos.captura_uuid',
                'operativos.fecha',
                'operativos.hora',
                'operativos.unidad_org_id',
                'operativos.delegacion_id',
                'operativos.destacamento_id',
                'operativos.descripcion',
                'operativos.lugar'
            )
            ->orderByDesc('operativos.fecha')
            ->orderByDesc('operativos.hora');

            return $q->paginate($per)->toArray();
        });
    }

    public function puestasDisposicion(Request $request)
    {
        return $this->cachedJson($request, 'puestasDisposicion', function () use ($request) {
            $per = (int) $request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = $this->basePuestasQuery($request);
            $this->applySearchPuestas($q, $request);

            $q->leftJoin('puestas_disposicion_personas', 'puestas_disposicion_personas.puesta_disposicion_id', '=', 'puestas_disposicion.id')
                ->leftJoin('puestas_disposicion_vehiculos', 'puestas_disposicion_vehiculos.puesta_disposicion_id', '=', 'puestas_disposicion.id')
                ->leftJoin('puestas_disposicion_objetos', 'puestas_disposicion_objetos.puesta_disposicion_id', '=', 'puestas_disposicion.id')
                ->select([
                    'puestas_disposicion.id',
                    'puestas_disposicion.numero_puesta',
                    'puestas_disposicion.anio',
                    'puestas_disposicion.tipo_puesta',
                    'puestas_disposicion.motivo',
                    'puestas_disposicion.estatus',
                    'puestas_disposicion.nombre_policia',
                    'puestas_disposicion.nombre_mp',
                    'puestas_disposicion.autoridad_receptora',
                    'puestas_disposicion.area',
                    'puestas_disposicion.carpeta_investigacion',
                    'puestas_disposicion.oficio',
                    'puestas_disposicion.fecha_puesta',
                    'puestas_disposicion.hora_puesta',
                    'puestas_disposicion.lugar_puesta',
                    'puestas_disposicion.unidad_id',
                    'puestas_disposicion.delegacion_id',
                    'puestas_disposicion.destacamento_id',
                ])
                ->selectRaw('COUNT(DISTINCT puestas_disposicion_personas.id) as total_personas')
                ->selectRaw('COUNT(DISTINCT puestas_disposicion_vehiculos.id) as total_vehiculos')
                ->selectRaw('COUNT(DISTINCT puestas_disposicion_objetos.id) as total_objetos')
                ->groupBy(
                    'puestas_disposicion.id',
                    'puestas_disposicion.numero_puesta',
                    'puestas_disposicion.anio',
                    'puestas_disposicion.tipo_puesta',
                    'puestas_disposicion.motivo',
                    'puestas_disposicion.estatus',
                    'puestas_disposicion.nombre_policia',
                    'puestas_disposicion.nombre_mp',
                    'puestas_disposicion.autoridad_receptora',
                    'puestas_disposicion.area',
                    'puestas_disposicion.carpeta_investigacion',
                    'puestas_disposicion.oficio',
                    'puestas_disposicion.fecha_puesta',
                    'puestas_disposicion.hora_puesta',
                    'puestas_disposicion.lugar_puesta',
                    'puestas_disposicion.unidad_id',
                    'puestas_disposicion.delegacion_id',
                    'puestas_disposicion.destacamento_id'
                )
                ->orderByDesc('puestas_disposicion.fecha_puesta')
                ->orderByDesc('puestas_disposicion.numero_puesta');

            return $q->paginate($per)->toArray();
        });
    }

    public function exportActividades(Request $request)
    {
        $filename = 'actividades_carreteras_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['sin_datos']);
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportOperativos(Request $request)
    {
        $q = $this->baseOperativosQuery($request);
        $this->applySearchOperativos($q, $request);

        $q->select(
            'operativos.captura_uuid',
            'operativos.fecha',
            'operativos.hora',
            'operativos.unidad_org_id',
            'operativos.delegacion_id',
            'operativos.destacamento_id',
            'operativos.descripcion',
            'operativos.lugar'
        )
        ->selectRaw('COUNT(operativos.id) as total_operativos')
        ->selectRaw('SUM(COALESCE(operativos.dispositivos_realizados,0)) as dispositivos_realizados')
        ->selectRaw('SUM(COALESCE(operativos.vehiculos_inspeccionados,0)) as vehiculos_inspeccionados')
        ->selectRaw('SUM(COALESCE(operativos.personas_inspeccionadas,0)) as personas_inspeccionadas')
        ->selectRaw('SUM(COALESCE(operativos.vehiculos_impactados,0)) as vehiculos_impactados')
        ->selectRaw('SUM(COALESCE(operativos.personas_impactadas,0)) as personas_impactadas')
        ->selectRaw('SUM(COALESCE(operativos.estado_fuerza_participante,0)) as estado_fuerza_participante')
        ->selectRaw('SUM(COALESCE(operativos.kilometros_recorridos,0)) as kilometros_recorridos')
        ->groupBy(
            'operativos.captura_uuid',
            'operativos.fecha',
            'operativos.hora',
            'operativos.unidad_org_id',
            'operativos.delegacion_id',
            'operativos.destacamento_id',
            'operativos.descripcion',
            'operativos.lugar'
        )
        ->orderByDesc('operativos.fecha')
        ->orderByDesc('operativos.hora');

        $filename = 'operativos_carreteras_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'captura_uuid',
                'fecha',
                'hora',
                'unidad_org_id',
                'delegacion_id',
                'destacamento_id',
                'descripcion',
                'lugar',
                'total_operativos',
                'dispositivos_realizados',
                'vehiculos_inspeccionados',
                'personas_inspeccionadas',
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'kilometros_recorridos',
            ]);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->captura_uuid,
                        $r->fecha,
                        $r->hora,
                        $r->unidad_org_id,
                        $r->delegacion_id,
                        $r->destacamento_id,
                        $r->descripcion,
                        $r->lugar,
                        $r->total_operativos,
                        $r->dispositivos_realizados,
                        $r->vehiculos_inspeccionados,
                        $r->personas_inspeccionadas,
                        $r->vehiculos_impactados,
                        $r->personas_impactadas,
                        $r->estado_fuerza_participante,
                        $r->kilometros_recorridos,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportPuestasDisposicion(Request $request)
    {
        $q = $this->basePuestasQuery($request);
        $this->applySearchPuestas($q, $request);

        $q->leftJoin('puestas_disposicion_personas', 'puestas_disposicion_personas.puesta_disposicion_id', '=', 'puestas_disposicion.id')
            ->leftJoin('puestas_disposicion_vehiculos', 'puestas_disposicion_vehiculos.puesta_disposicion_id', '=', 'puestas_disposicion.id')
            ->leftJoin('puestas_disposicion_objetos', 'puestas_disposicion_objetos.puesta_disposicion_id', '=', 'puestas_disposicion.id')
            ->select([
                'puestas_disposicion.id',
                'puestas_disposicion.numero_puesta',
                'puestas_disposicion.anio',
                'puestas_disposicion.tipo_puesta',
                'puestas_disposicion.motivo',
                'puestas_disposicion.estatus',
                'puestas_disposicion.nombre_policia',
                'puestas_disposicion.nombre_mp',
                'puestas_disposicion.autoridad_receptora',
                'puestas_disposicion.area',
                'puestas_disposicion.carpeta_investigacion',
                'puestas_disposicion.oficio',
                'puestas_disposicion.fecha_puesta',
                'puestas_disposicion.hora_puesta',
                'puestas_disposicion.lugar_puesta',
                'puestas_disposicion.unidad_id',
                'puestas_disposicion.delegacion_id',
                'puestas_disposicion.destacamento_id',
            ])
            ->selectRaw('COUNT(DISTINCT puestas_disposicion_personas.id) as total_personas')
            ->selectRaw('COUNT(DISTINCT puestas_disposicion_vehiculos.id) as total_vehiculos')
            ->selectRaw('COUNT(DISTINCT puestas_disposicion_objetos.id) as total_objetos')
            ->groupBy(
                'puestas_disposicion.id',
                'puestas_disposicion.numero_puesta',
                'puestas_disposicion.anio',
                'puestas_disposicion.tipo_puesta',
                'puestas_disposicion.motivo',
                'puestas_disposicion.estatus',
                'puestas_disposicion.nombre_policia',
                'puestas_disposicion.nombre_mp',
                'puestas_disposicion.autoridad_receptora',
                'puestas_disposicion.area',
                'puestas_disposicion.carpeta_investigacion',
                'puestas_disposicion.oficio',
                'puestas_disposicion.fecha_puesta',
                'puestas_disposicion.hora_puesta',
                'puestas_disposicion.lugar_puesta',
                'puestas_disposicion.unidad_id',
                'puestas_disposicion.delegacion_id',
                'puestas_disposicion.destacamento_id'
            )
            ->orderByDesc('puestas_disposicion.fecha_puesta')
            ->orderByDesc('puestas_disposicion.numero_puesta');

        $filename = 'puestas_disposicion_carreteras_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'id',
                'numero_puesta',
                'anio',
                'tipo_puesta',
                'motivo',
                'estatus',
                'nombre_policia',
                'nombre_mp',
                'autoridad_receptora',
                'area',
                'carpeta_investigacion',
                'oficio',
                'fecha_puesta',
                'hora_puesta',
                'lugar_puesta',
                'unidad_id',
                'delegacion_id',
                'destacamento_id',
                'total_personas',
                'total_vehiculos',
                'total_objetos',
            ]);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->numero_puesta,
                        $r->anio,
                        $r->tipo_puesta,
                        $r->motivo,
                        $r->estatus,
                        $r->nombre_policia,
                        $r->nombre_mp,
                        $r->autoridad_receptora,
                        $r->area,
                        $r->carpeta_investigacion,
                        $r->oficio,
                        $r->fecha_puesta,
                        $r->hora_puesta,
                        $r->lugar_puesta,
                        $r->unidad_id,
                        $r->delegacion_id,
                        $r->destacamento_id,
                        $r->total_personas,
                        $r->total_vehiculos,
                        $r->total_objetos,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function baseOperativosQuery(Request $request)
    {
        $q = DB::table('operativos')
            ->whereNotNull('operativos.captura_uuid');

        $this->applyOperativosDateFilter($q, $request);
        $this->applyOperativosVisibilityScope($q, $request->user());

        if ($request->filled('operativo_catalogo_id')) {
            $catalogoId = (int) $request->query('operativo_catalogo_id');

            $q->whereExists(function ($sub) use ($catalogoId) {
                $sub->select(DB::raw(1))
                    ->from('operativos as op2')
                    ->whereColumn('op2.captura_uuid', 'operativos.captura_uuid')
                    ->where('op2.operativo_catalogo_id', $catalogoId);
            });
        }

        return $q;
    }

    private function basePuestasQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion');

        $this->applyPuestasDateFilter($q, $request);
        $this->applyPuestasVisibilityScope($q, $request->user());

        if ($request->filled('motivo')) {
            $q->where('puestas_disposicion.motivo', strtoupper(trim((string) $request->query('motivo'))));
        }

        if ($request->filled('tipo_puesta')) {
            $q->where('puestas_disposicion.tipo_puesta', strtoupper(trim((string) $request->query('tipo_puesta'))));
        }

        return $q;
    }

    private function basePuestasPersonasQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion_personas')
            ->join('puestas_disposicion', 'puestas_disposicion.id', '=', 'puestas_disposicion_personas.puesta_disposicion_id');

        $this->applyPuestasDateFilter($q, $request);
        $this->applyPuestasVisibilityScope($q, $request->user());

        if ($request->filled('motivo')) {
            $q->where('puestas_disposicion.motivo', strtoupper(trim((string) $request->query('motivo'))));
        }

        if ($request->filled('tipo_puesta')) {
            $q->where('puestas_disposicion.tipo_puesta', strtoupper(trim((string) $request->query('tipo_puesta'))));
        }

        return $q;
    }

    private function basePuestasVehiculosQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion_vehiculos')
            ->join('puestas_disposicion', 'puestas_disposicion.id', '=', 'puestas_disposicion_vehiculos.puesta_disposicion_id');

        $this->applyPuestasDateFilter($q, $request);
        $this->applyPuestasVisibilityScope($q, $request->user());

        if ($request->filled('motivo')) {
            $q->where('puestas_disposicion.motivo', strtoupper(trim((string) $request->query('motivo'))));
        }

        if ($request->filled('tipo_puesta')) {
            $q->where('puestas_disposicion.tipo_puesta', strtoupper(trim((string) $request->query('tipo_puesta'))));
        }

        return $q;
    }

    private function basePuestasObjetosQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion_objetos')
            ->join('puestas_disposicion', 'puestas_disposicion.id', '=', 'puestas_disposicion_objetos.puesta_disposicion_id');

        $this->applyPuestasDateFilter($q, $request);
        $this->applyPuestasVisibilityScope($q, $request->user());

        if ($request->filled('motivo')) {
            $q->where('puestas_disposicion.motivo', strtoupper(trim((string) $request->query('motivo'))));
        }

        if ($request->filled('tipo_puesta')) {
            $q->where('puestas_disposicion.tipo_puesta', strtoupper(trim((string) $request->query('tipo_puesta'))));
        }

        return $q;
    }

    private function applyOperativosDateFilter($q, Request $request): void
    {
        $desde = trim((string) $request->query('desde', ''));
        $hasta = trim((string) $request->query('hasta', ''));

        if ($desde !== '' && $hasta !== '') {
            $q->whereBetween('operativos.fecha', [$desde, $hasta]);
        } elseif ($desde !== '') {
            $q->whereDate('operativos.fecha', '>=', $desde);
        } elseif ($hasta !== '') {
            $q->whereDate('operativos.fecha', '<=', $hasta);
        }
    }

    private function applyPuestasDateFilter($q, Request $request): void
    {
        $desde = trim((string) $request->query('desde', ''));
        $hasta = trim((string) $request->query('hasta', ''));

        if ($desde !== '' && $hasta !== '') {
            $q->whereBetween('puestas_disposicion.fecha_puesta', [$desde, $hasta]);
        } elseif ($desde !== '') {
            $q->whereDate('puestas_disposicion.fecha_puesta', '>=', $desde);
        } elseif ($hasta !== '') {
            $q->whereDate('puestas_disposicion.fecha_puesta', '<=', $hasta);
        }
    }

    private function applySearchOperativos($q, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('operativos.lugar', 'like', "%$search%")
                ->orWhere('operativos.descripcion', 'like', "%$search%")
                ->orWhere('operativos.observaciones', 'like', "%$search%")
                ->orWhere('operativos.crps_participantes', 'like', "%$search%");
        });
    }

    private function applySearchPuestas($q, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('puestas_disposicion.numero_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion.tipo_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion.motivo', 'like', "%$search%")
                ->orWhere('puestas_disposicion.nombre_policia', 'like', "%$search%")
                ->orWhere('puestas_disposicion.nombre_mp', 'like', "%$search%")
                ->orWhere('puestas_disposicion.autoridad_receptora', 'like', "%$search%")
                ->orWhere('puestas_disposicion.carpeta_investigacion', 'like', "%$search%")
                ->orWhere('puestas_disposicion.oficio', 'like', "%$search%")
                ->orWhere('puestas_disposicion.lugar_puesta', 'like', "%$search%");
        });
    }

    private function applySearchPuestasPersonas($q, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('puestas_disposicion.numero_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion.motivo', 'like', "%$search%")
                ->orWhere('puestas_disposicion.tipo_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion_personas.nombre_completo', 'like', "%$search%")
                ->orWhere('puestas_disposicion_personas.alias', 'like', "%$search%")
                ->orWhere('puestas_disposicion_personas.calidad', 'like', "%$search%")
                ->orWhere('puestas_disposicion_personas.delito_o_motivo', 'like', "%$search%");
        });
    }

    private function applySearchPuestasVehiculos($q, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('puestas_disposicion.numero_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion.motivo', 'like', "%$search%")
                ->orWhere('puestas_disposicion.tipo_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.tipo', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.marca', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.submarca', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.placas', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.serie', 'like', "%$search%")
                ->orWhere('puestas_disposicion_vehiculos.calidad', 'like', "%$search%");
        });
    }

    private function applySearchPuestasObjetos($q, Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('puestas_disposicion.numero_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion.motivo', 'like', "%$search%")
                ->orWhere('puestas_disposicion.tipo_puesta', 'like', "%$search%")
                ->orWhere('puestas_disposicion_objetos.tipo_objeto', 'like', "%$search%")
                ->orWhere('puestas_disposicion_objetos.descripcion', 'like', "%$search%")
                ->orWhere('puestas_disposicion_objetos.cadena_custodia', 'like', "%$search%");
        });
    }

    private function applyOperativosVisibilityScope($query, $usuario): void
    {
        if (!$usuario) {
            $query->whereRaw('1=0');
            return;
        }

        if ($usuario->hasRole('Superadmin')) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $unidadCarreterasId = (int) Unidad::query()
            ->where('slug', 'carreteras')
            ->value('id');

        if ($unidadCarreterasId > 0 && $unidadId === $unidadCarreterasId) {
            $query->where('operativos.unidad_org_id', $unidadCarreterasId);

            if (!is_null($usuario->delegacion_id) && $this->hasColumn('operativos', 'delegacion_id')) {
                $query->where('operativos.delegacion_id', $usuario->delegacion_id);
            }

            if (!is_null($usuario->destacamento_id) && $this->hasColumn('operativos', 'destacamento_id')) {
                $query->where('operativos.destacamento_id', $usuario->destacamento_id);
            }

            return;
        }

        if ($unidadId === 2) {
            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $esRegional = Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($usuario->hasRole('Subdirector')) {
                if ($esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $query->whereIn('operativos.delegacion_id', $ids);
                } else {
                    $query->where('operativos.delegacion_id', $delegacionId);
                }
            } else {
                $query->where('operativos.delegacion_id', $delegacionId);
            }

            return;
        }

        if ($unidadId > 0) {
            $query->where('operativos.unidad_org_id', $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function applyPuestasVisibilityScope($query, $usuario): void
    {
        if (!$usuario) {
            $query->whereRaw('1=0');
            return;
        }

        if ($usuario->hasRole('Superadmin')) {
            return;
        }

        if ($usuario->unidad_id) {
            $query->where('puestas_disposicion.unidad_id', $usuario->unidad_id);
        } else {
            $query->whereRaw('1=0');
            return;
        }

        if (!is_null($usuario->delegacion_id) && $this->hasColumn('puestas_disposicion', 'delegacion_id')) {
            $query->where('puestas_disposicion.delegacion_id', $usuario->delegacion_id);
        }

        if (!is_null($usuario->destacamento_id) && $this->hasColumn('puestas_disposicion', 'destacamento_id')) {
            $query->where('puestas_disposicion.destacamento_id', $usuario->destacamento_id);
        }
    }

    private function grouping(Request $request): string
    {
        $g = strtolower(trim((string) $request->query('group', 'day')));
        return in_array($g, ['day', 'month'], true) ? $g : 'day';
    }

    private function cachedJson(Request $request, string $key, \Closure $fn)
    {
        $ttl = (int) $request->query('cache_ttl', 60);
        $ttl = max(0, min(600, $ttl));

        if ($ttl === 0) {
            return response()->json($fn());
        }

        $hash = sha1($request->fullUrl());
        $cacheKey = "estadisticas_carreteras:$key:$hash";

        $data = Cache::remember($cacheKey, $ttl, function () use ($fn) {
            return $fn();
        });

        return response()->json($data);
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            return $this->tableExistsCache[$table] = DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return $this->tableExistsCache[$table] = false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        try {
            if (!$this->hasTable($table)) {
                return $this->columnExistsCache[$key] = false;
            }

            return $this->columnExistsCache[$key] = DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return $this->columnExistsCache[$key] = false;
        }
    }
}
