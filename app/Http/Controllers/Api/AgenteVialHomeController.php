<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenteVialHomeController extends Controller
{
    private const MORELIA_CENTER_LAT = 19.705950;
    private const MORELIA_CENTER_LNG = -101.194983;
    private const MORELIA_LAT_MIN = 19.560000;
    private const MORELIA_LAT_MAX = 19.860000;
    private const MORELIA_LNG_MIN = -101.360000;
    private const MORELIA_LNG_MAX = -101.020000;

    public function mapa(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');
        $now = Carbon::now($tz);

        $targetHour = $this->clampInt($request->get('hour', $now->hour), 0, 23, $now->hour);
        $wazeHours = $this->clampInt($request->get('waze_hours', 6), 1, 24, 6);
        $historyDays = $this->clampInt($request->get('history_days', 90), 7, 365, 90);
        $limit = $this->clampInt($request->get('limit', 80), 20, 200, 80);

        $gridSize = (float) $request->get('grid_size', 0.006);
        if ($gridSize < 0.002 || $gridSize > 0.02) {
            $gridSize = 0.006;
        }

        $tipo = strtoupper(trim((string) $request->get('tipo', 'TODOS')));
        if (!in_array($tipo, ['TODOS', 'CHOQUES', 'CIERRES'], true)) {
            $tipo = 'TODOS';
        }

        $wazeStart = $now->copy()->subHours($wazeHours);
        $historyStart = $now->copy()->subDays($historyDays)->startOfDay();
        $historyEnd = $now->copy()->endOfDay();

        $wazeRows = $this->wazeBaseQuery($wazeStart, $tipo)
            ->limit($limit)
            ->get();

        [$wazeAlerts, $chaosCellsByKey] = $this->buildWazeLayers(
            $wazeRows,
            $gridSize,
            $tz,
            $now,
            $wazeHours
        );

        $riskCells = $this->buildRiskCells(
            $historyStart,
            $historyEnd,
            $targetHour,
            $gridSize,
            $chaosCellsByKey
        );

        $hourly = $this->buildHourlySummary($historyStart, $historyEnd);
        $chaosCells = array_values($chaosCellsByKey);

        usort($chaosCells, fn ($a, $b) => $b['score'] <=> $a['score']);
        usort($riskCells, fn ($a, $b) => $b['score'] <=> $a['score']);

        $topRisk = $riskCells[0] ?? null;

        return response()->json([
            'meta' => [
                'city' => 'Morelia',
                'target_hour' => $targetHour,
                'target_hour_label' => sprintf('%02d:00', $targetHour),
                'waze_hours' => $wazeHours,
                'history_days' => $historyDays,
                'grid_size' => $gridSize,
                'tipo' => $tipo,
                'generated_at' => $now->toDateTimeString(),
                'timezone' => $tz,
            ],
            'map' => [
                'center' => [
                    'lat' => self::MORELIA_CENTER_LAT,
                    'lng' => self::MORELIA_CENTER_LNG,
                ],
                'zoom' => 12.5,
            ],
            'layers' => [
                'waze_alerts' => $wazeAlerts,
                'chaos_cells' => array_slice($chaosCells, 0, 30),
                'risk_cells' => array_slice($riskCells, 0, 30),
            ],
            'counts' => [
                'waze_alerts' => count($wazeAlerts),
                'choques' => count(array_filter($wazeAlerts, fn ($item) => $item['type'] === 'waze_accident')),
                'cierres' => count(array_filter($wazeAlerts, fn ($item) => $item['type'] === 'waze_road_closed')),
                'chaos_cells' => count($chaosCells),
                'risk_cells' => count($riskCells),
                'top_crash_probability' => $topRisk ? $topRisk['crash_probability'] : 0,
            ],
            'summary' => [
                'hourly' => $hourly,
                'top_risk' => $topRisk,
            ],
        ], 200);
    }

    public function filtros(Request $request)
    {
        return response()->json([
            'hour_options' => collect(range(0, 23))
                ->map(fn (int $hour) => [
                    'value' => $hour,
                    'label' => sprintf('%02d:00', $hour),
                ])
                ->values(),
            'waze_hours_options' => [1, 3, 6, 12, 24],
            'history_days_options' => [30, 60, 90, 180, 365],
            'tipo_options' => [
                ['value' => 'TODOS', 'label' => 'Todos'],
                ['value' => 'CHOQUES', 'label' => 'Choques'],
                ['value' => 'CIERRES', 'label' => 'Cierres'],
            ],
            'default_values' => [
                'hour' => Carbon::now(config('app.timezone', 'America/Mexico_City'))->hour,
                'waze_hours' => 6,
                'history_days' => 90,
                'grid_size' => 0.006,
                'tipo' => 'TODOS',
            ],
        ], 200);
    }

    public function show($id)
    {
        $row = DB::table('waze_alerts')
            ->select([
                'id',
                'uuid',
                'waze_id',
                'type',
                'subtype',
                'country',
                'city',
                'street',
                'street_norm',
                'lat',
                'lng',
                'cell_key',
                'pub_millis',
                'published_at',
                'notified',
                'is_read',
                'raw',
                'created_at',
                'updated_at',
            ])
            ->where('id', $id)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($q) {
                $this->applyMoreliaWazeFilter($q);
            })
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Alerta Waze de Morelia no encontrada.',
            ], 404);
        }

        $tz = config('app.timezone', 'America/Mexico_City');
        $alertType = $this->resolveAlertType($row->type, $row->subtype);
        $published = $row->published_at ?: $row->created_at;

        return response()->json([
            'alerta' => [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'waze_id' => $row->waze_id,
                'tipo' => $alertType,
                'waze_type' => $row->type,
                'waze_subtype' => $row->subtype,
                'title' => $alertType === 'cierre' ? 'CIERRE WAZE MORELIA' : 'CHOQUE WAZE MORELIA',
                'country' => $row->country,
                'street' => $row->street,
                'street_norm' => $row->street_norm,
                'city' => $row->city,
                'cell_key' => $row->cell_key,
                'pub_millis' => $row->pub_millis,
                'lat' => $row->lat !== null ? (float) $row->lat : null,
                'lng' => $row->lng !== null ? (float) $row->lng : null,
                'notified' => (bool) ($row->notified ?? false),
                'is_read' => (bool) ($row->is_read ?? false),
                'raw' => $row->raw ? json_decode($row->raw, true) : null,
                'published_at' => $published
                    ? Carbon::parse($published)->timezone($tz)->toDateTimeString()
                    : null,
                'created_at' => $row->created_at
                    ? Carbon::parse($row->created_at)->timezone($tz)->toDateTimeString()
                    : null,
                'updated_at' => $row->updated_at
                    ? Carbon::parse($row->updated_at)->timezone($tz)->toDateTimeString()
                    : null,
            ],
        ], 200);
    }

    private function wazeBaseQuery(Carbon $wazeStart, string $tipo)
    {
        return DB::table('waze_alerts')
            ->select([
                'id',
                'uuid',
                'waze_id',
                'type',
                'subtype',
                'country',
                'city',
                'street',
                'street_norm',
                'lat',
                'lng',
                'cell_key',
                'pub_millis',
                'published_at',
                'notified',
                'is_read',
                'created_at',
                'updated_at',
            ])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($q) {
                $this->applyMoreliaWazeFilter($q);
            })
            ->where(function ($q) use ($wazeStart) {
                $q->where('published_at', '>=', $wazeStart)
                    ->orWhere(function ($q2) use ($wazeStart) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $wazeStart);
                    });
            })
            ->where(function ($q) use ($tipo) {
                $this->applyWazeTipoFilter($q, $tipo);
            })
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'));
    }

    private function applyWazeTipoFilter($query, string $tipo): void
    {
        if ($tipo === 'CHOQUES') {
            $this->applyWazeCrashFilter($query);
            return;
        }

        if ($tipo === 'CIERRES') {
            $this->applyWazeClosureFilter($query);
            return;
        }

        $query->where(function ($q) {
            $q->where(function ($qq) {
                $this->applyWazeCrashFilter($qq);
            })->orWhere(function ($qq) {
                $this->applyWazeClosureFilter($qq);
            });
        });
    }

    private function applyWazeCrashFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw("UPPER(COALESCE(type, '')) = 'ACCIDENT'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) = 'ACCIDENT'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) = 'CRASH'")
                ->orWhereRaw("UPPER(COALESCE(type, '')) LIKE '%ACCIDENT%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%ACCIDENT%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%CRASH%'");
        });
    }

    private function applyWazeClosureFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw("UPPER(COALESCE(type, '')) = 'ROAD_CLOSED'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) = 'ROAD_CLOSED'")
                ->orWhereRaw("UPPER(COALESCE(type, '')) LIKE '%ROAD_CLOSED%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%ROAD_CLOSED%'")
                ->orWhereRaw("UPPER(COALESCE(type, '')) LIKE '%CLOSED%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%CLOSED%'")
                ->orWhereRaw("UPPER(COALESCE(type, '')) LIKE '%BLOCK%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%BLOCK%'")
                ->orWhereRaw("UPPER(COALESCE(type, '')) LIKE '%CLOSURE%'")
                ->orWhereRaw("UPPER(COALESCE(subtype, '')) LIKE '%CLOSURE%'");
        });
    }

    private function applyMoreliaWazeFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw("UPPER(TRIM(COALESCE(city, ''))) = ?", ['MORELIA'])
                ->orWhere(function ($bbox) {
                    $bbox->whereBetween('lat', [self::MORELIA_LAT_MIN, self::MORELIA_LAT_MAX])
                        ->whereBetween('lng', [self::MORELIA_LNG_MIN, self::MORELIA_LNG_MAX]);
                });
        });
    }

    private function applyMoreliaHechosFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereRaw("UPPER(TRIM(COALESCE(municipio, ''))) = ?", ['MORELIA'])
                ->orWhere(function ($bbox) {
                    $bbox->whereBetween('lat', [self::MORELIA_LAT_MIN, self::MORELIA_LAT_MAX])
                        ->whereBetween('lng', [self::MORELIA_LNG_MIN, self::MORELIA_LNG_MAX]);
                });
        });
    }

    private function buildWazeLayers($wazeRows, float $gridSize, string $tz, Carbon $now, int $wazeHours): array
    {
        $alerts = [];
        $chaosCells = [];

        foreach ($wazeRows as $row) {
            $alertType = $this->resolveAlertType($row->type, $row->subtype);
            $published = $row->published_at ?: $row->created_at;
            $publishedAt = $published
                ? Carbon::parse($published)->timezone($tz)->toDateTimeString()
                : null;

            $lat = round((float) $row->lat, 6);
            $lng = round((float) $row->lng, 6);
            $cell = $this->cellFromLatLng($lat, $lng, $gridSize);
            $key = $this->cellKey($cell['lat'], $cell['lng']);

            $alerts[] = [
                'type' => $alertType === 'cierre' ? 'waze_road_closed' : 'waze_accident',
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'waze_id' => $row->waze_id,
                'waze_type' => $row->type,
                'waze_subtype' => $row->subtype,
                'country' => $row->country,
                'lat' => $lat,
                'lng' => $lng,
                'street' => $row->street,
                'street_norm' => $row->street_norm,
                'city' => $row->city,
                'cell_key' => $key,
                'pub_millis' => $row->pub_millis,
                'notified' => (bool) ($row->notified ?? false),
                'is_read' => (bool) ($row->is_read ?? false),
                'title' => $alertType === 'cierre' ? 'CIERRE WAZE MORELIA' : 'CHOQUE WAZE MORELIA',
                'subtitle' => $row->street ?: ($row->city ?: 'Ubicación sin calle'),
                'published_at' => $publishedAt,
                'style' => [
                    'kind' => 'marker',
                    'icon' => $alertType === 'cierre' ? 'waze_road_closed' : 'waze_accident',
                    'marker_color' => $alertType === 'cierre' ? '#FF6F00' : '#FFD600',
                    'border_color' => $alertType === 'cierre' ? '#BF360C' : '#D50000',
                    'pulse' => true,
                    'z_index' => $alertType === 'cierre' ? 110 : 100,
                ],
            ];

            if (!isset($chaosCells[$key])) {
                $chaosCells[$key] = [
                    'type' => 'chaos_cell',
                    'cell_key' => $key,
                    'lat' => round($cell['lat'] + ($gridSize / 2), 6),
                    'lng' => round($cell['lng'] + ($gridSize / 2), 6),
                    'score' => 0.0,
                    'total' => 0,
                    'choques' => 0,
                    'cierres' => 0,
                    'last_waze_at' => null,
                ];
            }

            $minutesAgo = $published
                ? min($wazeHours * 60, Carbon::parse($published)->timezone($tz)->diffInMinutes($now))
                : $wazeHours * 60;
            $recency = max(0, 1 - ($minutesAgo / max(1, $wazeHours * 60)));
            $weight = $alertType === 'cierre' ? 14 : 18;

            $chaosCells[$key]['score'] += $weight + (10 * $recency);
            $chaosCells[$key]['total']++;
            $chaosCells[$key][$alertType === 'cierre' ? 'cierres' : 'choques']++;

            if (
                $publishedAt &&
                (
                    $chaosCells[$key]['last_waze_at'] === null ||
                    Carbon::parse($publishedAt)->gt(Carbon::parse($chaosCells[$key]['last_waze_at']))
                )
            ) {
                $chaosCells[$key]['last_waze_at'] = $publishedAt;
            }
        }

        foreach ($chaosCells as $key => $cell) {
            $level = $this->chaosLevel((float) $cell['score']);
            $chaosCells[$key]['score'] = round((float) $cell['score'], 1);
            $chaosCells[$key]['nivel'] = $level['key'];
            $chaosCells[$key]['nivel_label'] = $level['label'];
            $chaosCells[$key]['color'] = $level['color'];
            $chaosCells[$key]['radius_meters'] = min(720, 180 + ($chaosCells[$key]['score'] * 10));
            $chaosCells[$key]['accion'] = $level['accion'];
        }

        return [$alerts, $chaosCells];
    }

    private function buildRiskCells(
        Carbon $historyStart,
        Carbon $historyEnd,
        int $targetHour,
        float $gridSize,
        array $chaosCellsByKey
    ): array {
        $grid = number_format($gridSize, 6, '.', '');
        $cellLatExpr = "FLOOR(lat / {$grid}) * {$grid}";
        $cellLngExpr = "FLOOR(lng / {$grid}) * {$grid}";
        $crashExpr = $this->hechoCrashSqlExpression();

        $rows = DB::table('hechos')
            ->selectRaw("
                {$cellLatExpr} AS cell_lat,
                {$cellLngExpr} AS cell_lng,
                ROUND(AVG(lat), 6) AS lat,
                ROUND(AVG(lng), 6) AS lng,
                COUNT(*) AS historic_total,
                SUM(CASE WHEN {$crashExpr} THEN 1 ELSE 0 END) AS historic_crashes,
                SUM(CASE WHEN COALESCE(es_relevante, 0) = 1 THEN 1 ELSE 0 END) AS relevantes,
                MAX(CONCAT(fecha, ' ', COALESCE(hora, '00:00:00'))) AS last_event_at
            ")
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('lat', '!=', 0)
            ->where('lng', '!=', 0)
            ->whereBetween('created_at', [$historyStart, $historyEnd])
            ->whereNotNull('hora')
            ->whereRaw('HOUR(hora) = ?', [$targetHour])
            ->where(function ($q) {
                $this->applyMoreliaHechosFilter($q);
            })
            ->groupByRaw("{$cellLatExpr}, {$cellLngExpr}")
            ->get();

        $cells = [];

        foreach ($rows as $row) {
            $key = $this->cellKey((float) $row->cell_lat, (float) $row->cell_lng);
            $chaos = $chaosCellsByKey[$key] ?? null;
            $cells[$key] = $this->scoreRiskCell([
                'cell_key' => $key,
                'lat' => round((float) $row->lat, 6),
                'lng' => round((float) $row->lng, 6),
                'historic_total' => (int) $row->historic_total,
                'historic_crashes' => max((int) $row->historic_crashes, (int) $row->historic_total),
                'relevantes' => (int) $row->relevantes,
                'last_event_at' => $row->last_event_at,
                'recent_waze_score' => $chaos ? (float) $chaos['score'] : 0,
                'recent_waze_total' => $chaos ? (int) $chaos['total'] : 0,
                'recent_choques' => $chaos ? (int) $chaos['choques'] : 0,
                'recent_cierres' => $chaos ? (int) $chaos['cierres'] : 0,
            ]);
        }

        foreach ($chaosCellsByKey as $key => $chaos) {
            if (isset($cells[$key])) {
                continue;
            }

            $cells[$key] = $this->scoreRiskCell([
                'cell_key' => $key,
                'lat' => (float) $chaos['lat'],
                'lng' => (float) $chaos['lng'],
                'historic_total' => 0,
                'historic_crashes' => 0,
                'relevantes' => 0,
                'last_event_at' => $chaos['last_waze_at'],
                'recent_waze_score' => (float) $chaos['score'],
                'recent_waze_total' => (int) $chaos['total'],
                'recent_choques' => (int) $chaos['choques'],
                'recent_cierres' => (int) $chaos['cierres'],
            ]);
        }

        return array_values(array_filter($cells, fn ($cell) => $cell['score'] > 0));
    }

    private function scoreRiskCell(array $cell): array
    {
        $historic = (int) $cell['historic_total'];
        $crashes = (int) $cell['historic_crashes'];
        $relevantes = (int) $cell['relevantes'];
        $recentWaze = (int) $cell['recent_waze_total'];
        $recentWazeScore = (float) $cell['recent_waze_score'];
        $recentChoques = (int) $cell['recent_choques'];
        $recentCierres = (int) $cell['recent_cierres'];

        $historicScore = min(48, sqrt(max(0, $historic)) * 8.5);
        $crashScore = min(32, sqrt(max(0, $crashes)) * 7.5);
        $relevanceScore = min(12, $relevantes * 2.4);
        $wazeScore = min(42, $recentWazeScore * 0.95);
        $score = round($historicScore + $crashScore + $relevanceScore + $wazeScore, 1);
        $probability = min(0.94, round($score / 125, 3));
        $level = $this->riskLevel($score);

        return [
            'type' => 'risk_cell',
            'cell_key' => $cell['cell_key'],
            'lat' => (float) $cell['lat'],
            'lng' => (float) $cell['lng'],
            'score' => $score,
            'nivel' => $level['key'],
            'nivel_label' => $level['label'],
            'color' => $level['color'],
            'accion' => $level['accion'],
            'radius_meters' => min(780, 180 + ($score * 6.2)),
            'crash_probability' => $probability,
            'crash_probability_pct' => (int) round($probability * 100),
            'historic_total' => $historic,
            'historic_crashes' => $crashes,
            'relevantes' => $relevantes,
            'recent_waze_total' => $recentWaze,
            'recent_choques' => $recentChoques,
            'recent_cierres' => $recentCierres,
            'last_event_at' => $cell['last_event_at'],
        ];
    }

    private function buildHourlySummary(Carbon $historyStart, Carbon $historyEnd): array
    {
        $crashExpr = $this->hechoCrashSqlExpression();

        $rows = DB::table('hechos')
            ->selectRaw("
                LPAD(HOUR(hora), 2, '0') AS hour_key,
                COUNT(*) AS total,
                SUM(CASE WHEN {$crashExpr} THEN 1 ELSE 0 END) AS choques
            ")
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('lat', '!=', 0)
            ->where('lng', '!=', 0)
            ->whereBetween('created_at', [$historyStart, $historyEnd])
            ->whereNotNull('hora')
            ->where(function ($q) {
                $this->applyMoreliaHechosFilter($q);
            })
            ->groupByRaw("LPAD(HOUR(hora), 2, '0')")
            ->get()
            ->keyBy('hour_key');

        return collect(range(0, 23))
            ->map(function (int $hour) use ($rows) {
                $key = sprintf('%02d', $hour);
                $row = $rows[$key] ?? null;

                return [
                    'hour' => $hour,
                    'label' => $key . ':00',
                    'total' => $row ? (int) $row->total : 0,
                    'choques' => $row ? (int) $row->choques : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function hechoCrashSqlExpression(): string
    {
        return "
            UPPER(COALESCE(tipo_hecho, '')) LIKE '%CHOQUE%'
            OR UPPER(COALESCE(tipo_hecho, '')) LIKE '%COLISION%'
            OR UPPER(COALESCE(tipo_hecho, '')) LIKE '%COLISIÓN%'
            OR UPPER(COALESCE(tipo_hecho, '')) LIKE '%ACCIDENTE%'
            OR UPPER(COALESCE(tipo_hecho, '')) LIKE '%VOLCADURA%'
            OR UPPER(COALESCE(tipo_hecho, '')) LIKE '%ATROPELL%'
            OR COALESCE(tipo_hecho, '') <> ''
        ";
    }

    private function resolveAlertType(?string $type, ?string $subtype): string
    {
        $text = strtoupper(trim((string) $type) . ' ' . trim((string) $subtype));

        if (
            str_contains($text, 'ROAD_CLOSED') ||
            str_contains($text, 'CLOSED') ||
            str_contains($text, 'BLOCK') ||
            str_contains($text, 'CLOSURE')
        ) {
            return 'cierre';
        }

        return 'choque';
    }

    private function riskLevel(float $score): array
    {
        if ($score >= 95) {
            return [
                'key' => 'critico',
                'label' => 'Critico',
                'color' => '#D50000',
                'accion' => 'Enviar apoyo vial inmediato',
            ];
        }

        if ($score >= 70) {
            return [
                'key' => 'alto',
                'label' => 'Alto',
                'color' => '#FF6F00',
                'accion' => 'Priorizar presencia vial',
            ];
        }

        if ($score >= 42) {
            return [
                'key' => 'vigilancia',
                'label' => 'Vigilancia',
                'color' => '#FBC02D',
                'accion' => 'Monitorear y preparar desplazamiento',
            ];
        }

        return [
            'key' => 'latente',
            'label' => 'Latente',
            'color' => '#2563EB',
            'accion' => 'Observacion preventiva',
        ];
    }

    private function chaosLevel(float $score): array
    {
        if ($score >= 60) {
            return [
                'key' => 'critico',
                'label' => 'Caos critico',
                'color' => '#B91C1C',
                'accion' => 'Despeje prioritario',
            ];
        }

        if ($score >= 32) {
            return [
                'key' => 'alto',
                'label' => 'Caos alto',
                'color' => '#EA580C',
                'accion' => 'Enviar unidad de apoyo',
            ];
        }

        return [
            'key' => 'medio',
            'label' => 'Caos activo',
            'color' => '#F59E0B',
            'accion' => 'Monitoreo activo',
        ];
    }

    private function cellFromLatLng(float $lat, float $lng, float $gridSize): array
    {
        return [
            'lat' => floor($lat / $gridSize) * $gridSize,
            'lng' => floor($lng / $gridSize) * $gridSize,
        ];
    }

    private function cellKey(float $lat, float $lng): string
    {
        return number_format($lat, 6, '.', '') . '|' . number_format($lng, 6, '.', '');
    }

    private function clampInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
