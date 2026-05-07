<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiesgoDashboardController extends Controller
{
    private const TZ = 'America/Mexico_City';

    public function index()
    {
        [$fechaInicio, $fechaFin, $fechaMin, $fechaMax, $diasDisponibles] = $this->defaultDateRange();

        return view('waze.riesgo.index', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'fechaMin' => $fechaMin,
            'fechaMax' => $fechaMax,
            'diasDisponibles' => $diasDisponibles,
            'hechosConCoordenada' => DB::table('hechos')
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->count(),
            'wazeRecientes' => DB::table('waze_alerts')
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where('published_at', '>=', Carbon::now(self::TZ)->subHours(72)->toDateTimeString())
                ->count(),
        ]);
    }

    public function data(Request $request)
    {
        $precision = (int) $request->query('precision', 3);
        if (!in_array($precision, [2, 3, 4], true)) {
            $precision = 3;
        }

        [$defaultDesde, $defaultHasta] = $this->defaultDateRange();
        $desde = $request->query('desde', $defaultDesde);
        $hasta = $request->query('hasta', $defaultHasta);

        try {
            $desdeDate = Carbon::parse($desde, self::TZ)->toDateString();
            $hastaDate = Carbon::parse($hasta, self::TZ)->toDateString();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Fechas invalidas'], 422);
        }

        if ($desdeDate > $hastaDate) {
            [$desdeDate, $hastaDate] = [$hastaDate, $desdeDate];
        }

        $ventana = $this->clampInt($request->query('ventana', 60), 15, 240, 60);
        $wazeHoras = $this->clampInt($request->query('waze_horas', 72), 1, 168, 72);
        $horizonte = $this->clampInt($request->query('horizonte', 3), 1, 12, 3);
        $limite = $this->clampInt($request->query('limite', 50), 20, 300, 50);

        $now = Carbon::now(self::TZ);
        $desdeDT = Carbon::parse($desdeDate, self::TZ)->startOfDay()->toDateTimeString();
        $hastaDT = Carbon::parse($hastaDate, self::TZ)->endOfDay()->toDateTimeString();
        $wazeDesde = $now->copy()->subHours($wazeHoras)->toDateTimeString();
        $wazeVentanaDesde = $now->copy()->subMinutes($ventana)->toDateTimeString();
        $recentSince = Carbon::parse($hastaDate, self::TZ)->subDays(29)->toDateString();
        $previousSince = Carbon::parse($recentSince, self::TZ)->subDays(30)->toDateString();

        $horasHorizonte = [];
        for ($i = 0; $i < $horizonte; $i++) {
            $horasHorizonte[] = ($now->hour + $i) % 24;
        }

        $hourPlaceholders = implode(',', array_fill(0, count($horasHorizonte), '?'));

        /*
        |--------------------------------------------------------------------------
        | 1) Hechos historicos por celda, con senales de tendencia y horario
        |--------------------------------------------------------------------------
        */
        $hechosCells = DB::table('hechos')
            ->selectRaw("
                CONCAT(ROUND(lat, ?), ',', ROUND(lng, ?)) AS cell,
                ROUND(AVG(lat), ?) AS lat,
                ROUND(AVG(lng), ?) AS lng,
                COUNT(*) AS hechos_hist,
                SUM(CASE WHEN fecha >= ? THEN 1 ELSE 0 END) AS hechos_30d,
                SUM(CASE WHEN fecha >= ? AND fecha < ? THEN 1 ELSE 0 END) AS hechos_prev_30d,
                SUM(CASE WHEN HOUR(hora) IN ({$hourPlaceholders}) THEN 1 ELSE 0 END) AS hechos_horizonte,
                SUM(CASE WHEN COALESCE(es_relevante, 0) = 1 THEN 1 ELSE 0 END) AS relevantes,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'TURNADO' THEN 1 ELSE 0 END) AS turnados,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN (COALESCE(vehiculos_mp, 0) + COALESCE(personas_mp, 0)) > 0 THEN 1 ELSE 0 END) AS mp_eventos,
                MIN(fecha) AS fecha_min,
                MAX(fecha) AS fecha_max
            ", array_merge(
                [$precision, $precision, $precision, $precision, $recentSince, $previousSince, $recentSince],
                $horasHorizonte
            ))
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('fecha', [$desdeDate, $hastaDate])
            ->groupBy('cell')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2) Waze reciente: puntos crudos y agregados por celda dinamica
        |--------------------------------------------------------------------------
        */
        $wazePoints = DB::table('waze_alerts')
            ->select('id', 'lat', 'lng', 'type', 'subtype', 'street', 'street_norm', 'published_at')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('published_at', '>=', $wazeDesde)
            ->orderByDesc('published_at')
            ->limit(1200)
            ->get();

        $wazeAgg = DB::table('waze_alerts')
            ->selectRaw("
                CONCAT(ROUND(lat, {$precision}), ',', ROUND(lng, {$precision})) AS cell,
                ROUND(AVG(lat), 7) AS lat,
                ROUND(AVG(lng), 7) AS lng,
                COUNT(*) AS waze_total,
                SUM(CASE WHEN type = 'JAM' THEN 1 ELSE 0 END) AS jams_now,
                SUM(CASE WHEN type = 'ACCIDENT' THEN 1 ELSE 0 END) AS accidents_now,
                SUM(CASE WHEN published_at >= ? THEN 1 ELSE 0 END) AS waze_window,
                MAX(published_at) AS last_waze_at
            ", [$wazeVentanaDesde])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('published_at', '>=', $wazeDesde)
            ->groupBy('cell')
            ->get()
            ->keyBy('cell');

        /*
        |--------------------------------------------------------------------------
        | 3) Matches Waze-Hechos, ajustados a la precision activa
        |--------------------------------------------------------------------------
        */
        $matchCellExpression = "CONCAT(ROUND(SUBSTRING_INDEX(cell_key, ',', 1) + 0.0, {$precision}), ',', ROUND(SUBSTRING_INDEX(cell_key, ',', -1) + 0.0, {$precision}))";

        $matchesAgg = DB::table('waze_hecho_matches')
            ->selectRaw("
                {$matchCellExpression} AS cell,
                COUNT(*) AS matches,
                ROUND(AVG(min_accident_to_hecho), 1) AS avg_accident_lead,
                ROUND(AVG(min_hecho_to_jam), 1) AS avg_jam_lag,
                MAX(hecho_at) AS last_match_at
            ")
            ->whereBetween('hecho_at', [$desdeDT, $hastaDT])
            ->groupBy('cell')
            ->get()
            ->keyBy('cell');

        $matches = DB::table('waze_hecho_matches')
            ->selectRaw("
                {$matchCellExpression} AS cell,
                ROUND(SUBSTRING_INDEX(cell_key, ',', 1) + 0.0, {$precision}) AS lat,
                ROUND(SUBSTRING_INDEX(cell_key, ',', -1) + 0.0, {$precision}) AS lng,
                hecho_id,
                waze_accident_at,
                waze_first_jam_at,
                min_accident_to_hecho,
                min_hecho_to_jam
            ")
            ->whereBetween('hecho_at', [$desdeDT, $hastaDT])
            ->orderByDesc('hecho_at')
            ->limit(300)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4) Fusion predictiva: historia + tendencia + hora + Waze vivo + matches
        |--------------------------------------------------------------------------
        */
        $cells = [];

        foreach ($hechosCells as $row) {
            $cells[$row->cell] = [
                'cell' => (string) $row->cell,
                'lat' => (float) $row->lat,
                'lng' => (float) $row->lng,
                'hechos_hist' => (int) $row->hechos_hist,
                'hechos_30d' => (int) $row->hechos_30d,
                'hechos_prev_30d' => (int) $row->hechos_prev_30d,
                'hechos_horizonte' => (int) $row->hechos_horizonte,
                'relevantes' => (int) $row->relevantes,
                'turnados' => (int) $row->turnados,
                'pendientes' => (int) $row->pendientes,
                'mp_eventos' => (int) $row->mp_eventos,
                'fecha_min' => $row->fecha_min,
                'fecha_max' => $row->fecha_max,
                'jams_now' => 0,
                'accidents_now' => 0,
                'waze_total' => 0,
                'waze_window' => 0,
                'last_waze_at' => null,
                'matches' => 0,
                'avg_accident_lead' => null,
                'avg_jam_lag' => null,
                'last_match_at' => null,
            ];
        }

        foreach ($wazeAgg as $cell => $row) {
            if (!isset($cells[$cell])) {
                $cells[$cell] = [
                    'cell' => (string) $cell,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'hechos_hist' => 0,
                    'hechos_30d' => 0,
                    'hechos_prev_30d' => 0,
                    'hechos_horizonte' => 0,
                    'relevantes' => 0,
                    'turnados' => 0,
                    'pendientes' => 0,
                    'mp_eventos' => 0,
                    'fecha_min' => null,
                    'fecha_max' => null,
                    'matches' => 0,
                    'avg_accident_lead' => null,
                    'avg_jam_lag' => null,
                    'last_match_at' => null,
                ];
            }

            $cells[$cell]['jams_now'] = (int) $row->jams_now;
            $cells[$cell]['accidents_now'] = (int) $row->accidents_now;
            $cells[$cell]['waze_total'] = (int) $row->waze_total;
            $cells[$cell]['waze_window'] = (int) $row->waze_window;
            $cells[$cell]['last_waze_at'] = $row->last_waze_at;
        }

        foreach ($matchesAgg as $cell => $row) {
            if (!isset($cells[$cell])) {
                [$lat, $lng] = $this->latLngFromCell((string) $cell);

                $cells[$cell] = [
                    'cell' => (string) $cell,
                    'lat' => $lat,
                    'lng' => $lng,
                    'hechos_hist' => 0,
                    'hechos_30d' => 0,
                    'hechos_prev_30d' => 0,
                    'hechos_horizonte' => 0,
                    'relevantes' => 0,
                    'turnados' => 0,
                    'pendientes' => 0,
                    'mp_eventos' => 0,
                    'fecha_min' => null,
                    'fecha_max' => null,
                    'jams_now' => 0,
                    'accidents_now' => 0,
                    'waze_total' => 0,
                    'waze_window' => 0,
                    'last_waze_at' => null,
                ];
            }

            $cells[$cell]['matches'] = (int) $row->matches;
            $cells[$cell]['avg_accident_lead'] = $row->avg_accident_lead !== null ? (float) $row->avg_accident_lead : null;
            $cells[$cell]['avg_jam_lag'] = $row->avg_jam_lag !== null ? (float) $row->avg_jam_lag : null;
            $cells[$cell]['last_match_at'] = $row->last_match_at;
        }

        $riesgo = collect($cells)
            ->map(fn (array $cell) => $this->scoreCell($cell))
            ->filter(fn (array $cell) => $cell['score'] > 0)
            ->sortByDesc('score')
            ->take($limite)
            ->values();

        $porHora = $this->porHora($desdeDate, $hastaDate);
        $rankingTipo = $this->ranking($desdeDate, $hastaDate, 'tipo_hecho', 8);
        $rankingMunicipio = $this->ranking($desdeDate, $hastaDate, 'municipio', 8);

        $topRiesgo = $riesgo->first();
        $zonasAltas = $riesgo
            ->filter(fn (array $cell) => in_array($cell['nivel'], ['critico', 'alto'], true))
            ->count();

        $kpis = [
            'hechos' => (int) $hechosCells->sum('hechos_hist'),
            'jams' => (int) $wazePoints->where('type', 'JAM')->count(),
            'accidents' => (int) $wazePoints->where('type', 'ACCIDENT')->count(),
            'waze_total' => (int) $wazePoints->count(),
            'matches' => (int) $matchesAgg->sum(fn ($row) => (int) $row->matches),
            'top' => $topRiesgo ? (float) $topRiesgo['score'] : 0,
            'top_nivel' => $topRiesgo['nivel_label'] ?? 'Sin senal',
            'zonas_altas' => $zonasAltas,
            'celdas' => $riesgo->count(),
            'horizonte' => $horizonte,
            'ventana' => $ventana,
            'waze_horas' => $wazeHoras,
            'fecha_min' => $desdeDate,
            'fecha_max' => $hastaDate,
            'hora_base' => $now->format('H:00'),
        ];

        return response()->json([
            'kpis' => $kpis,
            'summary' => [
                'por_hora' => $porHora,
                'ranking_tipo' => $rankingTipo,
                'ranking_municipio' => $rankingMunicipio,
                'top_zonas' => $riesgo->take(12)->values(),
                'bandas' => $riesgo->groupBy('nivel')->map->count(),
            ],
            'hechos_cells' => $hechosCells->map(fn ($row) => [
                'cell' => (string) $row->cell,
                'lat' => (float) $row->lat,
                'lng' => (float) $row->lng,
                'total' => (int) $row->hechos_hist,
                'hechos_hist' => (int) $row->hechos_hist,
                'hechos_30d' => (int) $row->hechos_30d,
                'hechos_horizonte' => (int) $row->hechos_horizonte,
            ])->values(),
            'waze_points' => $wazePoints,
            'matches' => $matches,
            'riesgo_cells' => $riesgo,
            'filters' => [
                'desde' => $desdeDate,
                'hasta' => $hastaDate,
                'precision' => $precision,
                'ventana' => $ventana,
                'waze_horas' => $wazeHoras,
                'horizonte' => $horizonte,
                'limite' => $limite,
            ],
        ]);
    }

    private function defaultDateRange(): array
    {
        $tz = self::TZ;
        $fechaMin = DB::table('hechos')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->min('fecha');
        $fechaMax = DB::table('hechos')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->max('fecha');

        $hasta = $fechaMax
            ? Carbon::parse($fechaMax, $tz)
            : Carbon::now($tz);

        $desde = $hasta->copy()->subDays(179);

        if ($fechaMin) {
            $min = Carbon::parse($fechaMin, $tz);
            if ($min->gt($desde)) {
                $desde = $min;
            }
        }

        $diasDisponibles = $fechaMin && $fechaMax
            ? Carbon::parse($fechaMin, $tz)->diffInDays(Carbon::parse($fechaMax, $tz)) + 1
            : 0;

        return [
            $desde->toDateString(),
            $hasta->toDateString(),
            $fechaMin,
            $fechaMax,
            $diasDisponibles,
        ];
    }

    private function clampInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

    private function latLngFromCell(string $cell): array
    {
        $parts = array_map('trim', explode(',', $cell));

        if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return [0.0, 0.0];
        }

        return [(float) $parts[0], (float) $parts[1]];
    }

    private function scoreCell(array $cell): array
    {
        $hist = (int) ($cell['hechos_hist'] ?? 0);
        $recent = (int) ($cell['hechos_30d'] ?? 0);
        $previous = (int) ($cell['hechos_prev_30d'] ?? 0);
        $horizonHits = (int) ($cell['hechos_horizonte'] ?? 0);
        $jams = (int) ($cell['jams_now'] ?? 0);
        $accidents = (int) ($cell['accidents_now'] ?? 0);
        $wazeWindow = (int) ($cell['waze_window'] ?? 0);
        $matches = (int) ($cell['matches'] ?? 0);
        $relevantes = (int) ($cell['relevantes'] ?? 0);
        $turnados = (int) ($cell['turnados'] ?? 0);
        $pendientes = (int) ($cell['pendientes'] ?? 0);
        $mpEventos = (int) ($cell['mp_eventos'] ?? 0);

        $trendPct = $previous > 0
            ? (($recent - $previous) / $previous) * 100
            : ($recent > 0 ? 100 : 0);

        $histScore = min(58, sqrt(max(0, $hist)) * 6.8);
        $recentScore = min(32, $recent * 1.15);
        $trendScore = max(0, min(28, $trendPct / 4));
        $hourScore = $hist > 0 ? min(22, ($horizonHits / max(1, $hist)) * 45) : 0;
        $wazeScore = min(42, ($jams * 3.4) + ($accidents * 11) + ($wazeWindow * 5));
        $matchScore = min(28, $matches * 7);
        $severityScore = min(22, ($relevantes * 4) + ($turnados * 3) + ($mpEventos * 2.2) + ($pendientes * .8));

        $score = round($histScore + $recentScore + $trendScore + $hourScore + $wazeScore + $matchScore + $severityScore, 1);
        $nivel = $this->nivelRiesgo($score);
        $confidence = min(98, round(32 + min(34, $hist * 1.7) + min(18, ($jams + $accidents) * 3.5) + min(14, $matches * 4) + min(10, $recent)));

        return array_merge($cell, [
            'score' => $score,
            'nivel' => $nivel['key'],
            'nivel_label' => $nivel['label'],
            'color' => $nivel['color'],
            'accion' => $nivel['accion'],
            'confidence' => $confidence,
            'trend_pct' => round($trendPct, 1),
            'signals' => [
                'historial' => round($histScore, 1),
                'recencia' => round($recentScore, 1),
                'tendencia' => round($trendScore, 1),
                'horario' => round($hourScore, 1),
                'waze' => round($wazeScore, 1),
                'matches' => round($matchScore, 1),
                'severidad' => round($severityScore, 1),
            ],
        ]);
    }

    private function nivelRiesgo(float $score): array
    {
        if ($score >= 95) {
            return [
                'key' => 'critico',
                'label' => 'Critico',
                'color' => '#ff3b30',
                'accion' => 'Despacho sugerido',
            ];
        }

        if ($score >= 70) {
            return [
                'key' => 'alto',
                'label' => 'Alto',
                'color' => '#ff9f0a',
                'accion' => 'Patrullaje preventivo',
            ];
        }

        if ($score >= 42) {
            return [
                'key' => 'vigilancia',
                'label' => 'Vigilancia',
                'color' => '#00d4ff',
                'accion' => 'Monitoreo activo',
            ];
        }

        return [
            'key' => 'latente',
            'label' => 'Latente',
            'color' => '#34d399',
            'accion' => 'Observacion',
        ];
    }

    private function porHora(string $desdeDate, string $hastaDate): array
    {
        $rows = DB::table('hechos')
            ->selectRaw("LPAD(HOUR(hora), 2, '0') AS hora, COUNT(*) AS total")
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('fecha', [$desdeDate, $hastaDate])
            ->groupByRaw("LPAD(HOUR(hora), 2, '0')")
            ->orderBy('hora')
            ->get()
            ->keyBy('hora');

        return collect(range(0, 23))
            ->map(function (int $hour) use ($rows) {
                $key = sprintf('%02d', $hour);

                return [
                    'label' => $key . ':00',
                    'total' => isset($rows[$key]) ? (int) $rows[$key]->total : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function ranking(string $desdeDate, string $hastaDate, string $column, int $limit): array
    {
        return DB::table('hechos')
            ->select($column, DB::raw('COUNT(*) AS total'))
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->whereBetween('fecha', [$desdeDate, $hastaDate])
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
}
