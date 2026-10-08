<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AseguramientosResumenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadisticasAseguramientosController extends Controller
{
    private AseguramientosResumenService $resumenService;

    public function __construct(AseguramientosResumenService $resumenService)
    {
        $this->resumenService = $resumenService;
    }

    public function resumen(Request $request): JsonResponse
    {
        $this->autorizar($request);

        return response()->json(
            $this->resumenService->generar($request->query(), $request->user())
        );
    }

    public function catalogos(Request $request): JsonResponse
    {
        $this->autorizar($request);

        return response()->json(
            $this->resumenService->catalogos($request->user())
        );
    }

    private function autorizar(Request $request): void
    {
        $usuario = $request->user();

        abort_unless($usuario, 403);
        abort_unless(
            $usuario->hasRole('Superadmin')
            || $usuario->can('menu-estadisticas-generales')
            || $usuario->can('ver estadisticas')
            || $usuario->can('ver estadisticas globales')
            || $usuario->can('ver estadisticas actividades')
            || $usuario->can('ver estadisticas carreteras'),
            403
        );
    }
}
