<?php

namespace App\Http\Controllers;

use App\Models\HechoEliminado;
use App\Services\HechoEliminadoService;
use Illuminate\Http\Request;

class HechosEliminadosController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizar($request);

        $archivos = HechoEliminado::query()
            ->with(['vehiculos', 'eliminadoPor', 'restauradoPor'])
            ->latest('eliminado_at')
            ->paginate(20);

        return view('hechos.eliminados', compact('archivos'));
    }

    public function restore(
        Request $request,
        HechoEliminado $hechoEliminado,
        HechoEliminadoService $service
    ) {
        $this->autorizar($request);

        try {
            $hecho = $service->restaurar($hechoEliminado, optional($request->user())->id);

            return redirect()
                ->route('hechos.show', $hecho)
                ->with('success', 'Hecho restaurado exitosamente con sus vehículos y datos relacionados.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('hechos.eliminados.index')
                ->with('error', $e->getMessage());
        }
    }

    private function autorizar(Request $request): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario && $usuario->hasRole('Superadmin'),
            403
        );
    }
}
