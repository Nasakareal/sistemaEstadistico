<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DelegacionesHomeController extends Controller
{
    private const TZ = 'America/Mexico_City';
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function mapa(Request $request)
    {
        $user = $request->user();
        if (!$this->puedeVerHome($user)) {
            return response()->json([
                'message' => 'No tienes acceso al home de delegaciones.',
            ], 403);
        }

        $days = $this->clampInt($request->query('days', 30), 1, 90, 30);
        $limit = $this->clampInt($request->query('limit', 120), 20, 350, 120);
        $wazeMinutes = $this->clampInt($request->query('waze_minutes', 30), 1, 30, 30);

        $tz = self::TZ;
        $now = Carbon::now($tz);
        $desde = $now->copy()->subDays($days)->toDateString();
        $hasta = $now->copy()->toDateString();
        $wazeDesde = $now->copy()->subMinutes($wazeMinutes)->toDateTimeString();

        $waze = $this->wazePoints($wazeDesde, $limit);
        $riesgo = $this->riskZones($desde, $hasta, $waze, $limit);
        $center = $this->resolveCenter($waze, $riesgo);

        return response()->json([
            'meta' => [
                'days' => $days,
                'waze_minutes' => $wazeMinutes,
                'generated_at' => $now->toDateTimeString(),
                'timezone' => $tz,
            ],
            'map' => [
                'center' => $center,
                'zoom' => 12.5,
            ],
            'layers' => [
                'risk_zones' => $riesgo,
                'waze_alerts' => $waze,
            ],
            'counts' => [
                'risk_zones' => count($riesgo),
                'waze_alerts' => count($waze),
                'choques' => count(array_filter($waze, fn ($i) => $i['type'] === 'waze_accident')),
                'cierres' => count(array_filter($waze, fn ($i) => $i['type'] === 'waze_road_closed')),
                'trafico' => count(array_filter($waze, fn ($i) => $i['type'] === 'waze_jam')),
            ],
        ]);
    }

    public function filtros()
    {
        return response()->json([
            'default_values' => [
                'days' => 30,
                'waze_minutes' => 30,
                'limit' => 120,
            ],
        ]);
    }

    private function puedeVerHome($user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperadmin') && $user->isSuperadmin()) {
            return true;
        }

        $unidadId = (int) ($user->unidad_id ?? 0);
        if ($unidadId !== self::UNIDAD_DELEGACIONES_ID) {
            return false;
        }

        return $user->getRoleNames()
            ->map(fn ($role) => $this->normalizeRoleName($role))
            ->contains('POLICIA');
    }

    private function normalizeRoleName(?string $role): string
    {
        $text = trim((string) $role);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = strtoupper($text);
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }

    private function wazePoints(string $wazeDesde, int $limit): array
    {
        $rows = DB::table('waze_alerts')
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
                'created_at',
            ])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($q) use ($wazeDesde) {
                $q->where('published_at', '>=', $wazeDesde)
                    ->orWhere(function ($q2) use ($wazeDesde) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $wazeDesde);
                    });
            })
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'))
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $alertType = $this->resolveAlertType($row->type, $row->subtype);
            $published = $row->published_at ?: $row->created_at;

            $items[] = [
                'type' => $alertType,
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'waze_id' => $row->waze_id,
                'waze_type' => $row->type,
                'waze_subtype' => $row->subtype,
                'country' => $row->country,
                'lat' => round((float) $row->lat, 6),
                'lng' => round((float) $row->lng, 6),
                'street' => $row->street,
                'street_norm' => $row->street_norm,
                'city' => $row->city,
                'cell_key' => $row->cell_key,
                'pub_millis' => $row->pub_millis,
                'title' => $this->alertTitle($alertType),
                'subtitle' => $row->street ?: ($row->city ?: 'Ubicacion sin calle'),
                'published_at' => $published
                    ? Carbon::parse($published)->timezone(self::TZ)->toDateTimeString()
                    : null,
            ];
        }

        return $items;
    }

    private function riskZones(string $desde, string $hasta, array $waze, int $limit): array
    {
        $precision = 3;

        $rows = DB::table('hechos')
            ->selectRaw("
                CONCAT(ROUND(lat, ?), ',', ROUND(lng, ?)) AS cell,
                ROUND(AVG(lat), 6) AS lat,
                ROUND(AVG(lng), 6) AS lng,
                COUNT(*) AS total,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'TURNADO' THEN 1 ELSE 0 END) AS turnados,
                SUM(CASE WHEN UPPER(COALESCE(situacion, '')) = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN COALESCE(es_relevante, 0) = 1 THEN 1 ELSE 0 END) AS relevantes,
                MAX(fecha) AS fecha_max
            ", [$precision, $precision])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('cell')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->keyBy('cell')
            ->map(function ($row) {
                return [
                    'cell' => (string) $row->cell,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'hechos_hist' => (int) $row->total,
                    'turnados' => (int) $row->turnados,
                    'pendientes' => (int) $row->pendientes,
                    'relevantes' => (int) $row->relevantes,
                    'waze_total' => 0,
                    'last_event_at' => $row->fecha_max,
                ];
            })
            ->all();

        foreach ($waze as $item) {
            $cell = round((float) $item['lat'], $precision) . ',' . round((float) $item['lng'], $precision);
            if (!isset($rows[$cell])) {
                $rows[$cell] = [
                    'cell' => $cell,
                    'lat' => (float) $item['lat'],
                    'lng' => (float) $item['lng'],
                    'hechos_hist' => 0,
                    'turnados' => 0,
                    'pendientes' => 0,
                    'relevantes' => 0,
                    'waze_total' => 0,
                    'last_event_at' => $item['published_at'],
                ];
            }

            $rows[$cell]['waze_total']++;
            $rows[$cell]['last_event_at'] = $item['published_at'] ?: $rows[$cell]['last_event_at'];
        }

        $zones = array_map(function (array $cell) {
            $score = $this->scoreCell($cell);
            $severity = $this->severity($score);
            $color = $this->color($severity);

            return [
                'type' => 'risk_zone',
                'cell_key' => $cell['cell'],
                'center_lat' => round((float) $cell['lat'], 6),
                'center_lng' => round((float) $cell['lng'], 6),
                'score' => $score,
                'total_hechos' => (int) $cell['hechos_hist'],
                'waze_total' => (int) $cell['waze_total'],
                'severity' => $severity,
                'radius_meters' => min(760, max(240, 180 + ($score * 5))),
                'label' => $this->label($severity),
                'accion' => $this->action($severity),
                'last_event_at' => $cell['last_event_at'],
                'style' => [
                    'stroke_color' => $color,
                    'fill_color' => $color,
                ],
            ];
        }, array_values($rows));

        usort($zones, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($zones, 0, $limit);
    }

    private function scoreCell(array $cell): float
    {
        $score = ((int) $cell['hechos_hist'] * 5)
            + ((int) $cell['turnados'] * 4)
            + ((int) $cell['pendientes'] * 1.5)
            + ((int) $cell['relevantes'] * 3)
            + ((int) $cell['waze_total'] * 12);

        return round(min(100, $score), 1);
    }

    private function resolveAlertType(?string $type, ?string $subtype): string
    {
        $raw = strtoupper(trim((string) $type . ' ' . (string) $subtype));

        if ($this->contains($raw, 'ROAD_CLOSED') || $this->contains($raw, 'CLOSED')) {
            return 'waze_road_closed';
        }

        if ($this->contains($raw, 'JAM') || $this->contains($raw, 'TRAFFIC')) {
            return 'waze_jam';
        }

        if ($this->contains($raw, 'ACCIDENT') || $this->contains($raw, 'CRASH')) {
            return 'waze_accident';
        }

        return 'waze_alert';
    }

    private function contains(string $text, string $needle): bool
    {
        return $needle !== '' && strpos($text, $needle) !== false;
    }

    private function alertTitle(string $type): string
    {
        switch ($type) {
            case 'waze_road_closed':
                return 'CIERRE WAZE';
            case 'waze_jam':
                return 'TRAFICO WAZE';
            case 'waze_accident':
                return 'CHOQUE WAZE';
            default:
                return 'INCIDENCIA WAZE';
        }
    }

    private function severity(float $score): string
    {
        if ($score >= 70) {
            return 'critico';
        }

        if ($score >= 35) {
            return 'alto';
        }

        return 'vigilancia';
    }

    private function color(string $severity): string
    {
        switch ($severity) {
            case 'critico':
                return '#E11D48';
            case 'alto':
                return '#F97316';
            default:
                return '#0EA5E9';
        }
    }

    private function label(string $severity): string
    {
        switch ($severity) {
            case 'critico':
                return 'Riesgo critico';
            case 'alto':
                return 'Riesgo alto';
            default:
                return 'Vigilancia';
        }
    }

    private function action(string $severity): string
    {
        switch ($severity) {
            case 'critico':
                return 'Atencion prioritaria';
            case 'alto':
                return 'Monitoreo preventivo';
            default:
                return 'Observacion';
        }
    }

    private function resolveCenter(array $waze, array $riesgo): array
    {
        if (!empty($waze)) {
            return [
                'lat' => (float) $waze[0]['lat'],
                'lng' => (float) $waze[0]['lng'],
            ];
        }

        if (!empty($riesgo)) {
            return [
                'lat' => (float) $riesgo[0]['center_lat'],
                'lng' => (float) $riesgo[0]['center_lng'],
            ];
        }

        return [
            'lat' => 19.705950,
            'lng' => -101.194983,
        ];
    }

    private function clampInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
