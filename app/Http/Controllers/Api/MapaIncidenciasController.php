<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaIncidenciasController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'ok' => true,
            'message' => 'Mapa incidencias API',
        ]);
    }

    public function data(Request $request)
    {
        $actor = $request->user();

        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $tipo  = $request->get('tipo_hecho');

        $precision = (int) $request->get('precision', 3);
        if ($precision < 2) $precision = 2;
        if ($precision > 5) $precision = 5;

        $q = DB::table('hechos')
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if ($desde) $q->whereDate('fecha', '>=', $desde);
        if ($hasta) $q->whereDate('fecha', '<=', $hasta);
        if ($tipo)  $q->where('tipo_hecho', $tipo);

        $rows = $q->selectRaw("
                ROUND(lat, ?) AS lat_r,
                ROUND(lng, ?) AS lng_r,
                COUNT(*) AS total,
                MIN(fecha) AS fecha_min,
                MAX(fecha) AS fecha_max
            ", [$precision, $precision])
            ->groupBy('lat_r', 'lng_r')
            ->orderByDesc('total')
            ->limit(3000)
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'lat'       => (float) $r->lat_r,
                    'lng'       => (float) $r->lng_r,
                    'total'     => (int) $r->total,
                    'fecha_min' => $r->fecha_min,
                    'fecha_max' => $r->fecha_max,
                ];
            })->values(),
        ]);
    }
}
