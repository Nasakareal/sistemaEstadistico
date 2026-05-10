<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Delegaciones\DelegacionesExcelRevisionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DelegacionesExcelRevisionController extends Controller
{
    public function show(Request $request, DelegacionesExcelRevisionService $service)
    {
        $usuario = $request->user();

        if (
            !$usuario
            || (
                !$usuario->isSuperadmin()
                && !$usuario->perteneceAUnidad('delegaciones')
                && (int) ($usuario->unidad_id ?? 0) !== 3
            )
        ) {
            return response()->json([
                'message' => 'No tienes acceso a la revisión del Excel de delegaciones.',
            ], 403);
        }

        $tz = 'America/Mexico_City';
        $fecha = trim((string) $request->query('fecha', Carbon::now($tz)->format('Y-m-d')));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return response()->json([
                'message' => 'La fecha debe venir en formato YYYY-MM-DD.',
            ], 422);
        }

        try {
            $fecha = Carbon::parse($fecha, $tz)->format('Y-m-d');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Fecha inválida.',
            ], 422);
        }

        return response()->json($service->resumen($fecha));
    }
}
