<?php

namespace App\Http\Controllers;

use App\Services\AseguramientosResumenService;
use Illuminate\Http\Request;

class EstadisticasAseguramientosController extends Controller
{
    private $resumenService;

    public function __construct(AseguramientosResumenService $resumenService)
    {
        $this->resumenService = $resumenService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->autorizar($request);

        $catalogos = $this->resumenService->catalogos($request->user());

        return view('estadisticas_aseguramientos.index', compact('catalogos'));
    }

    public function resumen(Request $request)
    {
        $this->autorizar($request);

        return response()->json(
            $this->resumenService->generar($request->query(), $request->user())
        );
    }

    public function catalogos(Request $request)
    {
        $this->autorizar($request);

        return response()->json($this->resumenService->catalogos($request->user()));
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
