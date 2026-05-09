<?php

namespace App\Http\Controllers;

use App\Models\PendientesCorte;
use App\Services\PendientesCortesService;
use Illuminate\Http\Request;

class PendientesCortesController extends Controller
{
    public function index(Request $request, PendientesCortesService $cortesService)
    {
        $cortes = $cortesService->paginateCortes(
            $request->user(),
            PendientesCortesService::UNIDAD_SINIESTROS_ID
        );
        $titulo = 'Cortes de Pendientes - Siniestros';
        $routeShow = 'hechos.pendientes.cortes.show';

        return view('hechos.pendientes_cortes.index', compact('cortes', 'titulo', 'routeShow'));
    }

    public function show(Request $request, PendientesCorte $corte, PendientesCortesService $cortesService)
    {
        $detalle = $cortesService->detalle($corte, $request->user(), PendientesCortesService::UNIDAD_SINIESTROS_ID);

        if (!($detalle['visible'] ?? false)) {
            abort(404);
        }

        $titulo = 'Detalle del Corte de Pendientes - Siniestros';
        $routeIndex = 'hechos.pendientes.cortes.index';
        $routeShow = 'hechos.pendientes.cortes.show';

        return view('hechos.pendientes_cortes.show', array_merge($detalle, compact(
            'corte',
            'titulo',
            'routeIndex',
            'routeShow'
        )));
    }

    public function indexDelegaciones(Request $request, PendientesCortesService $cortesService)
    {
        $cortes = $cortesService->paginateCortes(
            $request->user(),
            PendientesCortesService::UNIDAD_DELEGACIONES_ID
        );
        $titulo = 'Cortes de Pendientes - Delegaciones';
        $routeShow = 'hechos.pendientes.delegaciones.cortes.show';

        return view('hechos.pendientes_cortes.index', compact('cortes', 'titulo', 'routeShow'));
    }

    public function showDelegaciones(Request $request, PendientesCorte $corte, PendientesCortesService $cortesService)
    {
        $detalle = $cortesService->detalle($corte, $request->user(), PendientesCortesService::UNIDAD_DELEGACIONES_ID);

        if (!($detalle['visible'] ?? false)) {
            abort(404);
        }

        $titulo = 'Detalle del Corte de Pendientes - Delegaciones';
        $routeIndex = 'hechos.pendientes.delegaciones.cortes.index';
        $routeShow = 'hechos.pendientes.delegaciones.cortes.show';

        return view('hechos.pendientes_cortes.show', array_merge($detalle, compact(
            'corte',
            'titulo',
            'routeIndex',
            'routeShow'
        )));
    }
}
