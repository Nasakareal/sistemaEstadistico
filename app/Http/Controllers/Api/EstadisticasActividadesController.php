<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class EstadisticasActividadesController extends \App\Http\Controllers\EstadisticasActividadesController
{
    public function index(Request $request)
    {
        return response()->json([
            'ok' => true,
            'module' => 'estadisticas_actividades',
            'message' => 'API de estadisticas de actividades disponible.',
        ]);
    }
}
