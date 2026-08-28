<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstadisticasActividadesController extends Controller
{
    private const UNIDAD_SEGURIDAD_VIAL_ID = 3;

    public function index(Request $request)
    {
        $unidadesFiltro = $this->unidadesDisponiblesParaFiltro($request->user());

        return view('estadisticas_actividades.index', compact('unidadesFiltro'));
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
            $totalPuestas = 0;
            $personasEnPuestas = 0;

            if ($this->hasTable('puestas_disposicion')) {
                $totalPuestas = (int)$this->basePuestasQuery($request)
                    ->distinct('puestas_disposicion.id')
                    ->count('puestas_disposicion.id');
            }

            if ($this->hasTable('puestas_disposicion') && $this->hasTable('puestas_disposicion_personas')) {
                $personasEnPuestas = (int)$this->basePuestasPersonasQuery($request)
                    ->count('personas_puesta.id');
            }

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
                    'puestas_disposicion' => $totalPuestas,
                    'personas_en_puestas' => $personasEnPuestas,
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

    public function resumenCategorias(Request $request)
    {
        return $this->cachedJson($request, 'resumenCategorias', function () use ($request) {
            $q = $this->baseActividadesQuery($request);
            $this->applySearchFilter($q, $request);

            $rows = $q
                ->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
                ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
                ->selectRaw("
                    actividad_categorias.id as categoria_id,
                    COALESCE(NULLIF(TRIM(actividad_categorias.nombre), ''), 'NO ESPECIFICADO') as categoria,
                    actividad_subcategorias.id as subcategoria_id,
                    COALESCE(NULLIF(TRIM(actividad_subcategorias.nombre), ''), 'NO ESPECIFICADO') as subcategoria,
                    COUNT(DISTINCT actividades.id) as total
                ")
                ->groupBy(
                    'actividad_categorias.id',
                    'actividad_categorias.nombre',
                    'actividad_subcategorias.id',
                    'actividad_subcategorias.nombre'
                )
                ->get();

            $categorias = $rows
                ->groupBy(function ($row) {
                    return $row->categoria_id !== null
                        ? 'categoria_' . $row->categoria_id
                        : 'sin_categoria';
                })
                ->map(function ($grupo) {
                    return [
                        'nombre' => (string)$grupo->first()->categoria,
                        'total' => (int)$grupo->sum('total'),
                        'subcategorias' => $grupo
                            ->map(function ($row) {
                                return [
                                    'nombre' => (string)$row->subcategoria,
                                    'total' => (int)$row->total,
                                ];
                            })
                            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
                            ->values(),
                    ];
                })
                ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return [
                'categorias' => $categorias,
                'total' => (int)$categorias->sum('total'),
            ];
        });
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

            $q = $this->basePuestasQuery($request)
                ->leftJoin('unidades as puesta_unidad', 'puesta_unidad.id', '=', 'puestas_disposicion.unidad_id')
                ->leftJoin('delegaciones as puesta_delegacion', 'puesta_delegacion.id', '=', 'puestas_disposicion.delegacion_id')
                ->leftJoin('destacamentos as puesta_destacamento', 'puesta_destacamento.id', '=', 'puestas_disposicion.destacamento_id')
                ->select([
                    'puestas_disposicion.id',
                    'puestas_disposicion.numero_puesta',
                    'puestas_disposicion.anio',
                    'puestas_disposicion.fecha_puesta',
                    'puestas_disposicion.hora_puesta',
                    'puestas_disposicion.carpeta_investigacion',
                    'puestas_disposicion.oficio',
                    'puestas_disposicion.tipo_puesta',
                    'puestas_disposicion.motivo',
                    'puesta_unidad.nombre as unidad_nombre',
                    'puesta_delegacion.nombre as delegacion_nombre',
                    'puesta_destacamento.nombre as destacamento_nombre',
                ]);

            if ($this->hasTable('puestas_disposicion_personas')) {
                $q->selectSub(function ($personas) use ($request) {
                    $personas->from('puestas_disposicion_personas as pdp')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('pdp.puesta_disposicion_id', 'puestas_disposicion.id');
                    $this->applyPuestasAgeFilter($personas, $request, 'pdp');
                }, 'personas_count');
            } else {
                $q->selectRaw('0 as personas_count');
            }

            $q->distinct()
                ->orderByDesc('puestas_disposicion.fecha_puesta')
                ->orderByDesc('puestas_disposicion.numero_puesta');

            return $q->paginate($per)->toArray();
        });
    }

    public function seriesPuestasPersonasEdad(Request $request)
    {
        return $this->cachedJson($request, 'seriesPuestasPersonasEdad', function () use ($request) {
            if (!$this->hasTable('puestas_disposicion') || !$this->hasTable('puestas_disposicion_personas')) {
                return ['total' => 0, 'series' => []];
            }

            $edad = $this->puestasEdadExpression('personas_puesta');
            $label = "CASE
                WHEN {$edad} IS NULL THEN 'SIN EDAD'
                WHEN {$edad} BETWEEN 0 AND 11 THEN '0-11'
                WHEN {$edad} BETWEEN 12 AND 17 THEN '12-17'
                WHEN {$edad} BETWEEN 18 AND 29 THEN '18-29'
                WHEN {$edad} BETWEEN 30 AND 44 THEN '30-44'
                WHEN {$edad} BETWEEN 45 AND 59 THEN '45-59'
                WHEN {$edad} >= 60 THEN '60+'
                ELSE 'SIN EDAD'
            END";

            $totales = $this->basePuestasPersonasQuery($request)
                ->selectRaw("{$label} as label, COUNT(personas_puesta.id) as total")
                ->groupByRaw($label)
                ->get()
                ->pluck('total', 'label');

            $series = collect(['0-11', '12-17', '18-29', '30-44', '45-59', '60+', 'SIN EDAD'])
                ->map(fn ($rango) => [
                    'label' => $rango,
                    'total' => (int)($totales[$rango] ?? 0),
                ]);

            return [
                'total' => (int)$series->sum('total'),
                'series' => $series,
            ];
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

    public function catalogoUnidades(Request $request)
    {
        return $this->cachedJson($request, 'catalogoUnidades', function () use ($request) {
            return $this->unidadesDisponiblesParaFiltro($request->user());
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

    public function exportFomentoCulturaVial(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        if (!$this->hasTable('fomento_cultura_vial_detalles')) {
            return back()->with('error', 'No existe la tabla fomento_cultura_vial_detalles.');
        }

        $anio = (int)$request->query('anio', now()->year);
        $mesParam = trim((string)$request->query('mes', ''));

        if ($anio < 2000 || $anio > 2100) {
            return back()->with('error', 'Año inválido.');
        }

        if ($mesParam !== '') {
            $mes = (int)$mesParam;

            if ($mes < 1 || $mes > 12) {
                return back()->with('error', 'Mes inválido.');
            }

            $desde = sprintf('%04d-%02d-01', $anio, $mes);
            $hasta = date('Y-m-t', strtotime($desde));
            $periodoTexto = sprintf('%04d-%02d', $anio, $mes);
            $filename = 'fomento_cultura_vial_' . sprintf('%04d_%02d', $anio, $mes) . '.xlsx';
        } else {
            $desde = sprintf('%04d-01-01', $anio);
            $hasta = sprintf('%04d-12-31', $anio);
            $periodoTexto = (string)$anio;
            $filename = 'fomento_cultura_vial_' . $anio . '.xlsx';
        }

        $request->merge([
            'desde' => $desde,
            'hasta' => $hasta,
            'cache_ttl' => 0,
        ]);

        $q = $this->baseActividadesQuery($request);
        $this->applySearchFilter($q, $request);

        $q->join('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id')
            ->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->leftJoin('actividad_subcategorias', 'actividad_subcategorias.id', '=', 'actividades.actividad_subcategoria_id')
            ->leftJoin('unidades', 'unidades.id', '=', 'actividades.unidad_org_id')
            ->leftJoin('delegaciones', 'delegaciones.id', '=', 'actividades.delegacion_id')
            ->leftJoin('destacamentos', 'destacamentos.id', '=', 'actividades.destacamento_id');

        $sums = "
            COUNT(DISTINCT actividades.id) as actividades,
            SUM(COALESCE(fomento.ninas, 0)) as ninas,
            SUM(COALESCE(fomento.ninos, 0)) as ninos,
            SUM(COALESCE(fomento.adolescentes_mujeres, 0)) as adolescentes_mujeres,
            SUM(COALESCE(fomento.adolescentes_hombres, 0)) as adolescentes_hombres,
            SUM(COALESCE(fomento.docentes_hombres, 0)) as docentes_hombres,
            SUM(COALESCE(fomento.docentes_mujeres, 0)) as docentes_mujeres,
            SUM(COALESCE(fomento.hombres, 0)) as hombres,
            SUM(COALESCE(fomento.mujeres, 0)) as mujeres,
            SUM(COALESCE(fomento.total_poblacion_atendida, 0)) as total_poblacion_atendida
        ";

        $totales = (clone $q)->selectRaw($sums)->first();

        $porNivel = (clone $q)
            ->selectRaw("
                COALESCE(NULLIF(TRIM(fomento.nivel_educativo), ''), 'NO ESPECIFICADO') as label,
                COUNT(DISTINCT actividades.id) as actividades,
                SUM(COALESCE(fomento.total_poblacion_atendida, 0)) as total_poblacion_atendida
            ")
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $porSector = (clone $q)
            ->selectRaw("
                COALESCE(NULLIF(TRIM(fomento.sector), ''), 'NO ESPECIFICADO') as label,
                COUNT(DISTINCT actividades.id) as actividades,
                SUM(COALESCE(fomento.total_poblacion_atendida, 0)) as total_poblacion_atendida
            ")
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $porPrograma = (clone $q)
            ->selectRaw("
                COALESCE(NULLIF(TRIM(fomento.programa_nombre), ''), 'NO ESPECIFICADO') as label,
                COUNT(DISTINCT actividades.id) as actividades,
                SUM(COALESCE(fomento.total_poblacion_atendida, 0)) as total_poblacion_atendida
            ")
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $detalles = (clone $q)
            ->select([
                'actividades.id',
                'actividades.fecha',
                'actividades.hora',
                'actividad_categorias.nombre as categoria',
                'actividad_subcategorias.nombre as subcategoria',
                'unidades.nombre as unidad',
                'delegaciones.nombre as delegacion',
                'destacamentos.nombre as destacamento',
                'actividades.municipio',
                'actividades.lugar',
                'actividades.nombre as capturo',
                'fomento.programa_nombre',
                'fomento.nombre_institucion',
                'fomento.domicilio',
                'fomento.nivel_educativo',
                'fomento.sector',
                'fomento.ninas',
                'fomento.ninos',
                'fomento.adolescentes_mujeres',
                'fomento.adolescentes_hombres',
                'fomento.docentes_hombres',
                'fomento.docentes_mujeres',
                'fomento.hombres',
                'fomento.mujeres',
                'fomento.total_poblacion_atendida',
            ])
            ->orderBy('actividades.fecha')
            ->orderBy('actividades.hora')
            ->orderBy('actividades.id')
            ->get();

        return new StreamedResponse(function () use ($totales, $porNivel, $porSector, $porPrograma, $detalles, $periodoTexto, $desde, $hasta) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen');

            $sheet->setCellValue('A1', 'FOMENTO A LA CULTURA VIAL');
            $sheet->setCellValue('A2', 'Periodo');
            $sheet->setCellValue('B2', $periodoTexto);
            $sheet->setCellValue('A3', 'Desde');
            $sheet->setCellValue('B3', $desde);
            $sheet->setCellValue('C3', 'Hasta');
            $sheet->setCellValue('D3', $hasta);

            $metricas = [
                ['Actividades', (int)($totales->actividades ?? 0)],
                ['Niñas', (int)($totales->ninas ?? 0)],
                ['Niños', (int)($totales->ninos ?? 0)],
                ['Adolescentes mujeres', (int)($totales->adolescentes_mujeres ?? 0)],
                ['Adolescentes hombres', (int)($totales->adolescentes_hombres ?? 0)],
                ['Docentes hombres', (int)($totales->docentes_hombres ?? 0)],
                ['Docentes mujeres', (int)($totales->docentes_mujeres ?? 0)],
                ['Hombres', (int)($totales->hombres ?? 0)],
                ['Mujeres', (int)($totales->mujeres ?? 0)],
                ['Total población atendida', (int)($totales->total_poblacion_atendida ?? 0)],
            ];

            $sheet->fromArray(['Concepto', 'Total'], null, 'A5');
            $row = 6;

            foreach ($metricas as $metrica) {
                $sheet->fromArray($metrica, null, 'A' . $row);
                $row++;
            }

            $row += 2;
            $sheet->fromArray(['Nivel educativo', 'Actividades', 'Total población atendida'], null, 'A' . $row);
            $row++;

            foreach ($porNivel as $item) {
                $sheet->fromArray([
                    $item->label,
                    (int)$item->actividades,
                    (int)$item->total_poblacion_atendida,
                ], null, 'A' . $row);
                $row++;
            }

            $row += 2;
            $sheet->fromArray(['Sector', 'Actividades', 'Total población atendida'], null, 'A' . $row);
            $row++;

            foreach ($porSector as $item) {
                $sheet->fromArray([
                    $item->label,
                    (int)$item->actividades,
                    (int)$item->total_poblacion_atendida,
                ], null, 'A' . $row);
                $row++;
            }

            $row += 2;
            $sheet->fromArray(['Programa / taller / campaña', 'Actividades', 'Total población atendida'], null, 'A' . $row);
            $row++;

            foreach ($porPrograma as $item) {
                $sheet->fromArray([
                    $item->label,
                    (int)$item->actividades,
                    (int)$item->total_poblacion_atendida,
                ], null, 'A' . $row);
                $row++;
            }

            $detalleSheet = $spreadsheet->createSheet();
            $detalleSheet->setTitle('Detalle');

            $headers = [
                'id',
                'fecha',
                'hora',
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'municipio',
                'lugar',
                'capturo',
                'programa',
                'nombre_escuela_empresa',
                'domicilio',
                'nivel_educativo',
                'sector',
                'ninas',
                'ninos',
                'adolescentes_mujeres',
                'adolescentes_hombres',
                'docentes_hombres',
                'docentes_mujeres',
                'hombres',
                'mujeres',
                'total_poblacion_atendida',
            ];

            $detalleSheet->fromArray($headers, null, 'A1');
            $detalleRow = 2;

            foreach ($detalles as $detalle) {
                $detalleSheet->fromArray([
                    $detalle->id,
                    $detalle->fecha,
                    $detalle->hora ? substr((string)$detalle->hora, 0, 5) : '',
                    $detalle->categoria,
                    $detalle->subcategoria,
                    $detalle->unidad,
                    $detalle->delegacion,
                    $detalle->destacamento,
                    $detalle->municipio,
                    $detalle->lugar,
                    $detalle->capturo,
                    $detalle->programa_nombre,
                    $detalle->nombre_institucion,
                    $detalle->domicilio,
                    $detalle->nivel_educativo,
                    $detalle->sector,
                    (int)$detalle->ninas,
                    (int)$detalle->ninos,
                    (int)$detalle->adolescentes_mujeres,
                    (int)$detalle->adolescentes_hombres,
                    (int)$detalle->docentes_hombres,
                    (int)$detalle->docentes_mujeres,
                    (int)$detalle->hombres,
                    (int)$detalle->mujeres,
                    (int)$detalle->total_poblacion_atendida,
                ], null, 'A' . $detalleRow);
                $detalleRow++;
            }

            foreach ([$sheet, $detalleSheet] as $worksheet) {
                $highestColumn = $worksheet->getHighestColumn();
                $highestRow = $worksheet->getHighestRow();

                $worksheet->freezePane('A2');
                $worksheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
                $worksheet->getStyle('A1:' . $highestColumn . '1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('D9EAF7');
                $worksheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportPuestasDisposicion(Request $request)
    {
        if (!$this->hasTable('puestas_disposicion')) {
            return back()->with('error', 'No existe la tabla puestas_disposicion.');
        }

        $q = $this->basePuestasQuery($request);

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

    private function basePuestasQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion');
        $this->applyPuestasFilters($q, $request, true);
        return $q;
    }

    private function basePuestasPersonasQuery(Request $request)
    {
        $q = DB::table('puestas_disposicion_personas as personas_puesta')
            ->join('puestas_disposicion', 'puestas_disposicion.id', '=', 'personas_puesta.puesta_disposicion_id');

        $this->applyPuestasFilters($q, $request, false);
        $this->applyPuestasAgeFilter($q, $request, 'personas_puesta');

        return $q;
    }

    private function applyPuestasFilters($q, Request $request, bool $filterByRelatedAge): void
    {
        $this->applyPuestasScopeByUser($q);

        $desde = trim((string)$request->query('desde', ''));
        $hasta = trim((string)$request->query('hasta', ''));
        $horaDesde = $this->normalizeHour($request->query('hora_desde', ''));
        $horaHasta = $this->normalizeHour($request->query('hora_hasta', ''));

        if ($desde !== '') {
            $q->whereDate('puestas_disposicion.fecha_puesta', '>=', $desde);
        }

        if ($hasta !== '') {
            $q->whereDate('puestas_disposicion.fecha_puesta', '<=', $hasta);
        }

        if ($desde !== '' && $hasta !== '' && $desde === $hasta) {
            if ($horaDesde !== null) {
                $q->whereTime('puestas_disposicion.hora_puesta', '>=', $horaDesde);
            }
            if ($horaHasta !== null) {
                $q->whereTime('puestas_disposicion.hora_puesta', '<=', $horaHasta);
            }
        }

        $unidadId = (int)$request->query('unidad_org_id', $request->query('unidad_id', 0));
        if ($unidadId > 0) {
            $q->where('puestas_disposicion.unidad_id', $unidadId);
        }

        foreach ([
            'delegacion_id' => 'puestas_disposicion.delegacion_id',
            'destacamento_id' => 'puestas_disposicion.destacamento_id',
        ] as $param => $column) {
            $value = trim((string)$request->query($param, ''));
            if ($value !== '') {
                $q->where($column, $value);
            }
        }

        $activityFilters = array_filter([
            'actividad_categoria_id' => trim((string)$request->query('actividad_categoria_id', '')),
            'actividad_subcategoria_id' => trim((string)$request->query('actividad_subcategoria_id', '')),
            'municipio' => trim((string)$request->query('municipio', '')),
        ], fn ($value) => $value !== '');

        if (!empty($activityFilters) && $this->hasColumn('puestas_disposicion', 'actividad_id')) {
            $q->whereExists(function ($actividad) use ($activityFilters) {
                $actividad->selectRaw('1')
                    ->from('actividades as actividad_puesta')
                    ->whereColumn('actividad_puesta.id', 'puestas_disposicion.actividad_id');

                foreach ($activityFilters as $field => $value) {
                    $actividad->where('actividad_puesta.' . $field, $value);
                }
            });
        }

        $search = mb_substr(trim((string)$request->query('q', '')), 0, 150, 'UTF-8');
        if ($search !== '') {
            $like = '%' . $search . '%';
            $q->where(function ($searchQuery) use ($like) {
                $searchQuery->where('puestas_disposicion.carpeta_investigacion', 'like', $like)
                    ->orWhere('puestas_disposicion.oficio', 'like', $like)
                    ->orWhere('puestas_disposicion.nombre_policia', 'like', $like)
                    ->orWhere('puestas_disposicion.nombre_mp', 'like', $like)
                    ->orWhere('puestas_disposicion.motivo', 'like', $like)
                    ->orWhere('puestas_disposicion.tipo_puesta', 'like', $like)
                    ->orWhereRaw('CAST(puestas_disposicion.numero_puesta AS CHAR) LIKE ?', [$like]);

                if ($this->hasTable('puestas_disposicion_personas')) {
                    $searchQuery->orWhereExists(function ($personas) use ($like) {
                        $personas->selectRaw('1')
                            ->from('puestas_disposicion_personas as persona_busqueda')
                            ->whereColumn('persona_busqueda.puesta_disposicion_id', 'puestas_disposicion.id')
                            ->where(function ($campos) use ($like) {
                                $campos->where('persona_busqueda.nombre_completo', 'like', $like)
                                    ->orWhere('persona_busqueda.alias', 'like', $like)
                                    ->orWhere('persona_busqueda.curp', 'like', $like);
                            });
                    });
                }
            });
        }

        if ($filterByRelatedAge && $this->hasTable('puestas_disposicion_personas') && $this->hasPuestasAgeFilter($request)) {
            $q->whereExists(function ($personas) use ($request) {
                $personas->selectRaw('1')
                    ->from('puestas_disposicion_personas as persona_edad')
                    ->whereColumn('persona_edad.puesta_disposicion_id', 'puestas_disposicion.id');
                $this->applyPuestasAgeFilter($personas, $request, 'persona_edad');
            });
        }
    }

    private function applyPuestasScopeByUser($q): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Superadmin') || (int)($user->unidad_id ?? 0) === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return;
        }

        $unidadId = (int)($user->unidad_id ?? 0);
        $unidadId > 0
            ? $q->where('puestas_disposicion.unidad_id', $unidadId)
            : $q->whereRaw('1 = 0');
    }

    private function hasPuestasAgeFilter(Request $request): bool
    {
        return trim((string)$request->query('edad_min', '')) !== ''
            || trim((string)$request->query('edad_max', '')) !== '';
    }

    private function applyPuestasAgeFilter($q, Request $request, string $alias): void
    {
        $min = trim((string)$request->query('edad_min', ''));
        $max = trim((string)$request->query('edad_max', ''));
        $edad = $this->puestasEdadExpression($alias);

        if ($min !== '' && is_numeric($min)) {
            $q->whereRaw("{$edad} >= ?", [max(0, (int)$min)]);
        }

        if ($max !== '' && is_numeric($max)) {
            $q->whereRaw("{$edad} <= ?", [min(130, (int)$max)]);
        }
    }

    private function puestasEdadExpression(string $alias): string
    {
        return "COALESCE({$alias}.edad, TIMESTAMPDIFF(YEAR, {$alias}.fecha_nacimiento, puestas_disposicion.fecha_puesta))";
    }

    private function applyActividadesFilters($q, Request $request)
    {
        $this->applyScopeByUser($q);
        $this->applySeguridadVialExclusion($q);

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

        $unidadFiltro = trim((string)$request->query('unidad_org_id', $request->query('unidad_id', '')));

        if ($unidadFiltro !== '') {
            $unidadId = (int)$unidadFiltro;

            if ($unidadId === self::UNIDAD_SEGURIDAD_VIAL_ID) {
                $q->whereRaw('1 = 0');
            } elseif ($unidadId > 0) {
                $this->applyUnidadScope($q, $unidadId);
            }
        }

        $map = [
            'actividad_categoria_id' => 'actividades.actividad_categoria_id',
            'actividad_subcategoria_id' => 'actividades.actividad_subcategoria_id',
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

            $rowsQuery = $q->selectRaw("COALESCE(NULLIF(TRIM($table.$labelColumn), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT actividades.id) as total")
                ->groupBy('label')
                ->orderByDesc('total');

            if (!in_array($table, ['actividad_categorias', 'actividad_subcategorias'], true)) {
                $rowsQuery->limit(100);
            }

            $rows = $rowsQuery->get();

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

    private function applySeguridadVialExclusion($q): void
    {
        $q->where(function ($scope) {
            $scope->where(function ($known) {
                $known->whereNotNull('actividades.unidad_org_id')
                    ->where('actividades.unidad_org_id', '<>', self::UNIDAD_SEGURIDAD_VIAL_ID);
            })->orWhere(function ($legacy) {
                $legacy->whereNull('actividades.unidad_org_id')
                    ->whereNotExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'actividades.created_by')
                            ->where('users.unidad_id', self::UNIDAD_SEGURIDAD_VIAL_ID);
                    });
            });
        });
    }

    private function unidadesDisponiblesParaFiltro($user)
    {
        $q = DB::table('unidades')
            ->select('id', 'nombre', 'slug')
            ->where('id', '<>', self::UNIDAD_SEGURIDAD_VIAL_ID);

        if ($this->hasColumn('unidades', 'activa')) {
            $q->where('activa', 1);
        }

        if (
            !$user
            || (!$user->hasRole('Superadmin') && (int)($user->unidad_id ?? 0) !== self::UNIDAD_SEGURIDAD_VIAL_ID)
        ) {
            $q->where('id', (int)($user->unidad_id ?? 0));
        }

        return $q->orderBy('nombre')->get();
    }
}
