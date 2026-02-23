<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function homePerito(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        // Si quieres mantener el rol Perito, ok. (además del can:ver home perito de la ruta)
        if (method_exists($user, 'hasRole')) {
            if (!$user->hasRole('Perito')) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        }

        // Reutiliza la MISMA lógica de riesgo() SIN crear otra ruta
        $riesgoResponse = $this->riesgo($request);

        // riesgo() ya regresa Response JSON. Lo convertimos a array.
        $riesgoData = [];
        if (method_exists($riesgoResponse, 'getData')) {
            $riesgoData = (array) $riesgoResponse->getData(true);
        }

        // Si riesgo() devolvió error (422), lo regresamos tal cual (para que Flutter lo vea claro)
        if (isset($riesgoData['error'])) {
            return $riesgoResponse;
        }

        // Mezclamos home + payload de riesgo en UNA sola respuesta
        return response()->json(array_merge([
            'ok'   => true,
            'home' => 'perito',
        ], $riesgoData));
    }

    public function accidentesHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        $total = DB::table('hechos')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $rows = DB::table('hechos')
            ->selectRaw('HOUR(CONVERT_TZ(created_at, "+00:00", ?)) as hour, COUNT(*) as count', [$tz])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->hour] = (int) $r->count;
        }

        $byHour = [];
        for ($h = 0; $h < 24; $h++) {
            $byHour[] = ['hour' => $h, 'count' => $map[$h] ?? 0];
        }

        return response()->json([
            'date'    => $start->format('Y-m-d'),
            'total'   => (int) $total,
            'by_hour' => $byHour,
        ]);
    }

    public function gruasHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        $rows = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'hv.hecho_id', '=', 'h.id')
            ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
            ->whereBetween('h.created_at', [$start, $end])
            ->whereNotNull('v.grua')
            ->where('v.grua', '!=', '')
            ->selectRaw('v.grua as name, COUNT(*) as count')
            ->groupBy('v.grua')
            ->orderByDesc('count')
            ->get();

        $total = 0;
        $byGrua = [];

        foreach ($rows as $r) {
            $c = (int) $r->count;
            $total += $c;
            $byGrua[] = [
                'name'  => (string) $r->name,
                'count' => $c,
            ];
        }

        return response()->json([
            'date'    => $start->format('Y-m-d'),
            'total'   => (int) $total,
            'by_grua' => $byGrua,
        ]);
    }

    public function riesgo(Request $request)
    {
        $precision = (int) $request->query('precision', 3);
        if (!in_array($precision, [2, 3, 4], true)) $precision = 3;

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $wazeHoras = (int) $request->query('waze_horas', 24);
        if ($wazeHoras <= 0) $wazeHoras = 24;
        if ($wazeHoras > 168) $wazeHoras = 168;

        if (!$desde || !$hasta) {
            return response()->json(['error' => 'Fechas inválidas'], 422);
        }

        $tz = 'America/Mexico_City';

        $desdeDT = Carbon::parse($desde, $tz)->startOfDay()->toDateTimeString();
        $hastaDT = Carbon::parse($hasta, $tz)->endOfDay()->toDateTimeString();
        $wazeDesde = Carbon::now($tz)->subHours($wazeHoras)->toDateTimeString();

        $hechosCells = DB::table('hechos')
            ->selectRaw("
                CONCAT(ROUND(lat, ?), ',', ROUND(lng, ?)) AS cell,
                ROUND(lat, ?) AS lat,
                ROUND(lng, ?) AS lng,
                COUNT(*) AS total
            ", [$precision, $precision, $precision, $precision])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween(
                DB::raw(\"STR_TO_DATE(CONCAT(fecha,' ',hora), '%Y-%m-%d %H:%i:%s')\"),
                [$desdeDT, $hastaDT]
            )
            ->groupBy('cell', 'lat', 'lng')
            ->get();

        $wazePoints = DB::table('waze_alerts')
            ->select('lat', 'lng', 'type', 'street', 'street_norm', 'published_at')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('published_at', '>=', $wazeDesde)
            ->get();

        $matches = DB::table('waze_hecho_matches')
            ->selectRaw("
                cell_key AS cell,
                (SUBSTRING_INDEX(cell_key, ',', 1) + 0.0) AS lat,
                (SUBSTRING_INDEX(cell_key, ',', -1) + 0.0) AS lng,
                hecho_id,
                waze_accident_at,
                waze_first_jam_at,
                min_accident_to_hecho,
                min_hecho_to_jam
            ")
            ->orderByDesc('hecho_at')
            ->limit(200)
            ->get();

        $riesgo = DB::select("
            SELECT
                h.cell,
                h.lat,
                h.lng,
                h.total AS hechos_hist,
                COALESCE(j.jams,0) AS jams_now,
                COALESCE(a.accidents,0) AS accidents_now,
                ROUND((COALESCE(j.jams,0) * 2)
                      + COALESCE(a.accidents,0)
                      + (h.total / 10), 2) AS score
            FROM
            (
                SELECT
                    CONCAT(ROUND(lat, {$precision}), ',', ROUND(lng, {$precision})) AS cell,
                    ROUND(lat, {$precision}) AS lat,
                    ROUND(lng, {$precision}) AS lng,
                    COUNT(*) AS total
                FROM hechos
                WHERE lat IS NOT NULL AND lng IS NOT NULL
                GROUP BY cell, lat, lng
            ) h
            LEFT JOIN
            (
                SELECT
                    CONCAT(ROUND(lat, {$precision}), ',', ROUND(lng, {$precision})) AS cell,
                    COUNT(*) AS jams
                FROM waze_alerts
                WHERE type='JAM'
                  AND lat IS NOT NULL AND lng IS NOT NULL
                  AND published_at >= ?
                GROUP BY cell
            ) j ON j.cell = h.cell
            LEFT JOIN
            (
                SELECT
                    CONCAT(ROUND(lat, {$precision}), ',', ROUND(lng, {$precision})) AS cell,
                    COUNT(*) AS accidents
                FROM waze_alerts
                WHERE type='ACCIDENT'
                  AND lat IS NOT NULL AND lng IS NOT NULL
                  AND published_at >= ?
                GROUP BY cell
            ) a ON a.cell = h.cell
            HAVING score > 0
            ORDER BY score DESC
            LIMIT 50
        ", [$wazeDesde, $wazeDesde]);

        $kpis = [
            'hechos'    => (int) $hechosCells->sum('total'),
            'jams'      => (int) $wazePoints->where('type', 'JAM')->count(),
            'accidents' => (int) $wazePoints->where('type', 'ACCIDENT')->count(),
            'matches'   => (int) $matches->count(),
            'top'       => isset($riesgo[0]) ? (float) $riesgo[0]->score : 0,
        ];

        return response()->json([
            'kpis'         => $kpis,
            'hechos_cells' => $hechosCells,
            'waze_points'  => $wazePoints,
            'matches'      => $matches,
            'riesgo_cells' => $riesgo,
        ]);
    }
}
