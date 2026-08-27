<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstadisticasGlobalesController extends Controller
{
    private const UNIDAD_SINIESTROS_ID = 1;
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function kpis(Request $request)
    {
        return $this->cached($request, 'kpis', function () use ($request) {

            $baseHechos = $this->baseHechosQuery($request);

            $this->applySearchFilter($baseHechos, $request);
            $this->applyVehiculoFiltersToHechos($baseHechos, $request);
            $this->applyLesionadosFilterToHechos($baseHechos, $request);
            $this->applyFallecidosFilterToHechos($baseHechos, $request);
            $hechosIdsSub = (clone $baseHechos)
                ->select('hechos.id')
                ->distinct('hechos.id');

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

            if ($vehTipo !== '')   $this->applyVehiculoTipoFilter($vehQ, $vehTipo);
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
                $lesionadosQ = DB::table('lesionados')
                    ->whereIn('lesionados.hecho_id', $hechosIdsSub);
                $this->whereLesionadoNoFallecido($lesionadosQ);
                $totalLesionados = (int) $lesionadosQ->count('lesionados.id');

                $fallecidosQ = DB::table('lesionados')
                    ->whereIn('lesionados.hecho_id', $hechosIdsSub);
                $this->whereLesionadoFallecido($fallecidosQ);
                $totalFallecidos = (int) $fallecidosQ->count('lesionados.id');
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

            return response()->json([
                'totales' => [
                    'hechos' => (int)$totalHechos,
                    'lesionados' => (int)$totalLesionados,
                    'fallecidos' => (int)$totalFallecidos,
                    'vehiculos' => (int)$totalVehiculos,
                ],
                'top' => [
                    'tipo_hecho' => $porTipoHecho,
                    'situacion' => $porSituacion,
                ],
            ]);
        });
    }

    public function seriesHechos(Request $request)
    {
        return $this->cached($request, 'seriesHechos', function () use ($request) {
            $group = $this->grouping($request);
            $q = $this->baseHechosQuery($request);

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);
            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(hechos.fecha, '%Y-%m-01') as x, COUNT(DISTINCT hechos.id) as y")
                    ->groupBy('x')->orderBy('x')->get();
            } else {
                $rows = $q->selectRaw("DATE(hechos.fecha) as x, COUNT(DISTINCT hechos.id) as y")
                    ->groupBy('x')->orderBy('x')->get();
            }

            return response()->json(['group' => $group, 'series' => $rows]);
        });
    }

    public function seriesLesionados(Request $request)
    {
        return $this->cached($request, 'seriesLesionados', function () use ($request) {
            if (!$this->hasTable('lesionados')) {
                return response()->json(['group' => $this->grouping($request), 'series' => []]);
            }

            $group = $this->grouping($request);
            $q = $this->baseLesionadosQuery($request);

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);
            $this->whereLesionadoNoFallecido($q);
            if ($group === 'month') {
                $rows = $q->selectRaw("DATE_FORMAT(hechos.fecha, '%Y-%m-01') as x, COUNT(lesionados.id) as y")
                    ->groupBy('x')->orderBy('x')->get();
            } else {
                $rows = $q->selectRaw("DATE(hechos.fecha) as x, COUNT(lesionados.id) as y")
                    ->groupBy('x')->orderBy('x')->get();
            }

            return response()->json(['group' => $group, 'series' => $rows]);
        });
    }

    public function seriesTipoHecho(Request $request) { return $this->distributionHechos($request, 'tipo_hecho'); }
    public function seriesSector(Request $request) { return $this->distributionHechos($request, 'sector'); }
    public function seriesMunicipio(Request $request) { return $this->distributionHechos($request, 'municipio'); }
    public function seriesTiempo(Request $request) { return $this->distributionHechos($request, 'tiempo'); }
    public function seriesClima(Request $request) { return $this->distributionHechos($request, 'clima'); }
    public function seriesCondiciones(Request $request) { return $this->distributionHechos($request, 'condiciones'); }
    public function seriesControlTransito(Request $request) { return $this->distributionHechos($request, 'control_transito'); }

    public function seriesVehiculosTipo(Request $request)
    {
        return $this->cached($request, 'seriesVehiculosTipo', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $raw = $q->selectRaw("vehiculos.tipo_general as tipo_general, COALESCE(NULLIF(TRIM(vehiculos.tipo), ''), 'NO ESPECIFICADO') as tipo, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('vehiculos.tipo_general', 'tipo')
                ->get();

            $totals = [];
            foreach ($raw as $row) {
                $general = $this->tipoGeneralVehiculo(
                    (string) ($row->tipo_general ?? ''),
                    (string) ($row->tipo ?? '')
                );
                $totals[$general] = ($totals[$general] ?? 0) + (int) ($row->total ?? 0);
            }

            arsort($totals);
            $rows = collect($totals)
                ->map(fn ($total, $label) => ['label' => $label, 'total' => (int) $total])
                ->values();

            return response()->json(['field' => 'vehiculos.tipo_general', 'series' => $rows]);
        });
    }

    public function seriesVehiculosMarca(Request $request)
    {
        return $this->cached($request, 'seriesVehiculosMarca', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(vehiculos.marca), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('label')->orderByDesc('total')->limit(50)->get();

            return response()->json(['field' => 'vehiculos.marca', 'series' => $rows]);
        });
    }

    public function seriesVehiculosModelo(Request $request)
    {
        return $this->cached($request, 'seriesVehiculosModelo', function () use ($request) {
            $q = $this->baseVehiculosQuery($request);

            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(vehiculos.modelo), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT vehiculos.id) as total")
                ->groupBy('label')->orderByDesc('total')->limit(50)->get();

            return response()->json(['field' => 'vehiculos.modelo', 'series' => $rows]);
        });
    }

    public function hechos(Request $request)
    {
        return $this->cached($request, 'hechos', function () use ($request) {
            $per = (int)$request->query('per', 25);
            $per = max(1, min(200, $per));

            $q = $this->baseHechosQuery($request);

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);
            $q->select('hechos.*')
                ->distinct('hechos.id')
                ->orderByDesc('hechos.fecha')
                ->orderByDesc('hechos.id');

            return response()->json($q->paginate($per));
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
            'hechos.id', 'hechos.folio_c5i', 'hechos.fecha', 'hechos.hora', 'hechos.sector', 'hechos.municipio',
            'hechos.tipo_hecho', 'hechos.situacion', 'hechos.perito', 'hechos.unidad', 'hechos.calle', 'hechos.colonia', 'hechos.entre_calles',
        ])
            ->distinct('hechos.id')
            ->orderByDesc('hechos.fecha')
            ->orderByDesc('hechos.id');

        $filename = 'hechos_export_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($q) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['id', 'folio_c5i', 'fecha', 'hora', 'sector', 'municipio', 'tipo_hecho', 'situacion', 'perito', 'unidad', 'calle', 'colonia', 'entre_calles']);

            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id, $r->folio_c5i, $r->fecha, $r->hora, $r->sector, $r->municipio,
                        $r->tipo_hecho, $r->situacion, $r->perito, $r->unidad, $r->calle, $r->colonia, $r->entre_calles,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function distributionHechos(Request $request, string $field)
    {
        return $this->cached($request, "dist_$field", function () use ($request, $field) {

            $allowed = ['tipo_hecho', 'sector', 'municipio', 'tiempo', 'clima', 'condiciones', 'control_transito', 'situacion'];
            if (!in_array($field, $allowed, true)) {
                return response()->json(['message' => 'Campo no permitido.'], 422);
            }

            $q = $this->baseHechosQuery($request);

            $this->applySearchFilter($q, $request);
            $this->applyVehiculoFiltersToHechos($q, $request);
            $this->applyLesionadosFilterToHechos($q, $request);
            $this->applyFallecidosFilterToHechos($q, $request);
            $rows = $q->selectRaw("COALESCE(NULLIF(TRIM(hechos.$field), ''), 'NO ESPECIFICADO') as label, COUNT(DISTINCT hechos.id) as total")
                ->groupBy('label')->orderByDesc('total')->limit(50)->get();

            return response()->json(['field' => "hechos.$field", 'series' => $rows]);
        });
    }

    private function baseHechosQuery(Request $request)
    {
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

        $vehTipo = trim((string)$request->query('veh_tipo', ''));
        $vehMarca = trim((string)$request->query('veh_marca', ''));
        $vehModelo = trim((string)$request->query('veh_modelo', ''));
        $vehLinea = trim((string)$request->query('veh_linea', ''));
        $vehColor = trim((string)$request->query('veh_color', ''));
        $vehPlacas = trim((string)$request->query('veh_placas', ''));
        $vehSerie = trim((string)$request->query('veh_serie', ''));

        if ($vehTipo !== '') $this->applyVehiculoTipoFilter($q, $vehTipo);
        if ($vehMarca !== '') $q->where('vehiculos.marca', $vehMarca);
        if ($vehModelo !== '') $q->where('vehiculos.modelo', $vehModelo);
        if ($vehLinea !== '') $q->where('vehiculos.linea', $vehLinea);
        if ($vehColor !== '') $q->where('vehiculos.color', $vehColor);
        if ($vehPlacas !== '') $q->where('vehiculos.placas', 'like', "%$vehPlacas%");
        if ($vehSerie !== '') $q->where('vehiculos.serie', 'like', "%$vehSerie%");

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
        $this->applyOrigenHechosFilter($q, $request);

        $desde = trim((string)$request->query('desde', ''));
        $hasta = trim((string)$request->query('hasta', ''));

        if ($desde !== '' && $hasta !== '') {
            $q->whereBetween('hechos.fecha', [$desde, $hasta]);
        } elseif ($desde !== '') {
            $q->whereDate('hechos.fecha', '>=', $desde);
        } elseif ($hasta !== '') {
            $q->whereDate('hechos.fecha', '<=', $hasta);
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
            'unidad_org_id' => 'hechos.unidad_org_id',
            'delegacion_id' => 'hechos.delegacion_id',
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
        $vehTipo = trim((string)$request->query('veh_tipo', ''));
        $vehMarca = trim((string)$request->query('veh_marca', ''));
        $vehModelo = trim((string)$request->query('veh_modelo', ''));
        $vehPlacas = trim((string)$request->query('veh_placas', ''));
        $vehSerie = trim((string)$request->query('veh_serie', ''));
        $vehLinea = trim((string)$request->query('veh_linea', ''));
        $vehColor = trim((string)$request->query('veh_color', ''));

        $need = ($vehTipo !== '' || $vehMarca !== '' || $vehModelo !== '' || $vehPlacas !== '' || $vehSerie !== '' || $vehLinea !== '' || $vehColor !== '');
        if (!$need) return;

        $q->join('hecho_vehiculo', 'hecho_vehiculo.hecho_id', '=', 'hechos.id')
            ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id');

        if ($vehTipo !== '') $this->applyVehiculoTipoFilter($q, $vehTipo);
        if ($vehMarca !== '') $q->where('vehiculos.marca', $vehMarca);
        if ($vehModelo !== '') $q->where('vehiculos.modelo', $vehModelo);
        if ($vehLinea !== '') $q->where('vehiculos.linea', $vehLinea);
        if ($vehColor !== '') $q->where('vehiculos.color', $vehColor);
        if ($vehPlacas !== '') $q->where('vehiculos.placas', 'like', "%$vehPlacas%");
        if ($vehSerie !== '') $q->where('vehiculos.serie', 'like', "%$vehSerie%");
    }

    private function applyVehiculoTipoFilter($q, string $vehTipo): void
    {
        $general = $this->tipoGeneralKey($vehTipo);
        if ($general === null) {
            $q->where('vehiculos.tipo', $vehTipo);
            return;
        }

        $carrocerias = $this->carroceriasTipoGeneral($general);

        $q->where(function ($where) use ($general, $carrocerias) {
            $where->whereRaw(
                "LOWER(TRIM(COALESCE(vehiculos.tipo_general, ''))) = ?",
                [$general]
            );

            if (!empty($carrocerias)) {
                $where->orWhereIn('vehiculos.tipo', $carrocerias);
            }

            $legacyLikeMap = [
                'motocicleta' => ['%MOTO%', '%SCOOTER%', '%CUATRIMOTO%'],
                'camioneta' => ['%CAMIONETA%', '%PICK%UP%', '%VAGONETA%', '%FURGON%', '%VAN%'],
                'camion' => ['%CAMION%', '%AUTOBUS%', '%MICROBUS%', '%TRACTOCAMION%', '%TRACTO CAMION%', '%TORTON%', '%RABON%'],
                'automovil' => ['%AUTOMOVIL%', '%SEDAN%', '%COUPE%'],
                'bicicleta' => ['%BICICLETA%', '%BICI%'],
                'remolque' => ['%REMOLQUE%', '%DOLLY%'],
                'maquinaria' => ['%MAQUINARIA%', '%TRACTOR%', '%MONTACARGAS%'],
                'tren' => ['%TREN%', '%FERROCARRIL%', '%VAGON%'],
                'semoviente' => ['%SEMOVIENTE%', '%CABALLO%', '%MULA%', '%VACA%'],
            ];
            $legacyLike = $legacyLikeMap[$general] ?? [];

            foreach ($legacyLike as $pattern) {
                $where->orWhereRaw(
                    "UPPER(TRIM(COALESCE(vehiculos.tipo, ''))) LIKE ?",
                    [$pattern]
                );
            }
        });
    }

    private function tipoGeneralVehiculo(string $tipoGeneral, string $tipo): string
    {
        $stored = $this->tipoGeneralKey($tipoGeneral);
        if ($stored !== null) return $stored;

        $normalizedType = $this->normalizeVehicleText($tipo);
        if ($normalizedType === '') return 'no especificado';

        foreach (array_keys(config('vehiculos.catalogos.tipos_generales', [])) as $general) {
            foreach ($this->carroceriasTipoGeneral((string) $general) as $carroceria) {
                if ($this->normalizeVehicleText((string) $carroceria) === $normalizedType) {
                    return (string) $general;
                }
            }
        }

        $rules = [
            'motocicleta' => ['MOTO', 'SCOOTER', 'CUATRIMOTO'],
            'camioneta' => ['CAMIONETA', 'PICK', 'VAGONETA', 'FURGON', 'VAN'],
            'maquinaria' => ['MAQUINARIA', 'TRACTOR', 'MONTACARGAS'],
            'camion' => ['CAMION', 'AUTOBUS', 'MICROBUS', 'OMNIBUS', 'TRACTO', 'TORTON', 'RABON'],
            'remolque' => ['REMOLQUE', 'DOLLY'],
            'automovil' => ['AUTOMOVIL', 'SEDAN', 'COUPE'],
            'bicicleta' => ['BICICLETA', 'BICI'],
            'tren' => ['FERROCARRIL', 'TREN', 'VAGON'],
            'semoviente' => ['SEMOVIENTE', 'CABALLO', 'BURRO', 'MULA', 'VACA'],
        ];

        foreach ($rules as $general => $needles) {
            foreach ($needles as $needle) {
                if (strpos($normalizedType, $needle) !== false) return $general;
            }
        }

        return 'otro';
    }

    private function tipoGeneralKey(string $value): ?string
    {
        $key = mb_strtolower($this->normalizeVehicleText($value), 'UTF-8');
        $valid = array_keys(config('vehiculos.catalogos.tipos_generales', []));
        return in_array($key, $valid, true) ? $key : null;
    }

    private function carroceriasTipoGeneral(string $general): array
    {
        $configured = config("vehiculos.catalogos.carrocerias.$general", []);
        $legacyMap = [
            'automovil' => ['Automovil', 'Automóvil'],
            'camioneta' => ['Camioneta', 'Camioneta carga', 'Camioneta de pasajeros'],
            'camion' => ['Camion de carga', 'Camión de carga', 'Camion urbano pasajeros', 'Camión urbano pasajeros', 'Microbus', 'Microbús', 'Omnibus', 'Ómnibus', 'Autobus', 'Autobús'],
            'motocicleta' => ['Motocicleta', 'Motoneta'],
            'bicicleta' => ['Bicicleta'],
            'maquinaria' => ['Tractor'],
            'tren' => ['Ferrocarril'],
            'semoviente' => ['Semoviente'],
        ];
        $legacy = $legacyMap[$general] ?? [];

        return array_values(array_unique([...$configured, ...$legacy]));
    }

    private function normalizeVehicleText(string $value): string
    {
        $value = Str::ascii(trim($value));
        $value = mb_strtoupper($value, 'UTF-8');
        return preg_replace('/\s+/', ' ', $value) ?: '';
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
                $this->whereLesionadoNoFallecido($sub);
            });
        } elseif ($val === '0') {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id');
                $this->whereLesionadoNoFallecido($sub);
            });
        }
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
                    ->whereColumn('lesionados.hecho_id', 'hechos.id');
                $this->whereLesionadoFallecido($sub);
            });
        } elseif ($val === '0') {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('lesionados')
                    ->whereColumn('lesionados.hecho_id', 'hechos.id');
                $this->whereLesionadoFallecido($sub);
            });
        }
    }

    private function whereLesionadoNoFallecido($q): void
    {
        $q->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) <> 'FALLECIDO'");
    }

    private function whereLesionadoFallecido($q): void
    {
        $q->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) = 'FALLECIDO'");
    }

    private function grouping(Request $request)
    {
        $g = strtolower(trim((string)$request->query('group', 'day')));
        return in_array($g, ['day', 'month'], true) ? $g : 'day';
    }

    private function cached(Request $request, string $key, \Closure $fn)
    {
        $ttl = (int)$request->query('cache_ttl', 60);
        $ttl = max(0, min(600, $ttl));

        if ($ttl === 0) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Error interno.'], 500);
            }
        }

        $user = $request->user();
        $scope = $user
            ? implode(':', [
                (int)$user->id,
                (int)($user->unidad_id ?? 0),
                (int)($user->delegacion_id ?? 0),
                $user->roles->pluck('name')->sort()->implode('|'),
            ])
            : 'guest';

        $hash = sha1($scope . '|' . $request->fullUrl());
        $cacheKey = "estadisticas_globales_api:$key:$hash";

        try {
            return Cache::remember($cacheKey, $ttl, function () use ($fn) {
                return $fn();
            });
        } catch (\Throwable $e) {
            try {
                return $fn();
            } catch (\Throwable $e2) {
                return response()->json(['message' => 'Error interno.'], 500);
            }
        }
    }

    private function hasTable(string $table)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function applyScopeByUser($q): void
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
        $this->applyUnidadesScope($q, [$unidadId]);
    }

    private function applyOrigenHechosFilter($q, Request $request): void
    {
        $origen = strtolower(trim((string) $request->query('origen_hechos', '')));

        if ($origen === 'ambas' || $origen === 'ambos') {
            $this->applyUnidadesScope($q, [
                self::UNIDAD_SINIESTROS_ID,
                self::UNIDAD_DELEGACIONES_ID,
            ]);
            return;
        }

        if ($origen === 'siniestros') {
            $this->applyUnidadScope($q, self::UNIDAD_SINIESTROS_ID);
            return;
        }

        if ($origen === 'delegaciones') {
            $this->applyUnidadScope($q, self::UNIDAD_DELEGACIONES_ID);
        }
    }

    private function applyUnidadesScope($q, array $unidadIds): void
    {
        $unidadIds = array_values(array_unique(array_map('intval', $unidadIds)));
        $unidadIds = array_values(array_filter($unidadIds, fn ($id) => $id > 0));

        if (empty($unidadIds)) {
            $q->whereRaw('1 = 0');
            return;
        }

        $q->where(function ($scope) use ($unidadIds) {
            $scope->whereIn('hechos.unidad_org_id', $unidadIds)
                ->orWhere(function ($legacy) use ($unidadIds) {
                    $legacy->whereNull('hechos.unidad_org_id')
                        ->whereExists(function ($sub) use ($unidadIds) {
                            $sub->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'hechos.created_by')
                                ->whereIn('users.unidad_id', $unidadIds);
                        });
                });
        });
    }
}
