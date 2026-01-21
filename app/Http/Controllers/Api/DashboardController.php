<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/accidentes-hoy
     * {
     *   "date":"YYYY-MM-DD",
     *   "total":12,
     *   "by_hour":[{"hour":0,"count":0},...{"hour":23,"count":1}]
     * }
     */
    public function accidentesHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        $total = DB::table('hechos')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Agrupa por hora usando la zona horaria de la app
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

    /**
     * GET /api/dashboard/gruas-hoy
     * {
     *   "date":"YYYY-MM-DD",
     *   "total":5,
     *   "by_grua":[{"name":"DANNYS","count":2},{"name":"MUÑOZ","count":3}]
     * }
     *
     * Fuente real:
     * hechos -> hecho_vehiculo -> vehiculos.grua (string)
     */
    public function gruasHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        // Cuenta por nombre de grúa (vehiculos.grua)
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
}
