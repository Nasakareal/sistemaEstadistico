<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
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
}
