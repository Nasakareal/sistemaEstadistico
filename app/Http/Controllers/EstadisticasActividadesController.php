<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstadisticasActividadesController extends Controller
{
    public function index(Request $request)
    {
        return view('estadisticas_actividades.index');
    }

    public function kpis(Request $request)
    {
        return $this->cachedJson($request, 'kpis', function () use ($request) {
            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $totalActividades = (clone $q)->distinct('actividades.id')->count('actividades.id');
            $totalCantidad = (clone $q)->sum('actividades.cantidad');
            $personasAlcanzadas = (clone $q)->sum('actividades.personas_alcanzadas');
            $personasParticipantes = (clone $q)->sum('actividades.personas_participantes');
            $personasDetenidas = (clone $q)->sum('actividades.personas_detenidas');
            $kmRecorridos = (clone $q)->sum('actividades.km_recorridos');

            $porCategoria = (clone $q)
                ->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
                ->selectRaw("COALESCE(NULLIF(TRIM(actividad_categorias.nombre), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT actividades.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            $porSubcategoria = (clone $q)
                ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
                ->selectRaw("COALESCE(NULLIF(TRIM(actividad_subcategorias.nombre), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT actividades.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(20)
                ->get();

            return [
                'totales' => [
                    'actividades' => (int)$totalActividades,
                    'cantidad' => (int)$totalCantidad,
                    'personas_alcanzadas' => (int)$personasAlcanzadas,
                    'personas_participantes' => (int)$personasParticipantes,
                    'personas_detenidas' => (int)$personasDetenidas,
                    'km_recorridos' => (float)$kmRecorridos,
                ],
                'top' => [
                    'categorias' => $porCategoria,
                    'subcategorias' => $porSubcategoria,
                ],
            ];
        });
    }

    public function seriesActividades(Request $request)
    {
        return $this->cachedJson($request, 'seriesActividades', function () use ($request) {
            $group = $this->grouping($request);

            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(actividades.fecha, '%Y-%m-01') as x, COUNT(DISTINCT actividades.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(actividades.fecha) as x, COUNT(DISTINCT actividades.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'series' => $rows];
        });
    }

    public function seriesCategoria(Request $request)
    {
        return $this->distributionJoin($request, 'actividad_categorias', 'actividad_categoria_id', 'nombre', 'actividades.actividad_categoria_id');
    }

    public function seriesSubcategoria(Request $request)
    {
        return $this->distributionJoin($request, 'actividad_subcategorias', 'actividad_subcategoria_id', 'nombre', 'actividades.actividad_subcategoria_id');
    }

    public function seriesUnidad(Request $request)
    {
        return $this->distributionJoin($request, 'unidades', 'unidad_org_id', 'nombre', 'actividades.unidad_org_id');
    }

    public function seriesDelegacion(Request $request)
    {
        return $this->distributionJoin($request, 'delegaciones', 'delegacion_id', 'nombre', 'actividades.delegacion_id');
    }

    public function seriesDestacamento(Request $request)
    {
        return $this->distributionJoin($request, 'destacamentos', 'destacamento_id', 'nombre', 'actividades.destacamento_id');
    }

    public function seriesMunicipio(Request $request)
    {
        return $this->distributionActividades($request, 'municipio');
    }

    public function seriesCarretera(Request $request)
    {
        return $this->distributionActividades($request, 'carretera');
    }

    public function seriesTiempo(Request $request)
    {
        return $this->cachedJson($request, 'seriesTiempo', function () use ($request) {
            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $rows = $q->selectRaw("
                    CASE
                        WHEN actividades.hora IS NULL THEN 'NO ESPECIFICADO'
                        WHEN TIME(actividades.hora) BETWEEN '00:00:00' AND '05:59:59' THEN 'MADRUGADA'
                        WHEN TIME(actividades.hora) BETWEEN '06:00:00' AND '11:59:59' THEN 'MAÑANA'
                        WHEN TIME(actividades.hora) BETWEEN '12:00:00' AND '17:59:59' THEN 'TARDE'
                        ELSE 'NOCHE'
                    END as label,
                    COUNT(DISTINCT actividades.id) as total
                ")
                ->groupBy('label')
                ->orderByDesc('total')
                ->get();

            return ['field' => 'actividades.hora', 'series' => $rows];
        });
    }

    public function seriesRevision(Request $request)
    {
        return $this->distributionActividades($request, 'estado_revision');
    }

    public function seriesPersonasAlcanzadas(Request $request)
    {
        return $this->sumSeriesByDate($request, 'personas_alcanzadas');
    }

    public function seriesPersonasParticipantes(Request $request)
    {
        return $this->sumSeriesByDate($request, 'personas_participantes');
    }

    public function seriesPersonasDetenidas(Request $request)
    {
        return $this->sumSeriesByDate($request, 'personas_detenidas');
    }

    public function seriesKmRecorridos(Request $request)
    {
        return $this->sumSeriesByDate($request, 'km_recorridos');
    }

    public function actividades(Request $request)
    {
        return $this->cachedJson($request, 'actividades', function () use ($request) {
            $per = (int)$request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $q->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
                ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
                ->leftJoin('unidades', 'unidades.id', '=', 'actividades.unidad_org_id')
                ->leftJoin('delegaciones', 'delegaciones.id', '=', 'actividades.delegacion_id')
                ->leftJoin('destacamentos', 'destacamentos.id', '=', 'actividades.destacamento_id')
                ->select([
                    'actividades.*',
                    'actividad_categorias.nombre as categoria_nombre',
                    'actividad_subcategorias.nombre as subcategoria_nombre',
                    'delegaciones.nombre as delegacion_nombre',
                    'destacamentos.nombre as destacamento_nombre',
                    'unidades.nombre as unidad_nombre',
                ])
                ->distinct()
                ->orderByDesc('actividades.fecha')
                ->orderByDesc('actividades.hora')
                ->orderByDesc('actividades.id');

            return $q->paginate($per)->toArray();
        });
    }

    public function puestasDisposicion(Request $request)
    {
        return $this->cachedJson($request, 'puestasDisposicion', function () use ($request) {
            if (!$this->hasTable('puestas_disposicion')) {
                return ['data' => [], 'total' => 0];
            }

            $per = (int)$request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = DB::table('puestas_disposicion');

            if ($this->hasColumn('puestas_disposicion', 'actividad_id')) {
                $q->join('actividades', 'actividades.id', '=', 'puestas_disposicion.actividad_id');
                $this->applyActividadesFilters($q, $request);
                $this->applySearchFilter($q, $request);
            }

            $q->select('puestas_disposicion.*')
                ->orderByDesc('puestas_disposicion.id');

            return $q->paginate($per)->toArray();
        });
    }

    public function catalogoCategorias(Request $request)
    {
        return $this->cachedJson($request, 'catalogoCategorias', function () {
            return DB::table('actividad_categorias')
                ->where('activo', 1)
                ->select('id', 'nombre', 'slug')
                ->orderBy('nombre')
                ->get();
        });
    }

    public function catalogoSubcategorias(Request $request)
    {
        return $this->cachedJson($request, 'catalogoSubcategorias', function () use ($request) {
            $q = DB::table('actividad_subcategorias')
                ->where('activo', 1)
                ->select('id', 'actividad_categoria_id', 'unidad_id', 'nombre', 'slug');

            $categoriaId = trim((string)$request->query('actividad_categoria_id', ''));
            $unidadId = trim((string)$request->query('unidad_id', $request->query('unidad_org_id', '')));

            if ($categoriaId !== '') {
                $q->where('actividad_categoria_id', $categoriaId);
            }

            if ($unidadId !== '') {
                $q->where(function ($w) use ($unidadId) {
                    $w->whereNull('unidad_id')
                        ->orWhere('unidad_id', $unidadId);
                });
            }

            return $q->orderBy('nombre')->get();
        });
    }

    public function catalogoDelegaciones(Request $request)
    {
        return $this->cachedJson($request, 'catalogoDelegaciones', function () {
            return DB::table('delegaciones')
                ->where('activa', 1)
                ->select('id', 'clave', 'nombre', 'municipio')
                ->orderBy('clave')
                ->orderBy('nombre')
                ->get();
        });
    }

    public function catalogoDestacamentos(Request $request)
    {
        return $this->cachedJson($request, 'catalogoDestacamentos', function () use ($request) {
            if (!$this->hasTable('destacamentos')) {
                return [];
            }

            $columns = ['id', 'nombre'];

            foreach (['clave', 'unidad_id', 'delegacion_id', 'municipio'] as $column) {
                if ($this->hasColumn('destacamentos', $column)) {
                    $columns[] = $column;
                }
            }

            $q = DB::table('destacamentos')
                ->select($columns);

            if ($this->hasColumn('destacamentos', 'activo')) {
                $q->where('activo', 1);
            }

            $delegacionId = trim((string)$request->query('delegacion_id', ''));

            if ($delegacionId !== '' && $this->hasColumn('destacamentos', 'delegacion_id')) {
                $q->where('delegacion_id', $delegacionId);
            }

            $unidadId = trim((string)$request->query('unidad_id', $request->query('unidad_org_id', '')));

            if ($unidadId !== '' && $this->hasColumn('destacamentos', 'unidad_id')) {
                $q->where('unidad_id', $unidadId);
            }

            if ($this->hasColumn('destacamentos', 'clave')) {
                $q->orderBy('clave');
            }

            return $q->orderBy('nombre')->get();
        });
    }

    public function exportActividades(Request $request)
    {
        $q = $this->baseActividadesQuery($request);
        $this->applySearchFilter($q, $request);

        $q->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->leftJoin('delegaciones', 'delegaciones.id', '=', 'actividades.delegacion_id')
            ->leftJoin('destacamentos', 'destacamentos.id', '=', 'actividades.destacamento_id')
            ->select([
                'actividades.id',
                'actividades.fecha',
                'actividades.hora',
                'actividad_categorias.nombre as categoria',
                'actividad_subcategorias.nombre as subcategoria',
                'actividades.nombre',
                'actividades.cantidad',
                'actividades.estado_revision',
                'delegaciones.nombre as delegacion',
                'destacamentos.nombre as destacamento',
                'actividades.lugar',
                'actividades.municipio',
                'actividades.carretera',
                'actividades.tramo',
                'actividades.kilometro',
                'actividades.lat',
                'actividades.lng',
                'actividades.km_recorridos',
                'actividades.motivo',
                'actividades.narrativa',
                'actividades.acciones_realizadas',
                'actividades.observaciones',
                'actividades.personas_alcanzadas',
                'actividades.personas_participantes',
                'actividades.personas_detenidas',
                'actividades.elementos_participantes_texto',
                'actividades.patrullas_participantes_texto',
            ])
            ->distinct()
            ->orderByDesc('actividades.fecha')
            ->orderByDesc('actividades.hora')
            ->orderByDesc('actividades.id');

        $filename = 'actividades_export_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'id',
                'fecha',
                'hora',
                'categoria',
                'subcategoria',
                'nombre',
                'cantidad',
                'estado_revision',
                'delegacion',
                'destacamento',
                'lugar',
                'municipio',
                'carretera',
                'tramo',
                'kilometro',
                'lat',
                'lng',
                'km_recorridos',
                'motivo',
                'narrativa',
                'acciones_realizadas',
                'observaciones',
                'personas_alcanzadas',
                'personas_participantes',
                'personas_detenidas',
                'elementos_participantes_texto',
                'patrullas_participantes_texto',
            ]);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->fecha,
                        $r->hora,
                        $r->categoria,
                        $r->subcategoria,
                        $r->nombre,
                        $r->cantidad,
                        $r->estado_revision,
                        $r->delegacion,
                        $r->destacamento,
                        $r->lugar,
                        $r->municipio,
                        $r->carretera,
                        $r->tramo,
                        $r->kilometro,
                        $r->lat,
                        $r->lng,
                        $r->km_recorridos,
                        $r->motivo,
                        $r->narrativa,
                        $r->acciones_realizadas,
                        $r->observaciones,
                        $r->personas_alcanzadas,
                        $r->personas_participantes,
                        $r->personas_detenidas,
                        $r->elementos_participantes_texto,
                        $r->patrullas_participantes_texto,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportMensual(Request $request)
    {
        $anio = (int)$request->query('anio', now()->year);
        $mes = (int)$request->query('mes', now()->month);

        if ($anio < 2000 || $anio > 2100) {
            return back()->with('error', 'Año inválido.');
        }

        if ($mes < 1 || $mes > 12) {
            return back()->with('error', 'Mes inválido.');
        }

        $request->merge([
            'desde' => sprintf('%04d-%02d-01', $anio, $mes),
            'hasta' => date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $anio, $mes))),
            'cache_ttl' => 0,
        ]);

        return $this->exportActividades($request);
    }

    public function exportPuestasDisposicion(Request $request)
    {
        if (!$this->hasTable('puestas_disposicion')) {
            return back()->with('error', 'No existe la tabla puestas_disposicion.');
        }

        $q = DB::table('puestas_disposicion');

        if ($this->hasColumn('puestas_disposicion', 'actividad_id')) {
            $q->join('actividades', 'actividades.id', '=', 'puestas_disposicion.actividad_id');
            $this->applyActividadesFilters($q, $request);
            $this->applySearchFilter($q, $request);
        }

        $q->select('puestas_disposicion.*')
            ->orderByDesc('puestas_disposicion.id');

        $filename = 'puestas_disposicion_export_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            $first = true;

            $q->chunk(1000, function ($rows) use ($out, &$first) {
                foreach ($rows as $r) {
                    $data = (array)$r;

                    if ($first) {
                        fputcsv($out, array_keys($data));
                        $first = false;
                    }

                    fputcsv($out, array_values($data));
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function baseActividadesQuery(Request $request)
    {
        $q = DB::table('actividades');
        $this->applyActividadesFilters($q, $request);
        return $q;
    }

    private function applyActividadesFilters($q, Request $request)
    {
        $this->applyScopeByUser($q);

        $desde = trim((string)$request->query('desde', ''));
        $hasta = trim((string)$request->query('hasta', ''));

        $horaDesde = $this->normalizeHour($request->query('hora_desde', ''));
        $horaHasta = $this->normalizeHour($request->query('hora_hasta', ''));

        if ($desde !== '' && $hasta !== '') {
            if ($desde === $hasta) {
                $q->whereDate('actividades.fecha', '=', $desde);

                if ($horaDesde !== null) {
                    $q->whereTime('actividades.hora', '>=', $horaDesde);
                }

                if ($horaHasta !== null) {
                    $q->whereTime('actividades.hora', '<=', $horaHasta);
                }
            } else {
                $q->where(function ($w) use ($desde, $hasta, $horaDesde, $horaHasta) {
                    $w->where(function ($mid) use ($desde, $hasta) {
                        $mid->whereDate('actividades.fecha', '>', $desde)
                            ->whereDate('actividades.fecha', '<', $hasta);
                    })
                    ->orWhere(function ($start) use ($desde, $horaDesde) {
                        $start->whereDate('actividades.fecha', '=', $desde);

                        if ($horaDesde !== null) {
                            $start->whereTime('actividades.hora', '>=', $horaDesde);
                        }
                    })
                    ->orWhere(function ($end) use ($hasta, $horaHasta) {
                        $end->whereDate('actividades.fecha', '=', $hasta);

                        if ($horaHasta !== null) {
                            $end->whereTime('actividades.hora', '<=', $horaHasta);
                        }
                    });
                });
            }
        } elseif ($desde !== '') {
            if ($horaDesde !== null) {
                $q->where(function ($w) use ($desde, $horaDesde) {
                    $w->whereDate('actividades.fecha', '>', $desde)
                        ->orWhere(function ($sameDay) use ($desde, $horaDesde) {
                            $sameDay->whereDate('actividades.fecha', '=', $desde)
                                ->whereTime('actividades.hora', '>=', $horaDesde);
                        });
                });
            } else {
                $q->whereDate('actividades.fecha', '>=', $desde);
            }
        } elseif ($hasta !== '') {
            if ($horaHasta !== null) {
                $q->where(function ($w) use ($hasta, $horaHasta) {
                    $w->whereDate('actividades.fecha', '<', $hasta)
                        ->orWhere(function ($sameDay) use ($hasta, $horaHasta) {
                            $sameDay->whereDate('actividades.fecha', '=', $hasta)
                                ->whereTime('actividades.hora', '<=', $horaHasta);
                        });
                });
            } else {
                $q->whereDate('actividades.fecha', '<=', $hasta);
            }
        }

        $map = [
            'actividad_categoria_id' => 'actividades.actividad_categoria_id',
            'actividad_subcategoria_id' => 'actividades.actividad_subcategoria_id',
            'unidad_org_id' => 'actividades.unidad_org_id',
            'delegacion_id' => 'actividades.delegacion_id',
            'destacamento_id' => 'actividades.destacamento_id',
            'estado_revision' => 'actividades.estado_revision',
            'municipio' => 'actividades.municipio',
            'carretera' => 'actividades.carretera',
            'tramo' => 'actividades.tramo',
            'sync_status' => 'actividades.sync_status',
            'created_by' => 'actividades.created_by',
        ];

        foreach ($map as $param => $col) {
            $val = trim((string)$request->query($param, ''));
            if ($val !== '') {
                $q->where($col, $val);
            }
        }
    }

    private function applySearchFilter($q, Request $request)
    {
        $search = trim((string)$request->query('q', ''));

        if ($search === '') {
            return;
        }

        $q->where(function ($qq) use ($search) {
            $qq->where('actividades.nombre', 'like', "%$search%")
                ->orWhere('actividades.lugar', 'like', "%$search%")
                ->orWhere('actividades.municipio', 'like', "%$search%")
                ->orWhere('actividades.carretera', 'like', "%$search%")
                ->orWhere('actividades.tramo', 'like', "%$search%")
                ->orWhere('actividades.motivo', 'like', "%$search%")
                ->orWhere('actividades.narrativa', 'like', "%$search%")
                ->orWhere('actividades.acciones_realizadas', 'like', "%$search%")
                ->orWhere('actividades.observaciones', 'like', "%$search%")
                ->orWhere('actividades.elementos_participantes_texto', 'like', "%$search%")
                ->orWhere('actividades.patrullas_participantes_texto', 'like', "%$search%");
        });
    }

    private function distributionActividades(Request $request, string $field)
    {
        return $this->cachedJson($request, "dist_$field", function () use ($request, $field) {
            $allowed = [
                'municipio',
                'carretera',
                'tramo',
                'estado_revision',
                'sync_status',
                'fuente_ubicacion',
            ];

            if (!in_array($field, $allowed, true)) {
                return ['message' => 'Campo no permitido.'];
            }

            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(actividades.$field), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT actividades.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(100)
                ->get();

            return ['field' => "actividades.$field", 'series' => $rows];
        });
    }

    private function distributionJoin(Request $request, string $table, string $foreignKey, string $labelColumn, string $field)
    {
        return $this->cachedJson($request, "dist_$table", function () use ($request, $table, $foreignKey, $labelColumn, $field) {
            $allowedTables = [
                'actividad_categorias',
                'actividad_subcategorias',
                'unidades',
                'delegaciones',
                'destacamentos',
            ];

            if (!in_array($table, $allowedTables, true)) {
                return ['message' => 'Tabla no permitida.'];
            }

            if (!$this->hasTable($table)) {
                return ['field' => $field, 'series' => []];
            }

            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $q->leftJoin($table, "$table.id", '=', "actividades.$foreignKey");

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM($table.$labelColumn), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT actividades.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(100)
                ->get();

            return ['field' => $field, 'series' => $rows];
        });
    }

    private function sumSeriesByDate(Request $request, string $field)
    {
        return $this->cachedJson($request, "sum_$field", function () use ($request, $field) {
            $allowed = [
                'personas_alcanzadas',
                'personas_participantes',
                'personas_detenidas',
                'km_recorridos',
            ];

            if (!in_array($field, $allowed, true)) {
                return ['message' => 'Campo no permitido.'];
            }

            $group = $this->grouping($request);

            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(actividades.fecha, '%Y-%m-01') as x, SUM(COALESCE(actividades.$field, 0)) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(actividades.fecha) as x, SUM(COALESCE(actividades.$field, 0)) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'field' => "actividades.$field", 'series' => $rows];
        });
    }

    private function grouping(Request $request)
    {
        $g = strtolower(trim((string)$request->query('group', 'day')));
        return in_array($g, ['day', 'month'], true) ? $g : 'day';
    }

    private function cachedJson(Request $request, string $key, \Closure $fn)
    {
        $ttl = (int)$request->query('cache_ttl', 60);
        $ttl = max(0, min(600, $ttl));

        if ($ttl === 0) {
            return response()->json($fn());
        }

        $user = auth()->user();

        $scope = $user
            ? implode(':', [
                (int)$user->id,
                (int)($user->unidad_id ?? 0),
                (int)($user->delegacion_id ?? 0),
                $user->roles->pluck('name')->sort()->implode('|'),
            ])
            : 'guest';

        $hash = sha1($scope . '|' . $request->fullUrl());
        $cacheKey = "estadisticas_actividades:$key:$hash";

        $data = Cache::remember($cacheKey, $ttl, function () use ($fn) {
            return $fn();
        });

        return response()->json($data);
    }

    private function hasTable(string $table)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column)
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeHour($value): ?string
    {
        $value = trim((string)$value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function applyScopeByUser($q)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Superadmin')) {
            return;
        }

        $unidadId = (int)($user->unidad_id ?? 0);

        if ($unidadId === 3) {
            return;
        }

        if ($unidadId > 0) {
            $this->applyUnidadScope($q, $unidadId);
            return;
        }

        $q->whereRaw('1 = 0');
    }

    private function applyUnidadScope($q, int $unidadId): void
    {
        $q->where(function ($scope) use ($unidadId) {
            $scope->where('actividades.unidad_org_id', $unidadId)
                ->orWhere(function ($legacy) use ($unidadId) {
                    $legacy->whereNull('actividades.unidad_org_id')
                        ->whereExists(function ($sub) use ($unidadId) {
                            $sub->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'actividades.created_by')
                                ->where('users.unidad_id', $unidadId);
                        });
                });
        });
    }
}
