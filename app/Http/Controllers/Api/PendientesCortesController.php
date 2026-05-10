<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendientesCorte;
use App\Services\PendientesCortesService;
use App\Support\HechoAccess;
use Illuminate\Http\Request;

class PendientesCortesController extends Controller
{
    public function index(Request $request, PendientesCortesService $cortesService)
    {
        return $this->indexPorUnidad($request, $cortesService, PendientesCortesService::UNIDAD_SINIESTROS_ID);
    }

    public function show(Request $request, PendientesCorte $corte, PendientesCortesService $cortesService)
    {
        return $this->showPorUnidad($request, $corte, $cortesService, PendientesCortesService::UNIDAD_SINIESTROS_ID);
    }

    public function indexDelegaciones(Request $request, PendientesCortesService $cortesService)
    {
        return $this->indexPorUnidad($request, $cortesService, PendientesCortesService::UNIDAD_DELEGACIONES_ID);
    }

    public function showDelegaciones(Request $request, PendientesCorte $corte, PendientesCortesService $cortesService)
    {
        return $this->showPorUnidad($request, $corte, $cortesService, PendientesCortesService::UNIDAD_DELEGACIONES_ID);
    }

    private function indexPorUnidad(Request $request, PendientesCortesService $cortesService, int $unidadId)
    {
        if (!HechoAccess::canUseHechosModule($request->user())) {
            return response()->json([
                'message' => 'No tienes permiso para consultar hechos desde esta unidad.',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 30);
        if ($perPage <= 0) $perPage = 30;
        if ($perPage > 100) $perPage = 100;

        $cortes = $cortesService->paginateCortes($request->user(), $unidadId, $perPage, true);

        return response()->json([
            'data' => $cortes->items(),
            'meta' => [
                'current_page' => $cortes->currentPage(),
                'per_page' => $cortes->perPage(),
                'total' => $cortes->total(),
                'last_page' => $cortes->lastPage(),
            ],
        ]);
    }

    private function showPorUnidad(Request $request, PendientesCorte $corte, PendientesCortesService $cortesService, int $unidadId)
    {
        if (!HechoAccess::canUseHechosModule($request->user())) {
            return response()->json([
                'message' => 'No tienes permiso para consultar hechos desde esta unidad.',
            ], 403);
        }

        $detalle = $cortesService->detalle($corte, $request->user(), $unidadId, true);

        if (!($detalle['visible'] ?? false)) {
            return response()->json([
                'message' => 'Corte no encontrado para la unidad solicitada.',
            ], 404);
        }

        return response()->json([
            'corte' => $corte,
            'prev' => $detalle['prev'],
            'totales' => $detalle['totales'],
            'resueltos' => $detalle['resueltos'],
            'turnados' => $detalle['turnados'],
            'siguen' => $detalle['siguen'],
            'otros' => $detalle['otros'],
            'nuevos' => $detalle['nuevos'],
        ]);
    }
}
