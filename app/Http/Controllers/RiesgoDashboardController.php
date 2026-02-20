<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiesgoDashboardController extends Controller
{
    public function index()
    {
        return view('waze.riesgo.index');
    }

    public function data(Request $request)
    {
        $precision = (int) $request->query('precision', 3);
        if (!in_array($precision, [2,3,4])) $precision = 3;

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $ventana = (int) $request->query('ventana', 60);
        $wazeHoras = (int) $request->query('waze_horas', 24);

        if (!$desde || !$hasta) {
            return response()->json(['error' => 'Fechas inválidas'], 422);
        }

        $tz = 'America/Mexico_City';

        $desdeDT = Carbon::parse($desde, $tz)->startOfDay();
        $hastaDT = Carbon::parse($hasta, $tz)->endOfDay();
        $wazeDesde = Carbon::now($tz)->subHours($wazeHoras);

        /*
        |--------------------------------------------------------------------------
        | 1) HECHOS agrupados por celda
        |--------------------------------------------------------------------------
        */
        $hechosCells = DB::table('hechos')
            ->selectRaw("
                CONCAT(ROUND(lat, ?), ',', ROUND(lng, ?)) AS cell,
                ROUND(lat, ?) AS lat,
                ROUND(lng, ?) AS lng,
                COUNT(*) AS total
            ", [$precision,$precision,$precision,$precision])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween(DB::raw("TIMESTAMP(fecha, hora)"), [$desdeDT, $hastaDT])
            ->groupBy('cell','lat','lng')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2) Waze puntos recientes
        |--------------------------------------------------------------------------
        */
        $wazePoints = DB::table('waze_alerts')
            ->select('lat','lng','type','street','street_norm','published_at')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('published_at','>=',$wazeDesde)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3) Matches (choque → tráfico)
        |--------------------------------------------------------------------------
        */
        $matches = DB::table('waze_hecho_matches')
            ->selectRaw("
                cell_key AS cell,
                SUBSTRING_INDEX(cell_key, ',', 1) AS lat,
                SUBSTRING_INDEX(cell_key, ',', -1) AS lng,
                folio_c5i,
                waze_accident_at,
                waze_first_jam_at,
                min_accident_to_hecho,
                min_hecho_to_jam
            ")
            ->orderByDesc('hecho_at')
            ->limit(100)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4) Zonas de riesgo (score dinámico)
        |--------------------------------------------------------------------------
        | score = (jams * 2) + accidents + (hechos_hist / 10)
        */
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
                      + (h.total / 10),2) AS score
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

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */
        $kpis = [
            'hechos'    => $hechosCells->sum('total'),
            'jams'      => $wazePoints->where('type','JAM')->count(),
            'accidents' => $wazePoints->where('type','ACCIDENT')->count(),
            'matches'   => $matches->count(),
            'top'       => isset($riesgo[0]) ? $riesgo[0]->score : 0
        ];

        return response()->json([
            'kpis'         => $kpis,
            'hechos_cells' => $hechosCells,
            'waze_points'  => $wazePoints,
            'matches'      => $matches,
            'riesgo_cells' => $riesgo
        ]);
    }
}
