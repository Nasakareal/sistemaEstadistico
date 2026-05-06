<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaIncidenciasController extends Controller
{
    public function index()
    {
        return view('mapa.incidencias', [
            'fechaInicio' => now('America/Mexico_City')->startOfMonth()->toDateString(),
            'fechaFin' => now('America/Mexico_City')->toDateString(),
            'catalogos' => [
                'tipos' => $this->catalogo('tipo_hecho'),
                'situaciones' => $this->catalogo('situacion'),
                'municipios' => $this->catalogo('municipio'),
                'sectores' => $this->catalogo('sector'),
            ],
        ]);
    }

    private function catalogo(string $column): array
    {
        return DB::table('hechos')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->limit(250)
            ->pluck($column)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    public function data(Request $request)
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $precision = (int)($request->get('precision', 3));
        if ($precision < 2) $precision = 2;
        if ($precision > 5) $precision = 5;

        $limite = (int) $request->get('limite', 3000);
        if ($limite < 100) $limite = 100;
        if ($limite > 5000) $limite = 5000;

        $minTotal = (int) $request->get('min_total', 1);
        if ($minTotal < 1) $minTotal = 1;
        if ($minTotal > 99) $minTotal = 99;

        $q = $this->queryFiltrada($request);

        $rows = $q->selectRaw("
                ROUND(lat, ?) AS lat_r,
                ROUND(lng, ?) AS lng_r,
                COUNT(*) AS total,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'TURNADO' THEN 1 ELSE 0 END) AS turnados,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'RESUELTO' THEN 1 ELSE 0 END) AS resueltos,
                SUM(CASE WHEN COALESCE(es_relevante, 0) = 1 THEN 1 ELSE 0 END) AS relevantes,
                MIN(fecha) AS fecha_min,
                MAX(fecha) AS fecha_max
            ", [$precision, $precision])
            ->groupBy('lat_r','lng_r')
            ->havingRaw('COUNT(*) >= ?', [$minTotal])
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        $puntos = $rows->map(function ($r) {
            $turnados = (int) $r->turnados;
            $pendientes = (int) $r->pendientes;
            $relevantes = (int) $r->relevantes;
            $total = (int) $r->total;
            $score = round($total + ($turnados * 2.5) + ($pendientes * .65) + ($relevantes * 1.75), 2);

            return [
                'lat'       => (float)$r->lat_r,
                'lng'       => (float)$r->lng_r,
                'total'     => $total,
                'score'     => $score,
                'turnados'  => $turnados,
                'pendientes'=> $pendientes,
                'resueltos' => (int) $r->resueltos,
                'relevantes'=> $relevantes,
                'categoria' => $this->categoriaPunto($turnados, $pendientes, $relevantes, $total),
                'fecha_min' => $r->fecha_min,
                'fecha_max' => $r->fecha_max,
            ];
        })->values();

        return response()->json([
            'data' => $puntos,
            'summary' => $this->summary($request, $puntos),
            'filters' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'precision' => $precision,
                'limite' => $limite,
                'min_total' => $minTotal,
                'bbox' => $request->get('bbox'),
            ],
        ]);
    }

    public function hechos(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $tipo = $request->query('tipo_hecho');

        $precision = (int) $request->query('precision', 3);
        if ($precision < 2) {
            $precision = 2;
        }
        if ($precision > 5) {
            $precision = 5;
        }

        if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
            return response()->json([
                'message' => 'Coordenadas inválidas.',
            ], 422);
        }

        $lat = round((float) $lat, $precision);
        $lng = round((float) $lng, $precision);

        $rows = $this->queryFiltrada($request)
            ->whereRaw('ROUND(lat, ?) = ?', [$precision, $lat])
            ->whereRaw('ROUND(lng, ?) = ?', [$precision, $lng]);

        $hechos = $rows
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(250)
            ->get([
                'id',
                'folio_c5i',
                'fecha',
                'hora',
                'tipo_hecho',
                'situacion',
                'sector',
                'calle',
                'colonia',
                'municipio',
                'fuente_ubicacion',
                'es_relevante',
                'vehiculos_mp',
                'personas_mp',
                'lat',
                'lng',
            ]);

        return response()->json([
            'data' => $hechos->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'folio_c5i' => $row->folio_c5i,
                    'fecha' => $row->fecha,
                    'hora' => $row->hora,
                    'tipo_hecho' => $row->tipo_hecho,
                    'situacion' => $row->situacion,
                    'sector' => $row->sector,
                    'calle' => $row->calle,
                    'colonia' => $row->colonia,
                    'municipio' => $row->municipio,
                    'fuente_ubicacion' => $row->fuente_ubicacion,
                    'es_relevante' => (bool) $row->es_relevante,
                    'vehiculos_mp' => (int) $row->vehiculos_mp,
                    'personas_mp' => (int) $row->personas_mp,
                    'lat' => $row->lat !== null ? (float) $row->lat : null,
                    'lng' => $row->lng !== null ? (float) $row->lng : null,
                    'show_url' => route('hechos.show', ['hecho' => $row->id]),
                ];
            })->values(),
        ]);
    }

    private function queryFiltrada(Request $request)
    {
        $q = DB::table('hechos')
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if ($request->filled('desde')) {
            $q->whereDate('fecha', '>=', $request->get('desde'));
        }

        if ($request->filled('hasta')) {
            $q->whereDate('fecha', '<=', $request->get('hasta'));
        }

        foreach ([
            'tipo_hecho' => 'tipo_hecho',
            'situacion' => 'situacion',
            'municipio' => 'municipio',
            'sector' => 'sector',
        ] as $param => $column) {
            $values = $this->listaFiltro($request, $param);

            if (!empty($values)) {
                $q->whereIn($column, $values);
            }
        }

        if ($this->verdadero($request->get('solo_relevantes'))) {
            $q->where('es_relevante', true);
        }

        $horaDesde = $this->horaFiltro($request->get('hora_desde'));
        $horaHasta = $this->horaFiltro($request->get('hora_hasta'));

        if ($horaDesde && $horaHasta) {
            if ($horaDesde <= $horaHasta) {
                $q->whereBetween('hora', [$horaDesde, $horaHasta]);
            } else {
                $q->where(function ($sub) use ($horaDesde, $horaHasta) {
                    $sub->where('hora', '>=', $horaDesde)
                        ->orWhere('hora', '<=', $horaHasta);
                });
            }
        } elseif ($horaDesde) {
            $q->where('hora', '>=', $horaDesde);
        } elseif ($horaHasta) {
            $q->where('hora', '<=', $horaHasta);
        }

        $bbox = $this->bboxFiltro($request->get('bbox'));
        if ($bbox) {
            [$minLat, $minLng, $maxLat, $maxLng] = $bbox;
            $q->whereBetween('lat', [$minLat, $maxLat])
                ->whereBetween('lng', [$minLng, $maxLng]);
        }

        return $q;
    }

    private function summary(Request $request, $puntos): array
    {
        $base = $this->queryFiltrada($request);
        $totalHechos = (clone $base)->count();
        $turnados = (clone $base)->whereRaw("UPPER(COALESCE(situacion, '')) = 'TURNADO'")->count();
        $pendientes = (clone $base)->whereRaw("UPPER(COALESCE(situacion, '')) = 'PENDIENTE'")->count();
        $relevantes = (clone $base)->where('es_relevante', true)->count();
        $fechaMin = (clone $base)->min('fecha');
        $fechaMax = (clone $base)->max('fecha');

        $porHoraRows = (clone $base)
            ->selectRaw("LPAD(HOUR(hora), 2, '0') AS hora, COUNT(*) AS total")
            ->whereNotNull('hora')
            ->groupByRaw("LPAD(HOUR(hora), 2, '0')")
            ->orderBy('hora')
            ->get();

        $porHoraBase = collect(range(0, 23))
            ->mapWithKeys(fn ($hour) => [sprintf('%02d:00', $hour) => 0]);

        foreach ($porHoraRows as $row) {
            $label = sprintf('%02d:00', (int) $row->hora);
            if ($porHoraBase->has($label)) {
                $porHoraBase->put($label, (int) $row->total);
            }
        }

        $horaPico = $porHoraBase->sortDesc()->keys()->first() ?? '00:00';
        $dias = $fechaMin && $fechaMax
            ? max(1, \Carbon\Carbon::parse($fechaMin)->diffInDays(\Carbon\Carbon::parse($fechaMax)) + 1)
            : 1;

        return [
            'totales' => [
                'hechos' => $totalHechos,
                'puntos' => $puntos->count(),
                'turnados' => $turnados,
                'pendientes' => $pendientes,
                'relevantes' => $relevantes,
                'max_zona' => (int) ($puntos->max('total') ?? 0),
                'score_max' => (float) ($puntos->max('score') ?? 0),
                'promedio_diario' => round($totalHechos / $dias, 1),
                'fecha_min' => $fechaMin,
                'fecha_max' => $fechaMax,
                'hora_pico' => $horaPico,
                'hora_pico_total' => (int) ($porHoraBase[$horaPico] ?? 0),
            ],
            'rankings' => [
                'tipo_hecho' => $this->ranking($base, 'tipo_hecho', 10),
                'situacion' => $this->ranking($base, 'situacion', 8),
                'municipio' => $this->ranking($base, 'municipio', 12),
                'sector' => $this->ranking($base, 'sector', 10),
            ],
            'por_hora' => $porHoraBase
                ->map(fn ($total, $hora) => ['label' => $hora, 'total' => $total])
                ->values(),
            'top_zonas' => $puntos
                ->sortByDesc(fn ($punto) => ((float) $punto['score'] * 1000) + (int) $punto['total'])
                ->take(12)
                ->values(),
        ];
    }

    private function ranking($base, string $column, int $limit): array
    {
        return (clone $base)
            ->select($column, DB::raw('COUNT(*) AS total'))
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => trim((string) $row->{$column}),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function listaFiltro(Request $request, string $key): array
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : preg_split('/[|,]/', (string) $value);

        return collect($values)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();
    }

    private function horaFiltro($value): ?string
    {
        $value = trim((string) $value);

        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $match)) {
            return null;
        }

        return sprintf('%02d:%02d:00', (int) $match[1], (int) $match[2]);
    }

    private function bboxFiltro($value): ?array
    {
        if (!$value) {
            return null;
        }

        $parts = array_map('trim', explode(',', (string) $value));
        if (count($parts) !== 4) {
            return null;
        }

        if (count(array_filter($parts, 'is_numeric')) !== 4) {
            return null;
        }

        [$minLat, $minLng, $maxLat, $maxLng] = array_map('floatval', $parts);

        $minLat = max(-90, min(90, $minLat));
        $maxLat = max(-90, min(90, $maxLat));
        $minLng = max(-180, min(180, $minLng));
        $maxLng = max(-180, min(180, $maxLng));

        if ($minLat > $maxLat) {
            [$minLat, $maxLat] = [$maxLat, $minLat];
        }

        if ($minLng > $maxLng) {
            [$minLng, $maxLng] = [$maxLng, $minLng];
        }

        return [$minLat, $minLng, $maxLat, $maxLng];
    }

    private function verdadero($value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'si', 'sí', 'yes', 'on'], true);
    }

    private function categoriaPunto(int $turnados, int $pendientes, int $relevantes, int $total): string
    {
        if ($turnados > 0 || $relevantes > 0 || $total >= 10) {
            return 'critico';
        }

        if ($pendientes > 0 || $total >= 5) {
            return 'alerta';
        }

        return $total >= 2 ? 'observacion' : 'base';
    }
}
