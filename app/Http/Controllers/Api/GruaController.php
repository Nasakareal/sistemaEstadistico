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
            ->with('unidades:id,nombre,slug')
            ->with('delegaciones:id,clave,nombre,municipio')
            ->withCount(['servicios as total_servicios' => function ($q) use ($request) {
                $this->applyServiciosRequestScope($q, $request);
            }])
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
            ->with('unidades:id,nombre,slug')
            ->with('delegaciones:id,clave,nombre,municipio')
            ->withCount(['servicios as total_servicios' => function ($q) use ($request) {
                $this->applyServiciosRequestScope($q, $request);
            }])
            ->findOrFail($id);

        return response()->json([
            'data' => $grua,
        ]);
    }

    public function listado(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $gruas = $this->visibleGruasQuery($request)
            ->with('unidades:id,nombre,slug')
            ->with('delegaciones:id,clave,nombre,municipio')
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

    public function delegaciones(Request $request)
    {
        $query = Delegacion::query()
            ->select(['id', 'clave', 'nombre', 'municipio', 'delegacion_padre_id'])
            ->where('activa', 1);

        $usuario = $request->user();
        if (!$this->tieneAccesoGlobal($usuario)) {
            if ((int) ($usuario->unidad_id ?? 0) !== self::UNIDAD_DELEGACIONES_ID) {
                $query->whereRaw('1 = 0');
            } else {
                $delegacionIds = $this->delegacionIdsVisibles($usuario);
                if (!empty($delegacionIds)) {
                    $query->whereIn('id', $delegacionIds);
                }
            }
        }

        $delegaciones = $query
            ->where(function ($q) {
                $q->whereHas('gruas')
                    ->orWhereExists(function ($qq) {
                        $qq->selectRaw('1')
                            ->from('servicios')
                            ->whereColumn('servicios.delegacion_id', 'delegaciones.id')
                            ->where('servicios.unidad_id', self::UNIDAD_DELEGACIONES_ID);

                        $this->applyServiciosOrigenVinculadoScope($qq);
                    });
            })
            ->orderBy('nombre')
            ->get()
            ->map(function (Delegacion $delegacion) {
                return [
                    'id' => (int) $delegacion->id,
                    'clave' => $delegacion->clave,
                    'nombre' => $delegacion->nombre,
                    'nombre_con_clave' => $delegacion->nombre_con_clave,
                    'municipio' => $delegacion->municipio,
                    'delegacion_padre_id' => $delegacion->delegacion_padre_id ? (int) $delegacion->delegacion_padre_id : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $delegaciones,
        ]);
    }

    public function graficaSemanal(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));
        $incluirSinServicios = $this->includeGruasSinServicios($request);

        $serviciosSub = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('grua_id', $gruasIds);
            });

        $this->applyServiciosRequestScope($serviciosSub, $request);

        $serviciosSub->groupBy('grua_id');

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
            ->when(!$incluirSinServicios, function ($q) {
                $q->whereNotNull('ss.grua_id');
            })
            ->orderBy('gruas.nombre')
            ->get();

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
                'incluir_sin_servicios' => $incluirSinServicios,
            ],
            'data' => $rows,
        ]);
    }

    public function resumenSemanal(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));
        $incluirSinServicios = $this->includeGruasSinServicios($request);

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
            ->whereIn('grua_id', $allowedGruaIds);

        $this->applyServiciosRequestScope($servicios, $request);

        $servicios = $servicios
            ->groupBy('grua_id')
            ->get();

        $byGruaId = [];
        foreach ($servicios as $s) {
            $byGruaId[(int) $s->grua_id] = [
                'servicios_count' => (int) $s->servicios_count,
                'fecha_ultimo_servicio' => $s->fecha_ultimo_servicio,
            ];
        }

        $tipoExpr = "COALESCE(NULLIF(s.tipo_vehiculo, ''), NULLIF(v.tipo, ''))";

        $tipos = DB::table('servicios as s')
            ->leftJoin('vehiculos as v', 'v.id', '=', 's.vehiculo_id')
            ->select([
                's.grua_id',
                DB::raw("{$tipoExpr} as tipo"),
                DB::raw('COUNT(*) as c'),
            ])
            ->whereBetween('s.created_at', [$fromDate, $toDate])
            ->whereIn('s.grua_id', $allowedGruaIds);

        $this->applyServiciosRequestScope($tipos, $request, 's');

        $tipos = $tipos
            ->whereRaw("{$tipoExpr} IS NOT NULL")
            ->groupBy('s.grua_id')
            ->groupByRaw($tipoExpr)
            ->orderBy('s.grua_id')
            ->orderByDesc('c')
            ->get();

        $topTipoByGruaId = [];
        foreach ($tipos as $t) {
            $gruaId = (int) $t->grua_id;
            $tipo = (string) $t->tipo;
            $c = (int) $t->c;

            if (!isset($topTipoByGruaId[$gruaId])) {
                $topTipoByGruaId[$gruaId] = ['tipo' => $tipo, 'c' => $c];
                continue;
            }

            if ($c > $topTipoByGruaId[$gruaId]['c']) {
                $topTipoByGruaId[$gruaId] = ['tipo' => $tipo, 'c' => $c];
            }
        }

        $data = [];
        foreach ($gruas as $g) {
            $id = (int) $g->id;
            $nombre = (string) $g->nombre;

            $serv = $byGruaId[$id] ?? ['servicios_count' => 0, 'fecha_ultimo_servicio' => null];
            $tipoTop = $topTipoByGruaId[$id]['tipo'] ?? null;

            if (!$incluirSinServicios && (int) $serv['servicios_count'] === 0) {
                continue;
            }

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
                'incluir_sin_servicios' => $incluirSinServicios,
            ],
            'data' => $data,
        ]);
    }

    public function resumenSemanalDetallado(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $gruasIds = $this->normalizeIds($request->query('gruas', []));
        $incluirSinServicios = $this->includeGruasSinServicios($request);

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
            ->whereIn('grua_id', $allowedGruaIds);

        $this->applyServiciosRequestScope($serviciosAgg, $request);

        $serviciosAgg = $serviciosAgg
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

        $avSub = DB::table('actividad_vehiculo')
            ->select([
                'vehiculo_id',
                DB::raw('MAX(actividad_id) as actividad_id'),
            ])
            ->groupBy('vehiculo_id');

        $odvSub = DB::table('operativo_dispositivo_vehiculo')
            ->select([
                'vehiculo_id',
                DB::raw('MAX(operativo_dispositivo_id) as operativo_dispositivo_id'),
            ])
            ->groupBy('vehiculo_id');

        $pdvSub = DB::table('puestas_disposicion_vehiculos')
            ->select([
                'vehiculo_id',
                DB::raw('MAX(puesta_disposicion_id) as puesta_disposicion_id'),
            ])
            ->whereNotNull('vehiculo_id')
            ->groupBy('vehiculo_id');

        $detalle = DB::table('servicios as s')
            ->leftJoin('vehiculos as v', 'v.id', '=', 's.vehiculo_id')
            ->leftJoinSub($hvSub, 'hv', function ($join) {
                $join->on('hv.vehiculo_id', '=', 's.vehiculo_id');
            })
            ->leftJoinSub($avSub, 'av', function ($join) {
                $join->on('av.vehiculo_id', '=', 's.vehiculo_id');
            })
            ->leftJoinSub($odvSub, 'odv', function ($join) {
                $join->on('odv.vehiculo_id', '=', 's.vehiculo_id');
            })
            ->leftJoinSub($pdvSub, 'pdv', function ($join) {
                $join->on('pdv.vehiculo_id', '=', 's.vehiculo_id');
            })
            ->select([
                's.grua_id',
                's.id as servicio_id',
                's.created_at as fecha_servicio',
                's.vehiculo_id',
                's.unidad_id',
                's.delegacion_id',
                'hv.hecho_id as hecho_id',
                'av.actividad_id as actividad_id',
                'odv.operativo_dispositivo_id as operativo_dispositivo_id',
                'pdv.puesta_disposicion_id as puesta_disposicion_id',
                's.tipo_vehiculo',
                's.aseguradora',
                'v.placas',
                'v.marca',
                'v.linea',
                'v.modelo',
                'v.color',
            ])
            ->whereBetween('s.created_at', [$fromDate, $toDate])
            ->whereIn('s.grua_id', $allowedGruaIds);

        $this->applyServiciosRequestScope($detalle, $request, 's');

        $detalle = $detalle
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
                'unidad_id' => $d->unidad_id ? (int) $d->unidad_id : null,
                'delegacion_id' => $d->delegacion_id ? (int) $d->delegacion_id : null,
                'hecho_id' => $d->hecho_id ? (int) $d->hecho_id : null,
                'actividad_id' => $d->actividad_id ? (int) $d->actividad_id : null,
                'operativo_dispositivo_id' => $d->operativo_dispositivo_id ? (int) $d->operativo_dispositivo_id : null,
                'puesta_disposicion_id' => $d->puesta_disposicion_id ? (int) $d->puesta_disposicion_id : null,
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

            if (!$incluirSinServicios && (int) $serv['servicios_count'] === 0) {
                continue;
            }

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
                'incluir_sin_servicios' => $incluirSinServicios,
            ],
            'data' => $data,
        ]);
    }

    private function visibleGruasQuery(Request $request)
    {
        $query = Grua::query();
        $this->applyVisibilityScope($query, $request->user());
        $this->applyRequestedUnidadScope($query, $request);
        return $query;
    }

    private function applyRequestedUnidadScope($query, Request $request): void
    {
        $unidadId = $this->requestedUnidadId($request);
        if ($unidadId <= 0) {
            return;
        }

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            $delegacionIds = $this->effectiveDelegacionIds($request);

            $query->where(function ($q) use ($unidadId) {
                $q->whereHas('unidades', function ($qq) use ($unidadId) {
                    $qq->where('unidades.id', $unidadId);
                })->orWhereHas('delegaciones');
            });

            if (!empty($delegacionIds)) {
                $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                    $q->whereIn('delegaciones.id', $delegacionIds);
                });
            }

            return;
        }

        $query->whereHas('unidades', function ($q) use ($unidadId) {
            $q->where('unidades.id', $unidadId);
        });
    }

    private function applyVisibilityScope($query, $usuario): void
    {
        if (!$usuario) {
            $query->whereRaw('1 = 0');
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($this->tieneAccesoGlobal($usuario)) {
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

    private function applyServiciosRequestScope($query, Request $request, string $alias = ''): void
    {
        $col = function (string $column) use ($alias): string {
            return $alias !== '' ? "{$alias}.{$column}" : $column;
        };

        $this->applyServiciosOrigenVinculadoScope($query, $alias);

        $unidadId = $this->requestedUnidadId($request);
        if ($unidadId > 0) {
            $query->where($col('unidad_id'), $unidadId);
        }

        if ($unidadId !== self::UNIDAD_DELEGACIONES_ID) {
            return;
        }

        $delegacionIds = $this->effectiveDelegacionIds($request);
        if (!empty($delegacionIds)) {
            $query->whereIn($col('delegacion_id'), $delegacionIds);
        }
    }

    private function applyServiciosOrigenVinculadoScope($query, string $alias = ''): void
    {
        $serviciosTable = $alias !== '' ? $alias : 'servicios';
        $vehiculoColumn = "{$serviciosTable}.vehiculo_id";

        $query->whereNotNull($vehiculoColumn)
            ->whereExists(function ($vehiculo) use ($vehiculoColumn) {
                $vehiculo->selectRaw('1')
                    ->from('vehiculos as veh_origen')
                    ->whereColumn('veh_origen.id', $vehiculoColumn)
                    ->where(function ($origen) {
                        $origen->whereExists(function ($hechos) {
                            $hechos->selectRaw('1')
                                ->from('hecho_vehiculo as hv_origen')
                                ->whereColumn('hv_origen.vehiculo_id', 'veh_origen.id');
                        })->orWhereExists(function ($actividades) {
                            $actividades->selectRaw('1')
                                ->from('actividad_vehiculo as av_origen')
                                ->whereColumn('av_origen.vehiculo_id', 'veh_origen.id');
                        })->orWhereExists(function ($dispositivos) {
                            $dispositivos->selectRaw('1')
                                ->from('operativo_dispositivo_vehiculo as odv_origen')
                                ->whereColumn('odv_origen.vehiculo_id', 'veh_origen.id');
                        })->orWhereExists(function ($puestas) {
                            $puestas->selectRaw('1')
                                ->from('puestas_disposicion_vehiculos as pdv_origen')
                                ->whereColumn('pdv_origen.vehiculo_id', 'veh_origen.id');
                        });
                    });
            });
    }

    private function requestedUnidadId(Request $request): int
    {
        $unidadId = (int) $request->query('unidad_id', 0);
        $origen = mb_strtolower(trim((string) $request->query('origen', '')), 'UTF-8');

        if ($unidadId <= 0) {
            if (in_array($origen, ['siniestro', 'siniestros'], true)) {
                $unidadId = self::UNIDAD_SINIESTROS_ID;
            } elseif (in_array($origen, ['delegacion', 'delegaciones'], true)) {
                $unidadId = self::UNIDAD_DELEGACIONES_ID;
            }
        }

        $usuario = $request->user();

        if ($this->tieneAccesoGlobal($usuario)) {
            return $unidadId > 0 ? $unidadId : 0;
        }

        $unidadUsuario = (int) ($usuario->unidad_id ?? 0);
        if ($unidadUsuario > 0) {
            return $unidadUsuario;
        }

        return $unidadId > 0 ? $unidadId : 0;
    }

    private function requestedDelegacionIds(Request $request): array
    {
        return collect([
            ...$this->normalizeIds($request->query('delegacion_id', [])),
            ...$this->normalizeIds($request->query('delegaciones', [])),
        ])
            ->unique()
            ->values()
            ->all();
    }

    private function effectiveDelegacionIds(Request $request): array
    {
        $requested = $this->requestedDelegacionIds($request);
        $usuario = $request->user();

        if ($this->tieneAccesoGlobal($usuario)) {
            return $requested;
        }

        if ((int) ($usuario->unidad_id ?? 0) !== self::UNIDAD_DELEGACIONES_ID) {
            return [];
        }

        $visible = $this->delegacionIdsVisibles($usuario);
        if (empty($visible)) {
            return $requested;
        }

        if (empty($requested)) {
            return $visible;
        }

        return array_values(array_intersect($requested, $visible));
    }

    private function tieneAccesoGlobal($usuario): bool
    {
        return $usuario
            && ($usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === self::UNIDAD_SEGURIDAD_VIAL_ID);
    }

    private function delegacionIdsVisibles($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);
        if ($this->esRolAdministrativoDelegaciones($usuario)) {
            return Delegacion::query()
                ->where('activa', 1)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all();
        }

        if ($delegacionId <= 0) {
            return [];
        }

        if (!$usuario->hasRole('Subdirector') && !$usuario->hasRole('Delegado')) {
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

    private function esRolAdministrativoDelegaciones($usuario): bool
    {
        return $usuario
            && ((int) ($usuario->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID)
            && ($usuario->hasRole('Administrador')
                || $usuario->hasRole('Administrativo')
                || $usuario->hasRole('Subdirector'));
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

    private function includeGruasSinServicios(Request $request): bool
    {
        return $request->boolean('incluir_sin_servicios')
            || $request->boolean('include_empty')
            || $request->boolean('con_ceros');
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
