<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaIncidenciasController extends Controller
{
    public function index()
    {
        return view('mapa.incidencias');
    }

    private function roleIs(User $u, string $name): bool
    {
        return method_exists($u,'hasRole') && ($u->hasRole($name) || $u->hasRole(mb_strtolower($name)) || $u->hasRole(mb_strtoupper($name)));
    }

    public function data(Request $request)
    {
        $actor = $request->user();
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $tipo  = $request->get('tipo_hecho');

        $precision = (int)($request->get('precision', 3));
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
            ->groupBy('lat_r','lng_r')
            ->orderByDesc('total')
            ->limit(3000)
            ->get();

        return response()->json([
            'data' => $rows->map(function($r){
                return [
                    'lat'       => (float)$r->lat_r,
                    'lng'       => (float)$r->lng_r,
                    'total'     => (int)$r->total,
                    'fecha_min' => $r->fecha_min,
                    'fecha_max' => $r->fecha_max,
                ];
            })
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

        $rows = DB::table('hechos')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereRaw('ROUND(lat, ?) = ?', [$precision, $lat])
            ->whereRaw('ROUND(lng, ?) = ?', [$precision, $lng]);

        if ($desde) {
            $rows->whereDate('fecha', '>=', $desde);
        }
        if ($hasta) {
            $rows->whereDate('fecha', '<=', $hasta);
        }
        if ($tipo) {
            $rows->where('tipo_hecho', $tipo);
        }

        $hechos = $rows
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(150)
            ->get([
                'id',
                'folio_c5i',
                'fecha',
                'hora',
                'tipo_hecho',
                'situacion',
                'calle',
                'colonia',
                'municipio',
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
                    'calle' => $row->calle,
                    'colonia' => $row->colonia,
                    'municipio' => $row->municipio,
                    'lat' => $row->lat !== null ? (float) $row->lat : null,
                    'lng' => $row->lng !== null ? (float) $row->lng : null,
                    'show_url' => route('hechos.show', ['hecho' => $row->id]),
                ];
            })->values(),
        ]);
    }
}
