<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delegacion;
use App\Models\Grua;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GruaController extends Controller
{
    private const UNIDAD_SINIESTROS_ID = 1;
    private const UNIDAD_DELEGACIONES_ID = 2;
    private const UNIDAD_SEGURIDAD_VIAL_ID = 3;

    public function index(Request $request)
    {
        $gruas = $this->visibleGruasQuery($request)
            ->select(['id', 'nombre', 'direccion', 'telefono', 'email', 'created_at'])
            ->withCount('servicios as total_servicios')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $grua = $this->visibleGruasQuery($request)
            ->select(['id', 'nombre', 'direccion', 'telefono', 'email', 'created_at', 'updated_at'])
            ->withCount('servicios as total_servicios')
            ->findOrFail($id);

        return response()->json([
            'data' => $grua,
        ]);
    }

    public function listado(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $gruas = $this->visibleGruasQuery($request)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('nombre', 'like', "%{$q}%")
                        ->orWhere('direccion', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas,
        ]);
    }

    public function graficaSemanal(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));

        $serviciosSub = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('grua_id', $gruasIds);
            })
            ->groupBy('grua_id');

        $rows = $this->visibleGruasQuery($request)
            ->leftJoinSub($serviciosSub, 'ss', function ($join) {
                $join->on('gruas.id', '=', 'ss.grua_id');
            })
            ->select([
                'gruas.id',
                'gruas.nombre',
                DB::raw('COALESCE(ss.servicios_count, 0) as servicios_count'),
                DB::raw('ss.fecha_ultimo_servicio as fecha_ultimo_servicio'),
            ])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('gruas.id', $gruasIds);
            })
            ->orderBy('gruas.nombre')
            ->get();

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $rows,
        ]);
    }

    public function resumenSemanal(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));

        $gruas = $this->visibleGruasQuery($request)
            ->select(['id', 'nombre'])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('id', $gruasIds);
            })
            ->orderBy('nombre')
            ->get();

        $allowedGruaIds = $gruas
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        $servicios = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereIn('grua_id', $allowedGruaIds)
            ->groupBy('grua_id')
            ->get();

        $byGruaId = [];
        foreach ($servicios as $s) {
            $byGruaId[(int) $s->grua_id] = [
                'servicios_count' => (int) $s->servicios_count,
                'fecha_ultimo_servicio' => $s->fecha_ultimo_servicio,
            ];
        }

        $allowedGruaNames = $gruas
            ->pluck('nombre')
            ->filter()
            ->values()
            ->all();

        $tipos = DB::table('vehiculos')
            ->select([
                'grua',
                'tipo',
                DB::raw('COUNT(*) as c'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereIn('grua', $allowedGruaNames)
            ->whereNotNull('grua')
            ->where('grua', '<>', 'N/A')
            ->where('grua', '<>', '')
            ->whereNotNull('tipo')
            ->where('tipo', '<>', '')
            ->groupBy('grua', 'tipo')
            ->orderBy('grua')
            ->orderByDesc('c')
            ->get();

        $topTipoByNombre = [];
        foreach ($tipos as $t) {
            $nombreGrua = (string) $t->grua;
            $tipo = (string) $t->tipo;
            $c = (int) $t->c;

            if (!isset($topTipoByNombre[$nombreGrua])) {
                $topTipoByNombre[$nombreGrua] = ['tipo' => $tipo, 'c' => $c];
                continue;
            }

            if ($c > $topTipoByNombre[$nombreGrua]['c']) {
                $topTipoByNombre[$nombreGrua] = ['tipo' => $tipo, 'c' => $c];
            }
        }

        $data = [];
        foreach ($gruas as $g) {
            $id = (int) $g->id;
            $nombre = (string) $g->nombre;

            $serv = $byGruaId[$id] ?? ['servicios_count' => 0, 'fecha_ultimo_servicio' => null];
            $tipoTop = $topTipoByNombre[$nombre]['tipo'] ?? null;

            $data[] = [
                'id' => $id,
                'nombre' => $nombre,
                'servicios_count' => (int) $serv['servicios_count'],
                'fecha_ultimo_servicio' => $serv['fecha_ultimo_servicio'],
                'tipo_vehiculo_top' => $tipoTop,
            ];
        }

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }

    public function resumenSemanalDetallado(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));

        $gruas = $this->visibleGruasQuery($request)
            ->select(['id', 'nombre'])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('id', $gruasIds);
            })
            ->orderBy('nombre')
            ->get();

        $allowedGruaIds = $gruas
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        $serviciosAgg = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereIn('grua_id', $allowedGruaIds)
            ->groupBy('grua_id')
            ->get();

        $byGruaId = [];
        foreach ($serviciosAgg as $s) {
            $byGruaId[(int) $s->grua_id] = [
                'servicios_count' => (int) $s->servicios_count,
                'fecha_ultimo_servicio' => $s->fecha_ultimo_servicio,
            ];
        }

        $hvSub = DB::table('hecho_vehiculo')
            ->select([
                'vehiculo_id',
                DB::raw('MAX(hecho_id) as hecho_id'),
            ])
            ->groupBy('vehiculo_id');

        $detalle = DB::table('servicios as s')
            ->leftJoin('vehiculos as v', 'v.id', '=', 's.vehiculo_id')
            ->leftJoinSub($hvSub, 'hv', function ($join) {
                $join->on('hv.vehiculo_id', '=', 's.vehiculo_id');
            })
            ->select([
                's.grua_id',
                's.id as servicio_id',
                's.created_at as fecha_servicio',
                's.vehiculo_id',
                'hv.hecho_id as hecho_id',
                's.tipo_vehiculo',
                's.aseguradora',
                'v.placas',
                'v.marca',
                'v.linea',
                'v.modelo',
                'v.color',
            ])
            ->whereBetween('s.created_at', [$fromDate, $toDate])
            ->whereIn('s.grua_id', $allowedGruaIds)
            ->orderBy('s.grua_id')
            ->orderByDesc('s.created_at')
            ->get();

        $vehiculosByGrua = [];
        foreach ($detalle as $d) {
            $gid = (int) $d->grua_id;
            if (!isset($vehiculosByGrua[$gid])) {
                $vehiculosByGrua[$gid] = [];
            }

            $aseg = trim((string) $d->aseguradora);
            $asegUpper = mb_strtoupper($aseg, 'UTF-8');
            $sinSeguro = ($asegUpper === '' || $asegUpper === 'SIN SEGURO' || $asegUpper === 'NO' || $asegUpper === 'N/A' || $asegUpper === 'NULL');

            $vehiculosByGrua[$gid][] = [
                'servicio_id' => (int) $d->servicio_id,
                'fecha_servicio' => $d->fecha_servicio,
                'vehiculo_id' => $d->vehiculo_id ? (int) $d->vehiculo_id : null,
                'hecho_id' => $d->hecho_id ? (int) $d->hecho_id : null,
                'placas' => $d->placas,
                'marca' => $d->marca,
                'linea' => $d->linea,
                'modelo' => $d->modelo,
                'color' => $d->color,
                'tipo_vehiculo' => $d->tipo_vehiculo,
                'aseguradora' => $d->aseguradora,
                'tiene_seguro' => $sinSeguro ? 0 : 1,
            ];
        }

        $data = [];
        foreach ($gruas as $g) {
            $id = (int) $g->id;

            $serv = $byGruaId[$id] ?? [
                'servicios_count' => 0,
                'fecha_ultimo_servicio' => null,
            ];

            $data[] = [
                'id' => $id,
                'nombre' => (string) $g->nombre,
                'servicios_count' => (int) $serv['servicios_count'],
                'fecha_ultimo_servicio' => $serv['fecha_ultimo_servicio'],
                'vehiculos' => $vehiculosByGrua[$id] ?? [],
            ];
        }

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }

    private function visibleGruasQuery(Request $request)
    {
        $query = Grua::query();
        $this->applyVisibilityScope($query, $request->user());
        return $query;
    }

    private function applyVisibilityScope($query, $usuario): void
    {
        if (!$usuario) {
            $query->whereRaw('1 = 0');
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($usuario->hasRole('Superadmin') || $unidadId === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return;
        }

        if ($unidadId === self::UNIDAD_SINIESTROS_ID) {
            $query->whereHas('unidades', function ($q) {
                $q->where('unidades.id', self::UNIDAD_SINIESTROS_ID);
            });
            return;
        }

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            $delegacionIds = $this->delegacionIdsVisibles($usuario);

            if (empty($delegacionIds)) {
                $query->whereHas('delegaciones');
                return;
            }

            $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                $q->whereIn('delegaciones.id', $delegacionIds);
            });
            return;
        }

        if ($unidadId > 0) {
            $query->whereHas('unidades', function ($q) use ($unidadId) {
                $q->where('unidades.id', $unidadId);
            });
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function delegacionIdsVisibles($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);
        if ($delegacionId <= 0) {
            return [];
        }

        if (!$usuario->hasRole('Subdirector')) {
            return [$delegacionId];
        }

        $esRegional = Delegacion::query()
            ->whereKey($delegacionId)
            ->whereNull('delegacion_padre_id')
            ->exists();

        if (!$esRegional) {
            return [$delegacionId];
        }

        return Delegacion::query()
            ->where('id', $delegacionId)
            ->orWhere('delegacion_padre_id', $delegacionId)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }

        return collect($ids)
            ->flatMap(function ($id) {
                return is_string($id) ? explode(',', $id) : [$id];
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function resolveDateRange(Request $request): array
    {
        $day  = trim((string) $request->query('day'));
        $from = $request->query('from');
        $to   = $request->query('to');

        if ($day !== '') {
            $d = Carbon::parse($day);
            return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
        }

        if (!$from || !$to) {
            $toDate   = Carbon::today()->endOfDay();
            $fromDate = Carbon::today()->subDays(6)->startOfDay();
            return [$fromDate, $toDate];
        }

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->endOfDay();
        return [$fromDate, $toDate];
    }
}
