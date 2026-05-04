<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeritoHomeController extends Controller
{
    public function mapa(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $days = (int) $request->get('days', 30);
        $days = $days > 0 ? $days : 30;

        $gridSize = (float) $request->get('grid_size', 0.01);
        if ($gridSize <= 0) {
            $gridSize = 0.01;
        }

        $minScore = (int) $request->get('min_score', 6);
        $minScore = $minScore > 0 ? $minScore : 6;

        $wazeHours = (int) $request->get('waze_hours', 12);
        $wazeHours = $wazeHours > 0 ? $wazeHours : 12;

        $start = Carbon::now($tz)->subDays($days)->startOfDay();
        $end = Carbon::now($tz)->endOfDay();

        $hechos = DB::table('hechos')
            ->select([
                'id',
                DB::raw('folio_c5i as folio'),
                'tipo_hecho',
                'sector',
                'municipio',
                'lat',
                'lng',
                'fecha',
                'hora',
                'created_at',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('lat', '!=', 0)
            ->where('lng', '!=', 0)
            ->get();

        $cells = [];

        foreach ($hechos as $h) {
            $lat = (float) $h->lat;
            $lng = (float) $h->lng;

            $cellLat = floor($lat / $gridSize) * $gridSize;
            $cellLng = floor($lng / $gridSize) * $gridSize;
            $cellKey = number_format($cellLat, 6, '.', '') . '|' . number_format($cellLng, 6, '.', '');

            $createdAt = $h->created_at ? Carbon::parse($h->created_at, $tz) : null;
            $daysAgo = $createdAt ? max(0, $createdAt->diffInDays(Carbon::now($tz))) : $days;

            $weight = 1;
            if ($daysAgo <= 1) {
                $weight = 4;
            } elseif ($daysAgo <= 3) {
                $weight = 3;
            } elseif ($daysAgo <= 7) {
                $weight = 2;
            }

            if (!isset($cells[$cellKey])) {
                $cells[$cellKey] = [
                    'cell_key' => $cellKey,
                    'center_lat' => $cellLat + ($gridSize / 2),
                    'center_lng' => $cellLng + ($gridSize / 2),
                    'score' => 0,
                    'total_hechos' => 0,
                    'tipos' => [],
                    'sectores' => [],
                    'municipios' => [],
                    'last_event_at' => null,
                    'sample_hechos' => [],
                ];
            }

            $cells[$cellKey]['score'] += $weight;
            $cells[$cellKey]['total_hechos']++;

            if (!empty($h->tipo_hecho)) {
                $cells[$cellKey]['tipos'][$h->tipo_hecho] = ($cells[$cellKey]['tipos'][$h->tipo_hecho] ?? 0) + 1;
            }

            if (!empty($h->sector)) {
                $cells[$cellKey]['sectores'][$h->sector] = true;
            }

            if (!empty($h->municipio)) {
                $cells[$cellKey]['municipios'][$h->municipio] = true;
            }

            if ($h->created_at) {
                if (
                    is_null($cells[$cellKey]['last_event_at']) ||
                    Carbon::parse($h->created_at)->gt(Carbon::parse($cells[$cellKey]['last_event_at']))
                ) {
                    $cells[$cellKey]['last_event_at'] = $h->created_at;
                }
            }

            if (count($cells[$cellKey]['sample_hechos']) < 5) {
                $cells[$cellKey]['sample_hechos'][] = [
                    'id' => (int) $h->id,
                    'folio' => $h->folio,
                    'tipo_hecho' => $h->tipo_hecho,
                    'fecha' => $h->fecha,
                    'hora' => $h->hora,
                ];
            }
        }

        $riskZones = [];

        foreach ($cells as $cell) {
            if ((int) $cell['score'] < $minScore) {
                continue;
            }

            arsort($cell['tipos']);
            $topTipo = !empty($cell['tipos']) ? array_key_first($cell['tipos']) : null;

            $severity = $this->resolveSeverity((int) $cell['score'], (int) $cell['total_hechos']);

            if (!in_array($severity, ['alta', 'muy_alta'], true)) {
                continue;
            }

            $riskZones[] = [
                'type' => 'risk_zone',
                'cell_key' => $cell['cell_key'],
                'center_lat' => round((float) $cell['center_lat'], 6),
                'center_lng' => round((float) $cell['center_lng'], 6),
                'score' => (int) $cell['score'],
                'total_hechos' => (int) $cell['total_hechos'],
                'severity' => $severity,
                'radius_meters' => $severity === 'muy_alta' ? 450 : 300,
                'label' => $severity === 'muy_alta'
                    ? 'ZONA DE RIESGO MUY ALTA'
                    : 'ZONA DE RIESGO ALTA',
                'top_tipo_hecho' => $topTipo,
                'sectores' => array_values(array_keys($cell['sectores'])),
                'municipios' => array_values(array_keys($cell['municipios'])),
                'last_event_at' => $cell['last_event_at']
                    ? Carbon::parse($cell['last_event_at'])->timezone($tz)->toDateTimeString()
                    : null,
                'sample_hechos' => $cell['sample_hechos'],
                'style' => [
                    'kind' => 'circle',
                    'stroke_color' => $severity === 'muy_alta' ? '#7B1E1E' : '#C62828',
                    'fill_color' => $severity === 'muy_alta' ? '#FF5252' : '#FF8A80',
                    'stroke_width' => $severity === 'muy_alta' ? 3 : 2,
                    'z_index' => $severity === 'muy_alta' ? 30 : 20,
                ],
            ];
        }

        usort($riskZones, fn($a, $b) => $b['score'] <=> $a['score']);

        $wazeStart = Carbon::now($tz)->subHours($wazeHours);

        $wazeRows = DB::table('waze_alerts')
            ->select([
                'id',
                'uuid',
                'type',
                'subtype',
                'street',
                'city',
                'lat',
                'lng',
                'published_at',
                'created_at',
            ])
            ->where(function ($q) {
                $q->where('type', 'ACCIDENT')
                    ->orWhere('subtype', 'ACCIDENT')
                    ->orWhere('subtype', 'CRASH')
                    ->orWhere('type', 'like', '%ACCIDENT%')
                    ->orWhere('subtype', 'like', '%ACCIDENT%')
                    ->orWhere('subtype', 'like', '%CRASH%');
            })
            ->where(function ($q) use ($wazeStart) {
                $q->where('published_at', '>=', $wazeStart)
                    ->orWhere(function ($q2) use ($wazeStart) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $wazeStart);
                    });
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'))
            ->limit(100)
            ->get();

        $wazeAlerts = [];

        foreach ($wazeRows as $row) {
            $published = $row->published_at ?: $row->created_at;

            $wazeAlerts[] = [
                'type' => 'waze_accident',
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'lat' => round((float) $row->lat, 6),
                'lng' => round((float) $row->lng, 6),
                'street' => $row->street,
                'city' => $row->city,
                'title' => 'CHOQUE WAZE',
                'subtitle' => $row->street ?: ($row->city ?: 'Ubicación sin calle'),
                'published_at' => $published
                    ? Carbon::parse($published)->timezone($tz)->toDateTimeString()
                    : null,
                'style' => [
                    'kind' => 'marker',
                    'icon' => 'waze_accident',
                    'marker_color' => '#FFD600',
                    'border_color' => '#D50000',
                    'pulse' => true,
                    'z_index' => 100,
                ],
            ];
        }

        $center = $this->resolveCenter($riskZones, $wazeAlerts);

        return response()->json([
            'meta' => [
                'days' => $days,
                'grid_size' => $gridSize,
                'min_score' => $minScore,
                'waze_hours' => $wazeHours,
                'generated_at' => Carbon::now($tz)->toDateTimeString(),
                'timezone' => $tz,
            ],
            'map' => [
                'center' => $center,
                'zoom' => 12,
            ],
            'layers' => [
                'risk_zones' => $riskZones,
                'waze_alerts' => $wazeAlerts,
            ],
            'counts' => [
                'risk_zones' => count($riskZones),
                'waze_alerts' => count($wazeAlerts),
            ],
        ]);
    }

    public function filtros(Request $request)
    {
        return response()->json([
            'days_options' => [1, 3, 7, 15, 30, 60, 90],
            'waze_hours_options' => [1, 3, 6, 12, 24],
            'severity' => [
                ['value' => 'alta', 'label' => 'Alta'],
                ['value' => 'muy_alta', 'label' => 'Muy alta'],
            ],
            'default_values' => [
                'days' => 30,
                'grid_size' => 0.01,
                'min_score' => 6,
                'waze_hours' => 12,
            ],
        ]);
    }

    public function show($hecho)
    {
        $row = DB::table('hechos')
            ->select([
                'id',
                DB::raw('folio_c5i as folio'),
                'fecha',
                'hora',
                'tipo_hecho',
                'sector',
                'municipio',
                'lat',
                'lng',
                'created_at',
                'updated_at',
            ])
            ->where('id', $hecho)
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Hecho no encontrado.',
            ], 404);
        }

        $vehiculos = DB::table('hecho_vehiculo as hv')
            ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
            ->where('hv.hecho_id', $hecho)
            ->select([
                'v.id',
                'v.tipo',
                DB::raw('v.tipo as tipo_vehiculo'),
                'v.marca',
                'v.modelo',
                'v.color',
                'v.placas',
                'v.serie',
                'v.grua',
            ])
            ->get();

        $lesionados = DB::table('lesionados')
            ->where('hecho_id', $hecho)
            ->select([
                'id',
                'nombre',
                'edad',
                'sexo',
                'tipo_lesion',
                DB::raw('tipo_lesion as estado_salud'),
            ])
            ->get();

        return response()->json([
            'hecho' => [
                'id' => (int) $row->id,
                'folio' => $row->folio,
                'fecha' => $row->fecha,
                'hora' => $row->hora,
                'tipo_hecho' => $row->tipo_hecho,
                'sector' => $row->sector,
                'municipio' => $row->municipio,
                'lat' => $row->lat !== null ? (float) $row->lat : null,
                'lng' => $row->lng !== null ? (float) $row->lng : null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ],
            'vehiculos' => $vehiculos,
            'lesionados' => $lesionados,
        ]);
    }

    private function resolveSeverity(int $score, int $totalHechos): string
    {
        if ($score >= 12 || $totalHechos >= 7) {
            return 'muy_alta';
        }

        if ($score >= 6 || $totalHechos >= 4) {
            return 'alta';
        }

        return 'media';
    }

    private function resolveCenter(array $riskZones, array $wazeAlerts): array
    {
        if (!empty($wazeAlerts)) {
            return [
                'lat' => (float) $wazeAlerts[0]['lat'],
                'lng' => (float) $wazeAlerts[0]['lng'],
            ];
        }

        if (!empty($riskZones)) {
            return [
                'lat' => (float) $riskZones[0]['center_lat'],
                'lng' => (float) $riskZones[0]['center_lng'],
            ];
        }

        return [
            'lat' => 19.705950,
            'lng' => -101.194983,
        ];
    }
}
