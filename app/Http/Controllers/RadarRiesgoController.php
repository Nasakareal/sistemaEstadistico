<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RadarRiesgoController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $ultimos14 = $hoy->copy()->subDays(14);
        $previos14 = $hoy->copy()->subDays(28);

        $data = DB::table('hechos')
            ->select(
                'colonia',
                DB::raw("SUM(CASE WHEN fecha >= '$ultimos14' THEN 1 ELSE 0 END) as hechos_ultimos14"),
                DB::raw("SUM(CASE WHEN fecha < '$ultimos14' AND fecha >= '$previos14' THEN 1 ELSE 0 END) as hechos_previos14"),
                DB::raw("SUM(CASE WHEN fecha >= '$ultimos14' THEN COALESCE(personas_mp,0) ELSE 0 END) as lesionados_ultimos14")
            )
            ->whereNotNull('colonia')
            ->groupBy('colonia')
            ->get()
            ->map(function ($row) {
                $prev = $row->hechos_previos14 ?: 1;
                $crecimiento = (($row->hechos_ultimos14 - $prev) / $prev) * 100;
                $score = ($row->hechos_ultimos14 * 1.5) + ($row->lesionados_ultimos14 * 4);

                if ($crecimiento > 50) {
                    $semaforo = 'ROJO';
                } elseif ($crecimiento > -10) {
                    $semaforo = 'AMARILLO';
                } else {
                    $semaforo = 'VERDE';
                }

                $row->crecimiento = round($crecimiento,2);
                $row->score = round($score,2);
                $row->semaforo = $semaforo;

                return $row;
            })
            ->sortByDesc('score')
            ->take(20);

        return view('riesgo.radar', compact('data'));
    }
}
