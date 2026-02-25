<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class RiesgoWebController extends Controller
{
    public function index()
    {
        $data = DB::table('hechos')
            ->select(
                'colonia',
                DB::raw('COUNT(*) as hechos'),
                DB::raw('SUM(COALESCE(vehiculos_mp,0)) as vehiculos'),
                DB::raw('SUM(COALESCE(personas_mp,0)) as lesionados'),
                DB::raw('
                    COUNT(*) 
                    + (3 * SUM(COALESCE(personas_mp,0)))
                    + (0.5 * SUM(COALESCE(vehiculos_mp,0)))
                    as score
                ')
            )
            ->whereNotNull('colonia')
            ->groupBy('colonia')
            ->orderByDesc('score')
            ->limit(20)
            ->get();

        return view('riesgo.demo', compact('data'));
    }
}
