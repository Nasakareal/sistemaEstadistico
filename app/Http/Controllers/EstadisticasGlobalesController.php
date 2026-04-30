<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Exports\EstadisticaMensualExporter;
use Illuminate\Support\Str;

class EstadisticasGlobalesController extends Controller
{
    private bool $vehiculosJoined = false;

    public function index(Request $request)
    {
        return view('estadisticas_globales.index');
    }

    public function kpis(Request $request)
    {
        return $this->cachedJson($request, 'kpis', function () use ($request) {

            $baseHechos = $this->baseHechosQuery($request);
            $this->applySearchFilter($baseHechos, $request);
            $this->applyVehiculoFiltersToHechos($baseHechos, $request);
            $this->applyLesionadosFilterToHechos($baseHechos, $request);
            $this->applyFallecidosFilterToHechos($baseHechos, $request);

            $hechosIdsSub = (clone $baseHechos)->select('hechos.id')->distinct();

            $totalHechos = (clone $hechosIdsSub)->count('hechos.id');

            $vehQ = DB::table('hecho_vehiculo')
                ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id')
                ->whereIn('hecho_vehiculo.hecho_id', $hechosIdsSub);

            $vehTipo   = trim((string)$request->query('veh_tipo', ''));
            $vehMarca  = trim((string)$request->query('veh_marca', ''));
            $vehModelo = trim((string)$request->query('veh_modelo', ''));
            $vehLinea  = trim((string)$request->query('veh_linea', ''));
            $vehColor  = trim((string)$request->query('veh_color', ''));
            $vehPlacas = trim((string)$request->query('veh_placas', ''));
            $vehSerie  = trim((string)$request->query('veh_serie', ''));

            if ($vehTipo !== '') {
                $carrocerias = $this->carroceriasFromTipoGeneral($vehTipo);

                if (!empty($carrocerias)) {
                    $vehQ->whereIn('vehiculos.tipo', $carrocerias);
                } else {
                    $vehQ->where('vehiculos.tipo', $vehTipo);
                }
            }

            if ($vehMarca !== '')  $vehQ->where('vehiculos.marca', $vehMarca);
            if ($vehModelo !== '') $vehQ->where('vehiculos.modelo', $vehModelo);
            if ($vehLinea !== '')  $vehQ->where('vehiculos.linea', $vehLinea);
            if ($vehColor !== '')  $vehQ->where('vehiculos.color', $vehColor);
            if ($vehPlacas !== '') $vehQ->where('vehiculos.placas', 'like', "%$vehPlacas%");
            if ($vehSerie !== '')  $vehQ->where('vehiculos.serie', 'like', "%$vehSerie%");

            $totalVehiculos = (clone $vehQ)->distinct('vehiculos.id')->count('vehiculos.id');

            $totalLesionados = 0;
            $totalFallecidos = 0;

            if ($this->hasTable('lesionados')) {

                $baseLes = DB::table('lesionados')
                    ->whereIn('lesionados.hecho_id', $hechosIdsSub);

                $totalFallecidos = (int) (clone $baseLes)
                    ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) = 'FALLECIDO'")
                    ->count('lesionados.id');

                $totalLesionados = (int) (clone $baseLes)
                    ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) <> 'FALLECIDO'")
                    ->count('lesionados.id');
            }

            $porTipoHecho = (clone $baseHechos)
                ->selectRaw("COALESCE(NULLIF(TRIM(hechos.tipo_hecho), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT hechos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(50)
                ->get();

            $porSituacion = (clone $baseHechos)
                ->selectRaw("COALESCE(NULLIF(TRIM(hechos.situacion), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT hechos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->get();

            return [
                'totales' => [
                    'hechos' => (int)$totalHechos,
                    'lesionados'  => (int)$totalLesionados,
                    'fallecidos'  => (int)$totalFallecidos,
                    'vehiculos' => (int)$totalVehiculos,
                ],
                'top' => [
                    'tipo_hecho' => $porTipoHecho,
                    'situacion' => $porSituacion,
                ],
            ];
        });
    }

    public function seriesHechos(Request $request)
    {
        return $this->cachedJson($request, 'seriesHechos', function () use ($request) {
            $group = $this->grouping($request);

            $q = $this->baseHechosQuery($request);
            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(hechos.fecha, '%Y-%m-01') as x, COUNT(DISTINCT hechos.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(hechos.fecha) as x, COUNT(DISTINCT hechos.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'series' => $rows];
        });
    }

    public function seriesLesionados(Request $request)
    {
        return $this->cachedJson($request, 'seriesLesionados', function () use ($request) {
            if (!$this->hasTable('lesionados')) {
                return ['group' => $this->grouping($request), 'series' => []];
            }

            $group = $this->grouping($request);

            $q = $this->baseLesionadosQuery($request);

            $q->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) <> 'FALLECIDO'");

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);

            $val = trim((string)$request->query('con_lesionados', ''));
            if ($val === '0') {
                return ['group' => $group, 'series' => []];
            }

            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(hechos.fecha, '%Y-%m-01') as x, COUNT(lesionados.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            } else {
                $rows = $q->selectRaw("DATE(hechos.fecha) as x, COUNT(lesionados.id) as y")
                    ->groupBy('x')
                    ->orderBy('x')
                    ->get();
            }

            return ['group' => $group, 'series' => $rows];
        });
    }

    public function seriesTipoHecho(Request $request) { return $this->distributionHechos($request, 'tipo_hecho'); }
    public function seriesSector(Request $request) { return $this->distributionHechos($request, 'sector'); }
    public function seriesTiempo(Request $request) { return $this->distributionHechos($request, 'tiempo'); }
    public function seriesClima(Request $request) { return $this->distributionHechos($request, 'clima'); }
    public function seriesCondiciones(Request $request) { return $this->distributionHechos($request, 'condiciones'); }
    public function seriesControlTransito(Request $request) { return $this->distributionHechos($request, 'control_transito'); }
    public function seriesMunicipio(Request $request) { return $this->distributionHechos($request, 'municipio'); }
    public function seriesDelegacion(Request $request) { return $this->distributionHechos($request, 'unidad'); }

    public function seriesVehiculosTipo(Request $request)
    {
        return $this->cachedJson($request, 'seriesVehiculosTipo', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $raw = $q->selectRaw("COALESCE(NULLIF(TRIM(vehiculos.tipo), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(200)
                ->get();

            $acc = [];
            foreach ($raw as $r) {
                $general = $this->tipoGeneralFromTipo((string)($r->label ?? ''));
                $acc[$general] = ($acc[$general] ?? 0) + (int)($r->total ?? 0);
            }

            arsort($acc);

            $rows = [];
            foreach ($acc as $label => $total) {
                $rows[] = ['label' => $label, 'total' => (int)$total];
            }

            return ['field' => 'vehiculos.tipo_general', 'series' => array_slice($rows, 0, 50)];
        });
    }

    public function seriesVehiculosMarca(Request $request)
    {
        return $this->cachedJson($request, 'seriesVehiculosMarca', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(vehiculos.marca), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(50)
                ->get();

            return ['field' => 'vehiculos.marca', 'series' => $rows];
        });
    }

    public function seriesVehiculosModelo(Request $request)
    {
        return $this->cachedJson($request, 'seriesVehiculosModelo', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(vehiculos.modelo), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(50)
                ->get();

            return ['field' => 'vehiculos.modelo', 'series' => $rows];
        });
    }

    public function hechos(Request $request)
    {
        return $this->cachedJson($request, 'hechos', function () use ($request) {
            $per = (int)$request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = $this->baseHechosQuery($request);
            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);

            $q->select('hechos.*')
              ->distinct()
              ->orderByDesc('hechos.fecha')
              ->orderByDesc('hechos.id');

            return $q->paginate($per)->toArray();
        });
    }

    public function exportHechos(Request $request)
    {
        $q = $this->baseHechosQuery($request);

        $this->applySearchFilter($q, $request);
        $this->applyVehiculoFiltersToHechos($q, $request);
        $this->applyLesionadosFilterToHechos($q, $request);
        $this->applyFallecidosFilterToHechos($q, $request);

        $q->select([
            'hechos.id',
            'hechos.folio_c5i',
            'hechos.fecha',
            'hechos.hora',
            'hechos.sector',
            'hechos.municipio',
            'hechos.tipo_hecho',
            'hechos.situacion',
            'hechos.perito',
            'hechos.unidad',
            'hechos.calle',
            'hechos.colonia',
            'hechos.entre_calles',
        ])
        ->distinct()
        ->orderByDesc('hechos.fecha')
        ->orderByDesc('hechos.id');

        $filename = 'hechos_export_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['id','folio_c5i','fecha','hora','sector','municipio','tipo_hecho','situacion','perito','unidad','calle','colonia','entre_calles']);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->folio_c5i,
                        $r->fecha,
                        $r->hora,
                        $r->sector,
                        $r->municipio,
                        $r->tipo_hecho,
                        $r->situacion,
                        $r->perito,
                        $r->unidad,
                        $r->calle,
                        $r->colonia,
                        $r->entre_calles,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportMensual(Request $request, EstadisticaMensualExporter $exporter)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        if (class_exists(\Barryvdh\Debugbar\Facade::class)) {
            \Barryvdh\Debugbar\Facade::disable();
        }

        $anio = (int)$request->query('anio', now()->year);
        $mes  = (int)$request->query('mes', now()->month);

        if ($anio < 2000 || $anio > 2100) return back()->with('error', 'Año inválido.');
        if ($mes < 1 || $mes > 12) return back()->with('error', 'Mes inválido.');

        return $exporter->download($request, $anio, $mes);
    }

    private function distributionHechos(Request $request, string $field)
    {
        return $this->cachedJson($request, "dist_$field", function () use ($request, $field) {

            $allowed = ['tipo_hecho','sector','tiempo','clima','condiciones','control_transito','situacion','municipio','unidad'];
            if (!in_array($field, $allowed, true)) {
                return ['message' => 'Campo no permitido.'];
            }

            $q = $this->baseHechosQuery($request);

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(hechos.$field), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT hechos.id) as total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(50)
                ->get();

            return ['field' => "hechos.$field", 'series' => $rows];
        });
    }

    private function baseHechosQuery(Request $request)
    {
        $this->vehiculosJoined = false;

        $q = DB::table('hechos');
        $this->applyHechosFilters($q, $request);
        return $q;
    }

    private function baseVehiculosQuery(Request $request)
    {
        $q = DB::table('hecho_vehiculo')
            ->join('hechos', 'hechos.id', '=', 'hecho_vehiculo.hecho_id')
            ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id');

        $this->applyHechosFilters($q, $request);

        $vehTipo   = trim((string)$request->query('veh_tipo', ''));
        $vehMarca  = trim((string)$request->query('veh_marca', ''));
        $vehModelo = trim((string)$request->query('veh_modelo', ''));
        $vehLinea  = trim((string)$request->query('veh_linea', ''));
        $vehColor  = trim((string)$request->query('veh_color', ''));
        $vehPlacas = trim((string)$request->query('veh_placas', ''));
        $vehSerie  = trim((string)$request->query('veh_serie', ''));

        if ($vehTipo !== '') {
            $carrocerias = $this->carroceriasFromTipoGeneral($vehTipo);

            if (!empty($carrocerias)) {
                $q->whereIn('vehiculos.tipo', $carrocerias);
            } else {
                $q->where('vehiculos.tipo', $vehTipo);
            }
        }
        if ($vehMarca !== '')  $q->where('vehiculos.marca', $vehMarca);
        if ($vehModelo !== '') $q->where('vehiculos.modelo', $vehModelo);
        if ($vehLinea !== '')  $q->where('vehiculos.linea', $vehLinea);
        if ($vehColor !== '')  $q->where('vehiculos.color', $vehColor);
        if ($vehPlacas !== '') $q->where('vehiculos.placas', 'like', "%$vehPlacas%");
        if ($vehSerie !== '')  $q->where('vehiculos.serie', 'like', "%$vehSerie%");

        $this->applyFallecidosFilterToHechos($q, $request);

        return $q;
    }

    private function baseLesionadosQuery(Request $request)
    {
        $q = DB::table('lesionados')
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id');

        $this->applyHechosFilters($q, $request);

        return $q;
    }

    private function applyHechosFilters($q, Request $request)
    {
        $this->applyScopeByUser($q);

        $desde = trim((string)$request->query('desde', ''));
        $hasta = trim((string)$request->query('hasta', ''));

        $horaDesde = $this->normalizeHour($request->query('hora_desde', ''));
        $horaHasta = $this->normalizeHour($request->query('hora_hasta', ''));

        if ($desde !== '' && $hasta !== '') {
            if ($desde === $hasta) {
                $q->whereDate('hechos.fecha', '=', $desde);

                if ($horaDesde !== null) {
                    $q->whereTime('hechos.hora', '>=', $horaDesde);
                }

                if ($horaHasta !== null) {
                    $q->whereTime('hechos.hora', '<=', $horaHasta);
                }
            } else {
                $q->where(function ($w) use ($desde, $hasta, $horaDesde, $horaHasta) {
                    $w->where(function ($mid) use ($desde, $hasta) {
                        $mid->whereDate('hechos.fecha', '>', $desde)
                            ->whereDate('hechos.fecha', '<', $hasta);
                    })
                    ->orWhere(function ($start) use ($desde, $horaDesde) {
                        $start->whereDate('hechos.fecha', '=', $desde);

                        if ($horaDesde !== null) {
                            $start->whereTime('hechos.hora', '>=', $horaDesde);
                        }
                    })
                    ->orWhere(function ($end) use ($hasta, $horaHasta) {
                        $end->whereDate('hechos.fecha', '=', $hasta);

                        if ($horaHasta !== null) {
                            $end->whereTime('hechos.hora', '<=', $horaHasta);
                        }
                    });
                });
            }
        } elseif ($desde !== '') {
            if ($horaDesde !== null) {
                $q->where(function ($w) use ($desde, $horaDesde) {
                    $w->whereDate('hechos.fecha', '>', $desde)
                        ->orWhere(function ($sameDay) use ($desde, $horaDesde) {
                            $sameDay->whereDate('hechos.fecha', '=', $desde)
                                ->whereTime('hechos.hora', '>=', $horaDesde);
                        });
                });
            } else {
                $q->whereDate('hechos.fecha', '>=', $desde);
            }
        } elseif ($hasta !== '') {
            if ($horaHasta !== null) {
                $q->where(function ($w) use ($hasta, $horaHasta) {
                    $w->whereDate('hechos.fecha', '<', $hasta)
                        ->orWhere(function ($sameDay) use ($hasta, $horaHasta) {
                            $sameDay->whereDate('hechos.fecha', '=', $hasta)
                                ->whereTime('hechos.hora', '<=', $horaHasta);
                        });
                });
            } else {
                $q->whereDate('hechos.fecha', '<=', $hasta);
            }
        }

        $map = [
            'tipo_hecho' => 'hechos.tipo_hecho',
            'sector' => 'hechos.sector',
            'municipio' => 'hechos.municipio',
            'tiempo' => 'hechos.tiempo',
            'clima' => 'hechos.clima',
            'condiciones' => 'hechos.condiciones',
            'control_transito' => 'hechos.control_transito',
            'situacion' => 'hechos.situacion',
            'perito' => 'hechos.perito',
            'unidad' => 'hechos.unidad',
            'superficie_via' => 'hechos.superficie_via',
        ];

        foreach ($map as $param => $col) {
            $val = trim((string)$request->query($param, ''));
            if ($val !== '') $q->where($col, $val);
        }
    }

    private function applySearchFilter($q, Request $request)
    {
        $search = trim((string)$request->query('q', ''));
        if ($search === '') return;

        $q->where(function ($qq) use ($search) {
            $qq->where('hechos.folio_c5i', 'like', "%$search%")
               ->orWhere('hechos.perito', 'like', "%$search%")
               ->orWhere('hechos.unidad', 'like', "%$search%")
               ->orWhere('hechos.calle', 'like', "%$search%")
               ->orWhere('hechos.colonia', 'like', "%$search%")
               ->orWhere('hechos.entre_calles', 'like', "%$search%")
               ->orWhere('hechos.tipo_hecho', 'like', "%$search%")
               ->orWhere('hechos.sector', 'like', "%$search%")
               ->orWhere('hechos.municipio', 'like', "%$search%");
        });
    }

    private function applyVehiculoFiltersToHechos($q, Request $request)
    {
        $vehTipo   = trim((string)$request->query('veh_tipo', ''));
        $vehMarca  = trim((string)$request->query('veh_marca', ''));
        $vehModelo = trim((string)$request->query('veh_modelo', ''));
        $vehPlacas = trim((string)$request->query('veh_placas', ''));
        $vehSerie  = trim((string)$request->query('veh_serie', ''));
        $vehLinea  = trim((string)$request->query('veh_linea', ''));
        $vehColor  = trim((string)$request->query('veh_color', ''));

        $need = ($vehTipo !== '' || $vehMarca !== '' || $vehModelo !== '' || $vehPlacas !== '' || $vehSerie !== '' || $vehLinea !== '' || $vehColor !== '');
        if (!$need) return;

        if (!$this->vehiculosJoined) {
            $q->join('hecho_vehiculo', 'hecho_vehiculo.hecho_id', '=', 'hechos.id')
              ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id');
            $this->vehiculosJoined = true;
        }

        if ($vehTipo !== '') {
            $carrocerias = $this->carroceriasFromTipoGeneral($vehTipo);

            if (!empty($carrocerias)) {
                $q->whereIn('vehiculos.tipo', $carrocerias);
            } else {
                $q->where('vehiculos.tipo', $vehTipo);
            }
        }
        if ($vehMarca !== '')  $q->where('vehiculos.marca', $vehMarca);
        if ($vehModelo !== '') $q->where('vehiculos.modelo', $vehModelo);
        if ($vehLinea !== '')  $q->where('vehiculos.linea', $vehLinea);
        if ($vehColor !== '')  $q->where('vehiculos.color', $vehColor);
        if ($vehPlacas !== '') $q->where('vehiculos.placas', 'like', "%$vehPlacas%");
        if ($vehSerie !== '')  $q->where('vehiculos.serie', 'like', "%$vehSerie%");
    }

    private function applyLesionadosFilterToHechos($q, Request $request)
    {
        $val = trim((string)$request->query('con_lesionados', ''));
        if ($val === '') return;
        if (!$this->hasTable('lesionados')) return;

        if ($val === '1') {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id');
            });
        } elseif ($val === '0') {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id');
            });
        }
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

        $hash = sha1($request->fullUrl());
        $cacheKey = "estadisticas_globales:$key:$hash";

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

    private function countLesionadosSafe(Request $request)
    {
        if (!$this->hasTable('lesionados')) return 0;

        try {
            $q = $this->baseLesionadosQuery($request);
            return (int)$q->count('lesionados.id');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function carroceriasMap(): array
    {
        return [
            'automovil' => [
                'Sedán',
                'Hatchback',
                'Coupé',
                'SUV',
                'Convertible',
            ],

            'camioneta' => [
                'Pick-up',
                'Panel',
                'Vagoneta',
                'Furgoneta',
                'Van',
            ],

            'camion' => [
                'Caja seca',
                'Caja cerrada',
                'Caja abierta',
                'Plataforma',
                'Volteo',
                'Refrigerado',
                'Cisterna',
                'Pipa',
                'Grúa',
                'Torton',
                'Rabón',
                'Tracto',
            ],

            'motocicleta' => [
                'Trabajo',
                'Cruiser',
                'Doble Propósito',
                'Scooter',
                'Enduro',
                'Naked',
                'Pista',
                'Chopper',
                'Cuatrimoto',
            ],

            'bicicleta' => [
                'Montaña',
                'Ruta',
                'BMX',
                'Urbana',
                'Plegable',
            ],

            'remolque' => [
                'Plataforma',
                'Caja cerrada',
                'Caja seca',
                'Cama baja',
                'Refrigerado',
                'Volteo',
                'Góndola',
                'Dolly',
                'Portacontenedor',
            ],

            'maquinaria' => [
                'Retroexcavadora',
                'Excavadora',
                'Cargador frontal',
                'Motoconformadora',
                'Bulldozer',
                'Rodillo compactador',
                'Grúa industrial',
                'Montacargas',
                'Tractor agrícola',
                'Pavimentadora',
                'Compactadora',
            ],

            'tren' => [
                'Locomotora',
                'Vagón',
                'Tren de carga',
                'Tren de pasajeros',
                'Tranvía',
                'Metro',
            ],

            'semoviente' => [
                'Caballo',
                'Burro',
                'Vaca',
                'Mula',
                'Otro animal de tiro',
            ],
        ];
    }

    private function tipoGeneralFromTipo(string $tipoCarroceria): string
    {
        $t = $this->norm($tipoCarroceria);
        if ($t === '') return 'NO ESPECIFICADO';

        foreach ($this->carroceriasMap() as $general => $lista) {
            foreach ($lista as $x) {
                if ($this->norm((string)$x) === $t) {
                    return $general;
                }
            }
        }

        return 'OTRO';
    }

    private function carroceriasFromTipoGeneral(string $vehTipo): array
    {
        $key = strtolower(trim($vehTipo));
        $map = $this->carroceriasMap();
        return $map[$key] ?? [];
    }

    private function norm(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        $s = Str::ascii($s);
        $s = mb_strtoupper($s, 'UTF-8');
        $s = preg_replace('/\s+/', ' ', $s);

        return $s;
    }

    private function applyFallecidosFilterToHechos($q, Request $request)
    {
        $val = trim((string)$request->query('con_fallecidos', ''));
        if ($val === '') return;
        if (!$this->hasTable('lesionados')) return;

        if ($val === '1') {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id')
                    ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) = 'FALLECIDO'");
            });
        } elseif ($val === '0') {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id')
                    ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) = 'FALLECIDO'");
            });
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

        if ($user->unidades()->where('unidades.id', 3)->exists()) {
            return;
        }

        if ($user->unidades()->where('unidades.id', 1)->exists()) {
            $q->where('hechos.unidad_org_id', 1);
            return;
        }

        if ($user->unidades()->where('unidades.id', 2)->exists()) {
            $q->where('hechos.unidad_org_id', 2);
            return;
        }

        abort(403);
    }
}
