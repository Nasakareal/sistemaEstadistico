<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use App\Support\HechoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PendientesCortesController extends Controller
{
    public function index(Request $request)
    {
        if (!HechoAccess::canUseHechosModule($request->user())) {
            return response()->json([
                'message' => 'No tienes permiso para consultar hechos desde esta unidad.',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 30);
        if ($perPage <= 0) $perPage = 30;
        if ($perPage > 100) $perPage = 100;

        $cortes = PendientesCorte::orderByDesc('corte_fecha')
            ->paginate($perPage);

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

    public function show(Request $request, PendientesCorte $corte)
    {
        if (!HechoAccess::canUseHechosModule($request->user())) {
            return response()->json([
                'message' => 'No tienes permiso para consultar hechos desde esta unidad.',
            ], 403);
        }

        $tz = 'America/Mexico_City';

        $prevDate = Carbon::parse($corte->corte_fecha, $tz)->subWeek()->toDateString();
        $prev = PendientesCorte::where('corte_fecha', $prevDate)->first();

        $idsPrev = $prev
            ? PendientesCorteDetalle::where('pendientes_corte_id', $prev->id)
                ->pluck('hecho_id')->unique()->values()->all()
            : [];

        $idsNow = PendientesCorteDetalle::where('pendientes_corte_id', $corte->id)
            ->pluck('hecho_id')->unique()->values()->all();

        $hechosPrev = count($idsPrev)
            ? Hechos::whereIn('id', $idsPrev)
                ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion'])
                ->get()
                ->keyBy('id')
            : collect();

        $hechosNow = count($idsNow)
            ? Hechos::whereIn('id', $idsNow)
                ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion'])
                ->get()
            : collect();

        $resueltos = [];
        $turnados = [];
        $siguen = [];
        $otros = [];

        foreach ($idsPrev as $id) {
            $h = $hechosPrev->get($id);
            if (!$h) continue;

            if ($h->situacion === 'RESUELTO') {
                $resueltos[] = $h;
            } elseif ($h->situacion === 'TURNADO') {
                $turnados[] = $h;
            } elseif ($h->situacion === 'PENDIENTE') {
                $siguen[] = $h;
            } else {
                $otros[] = $h;
            }
        }

        $setPrev = array_fill_keys($idsPrev, true);

        $nuevos = $hechosNow->filter(function ($h) use ($setPrev) {
            return !isset($setPrev[$h->id]);
        })->values();

        $totales = [
            'previos' => count($idsPrev),
            'resueltos' => count($resueltos),
            'turnados' => count($turnados),
            'siguen_pendiente' => count($siguen),
            'otros' => count($otros),
            'nuevos_pendientes' => $nuevos->count(),
        ];

        return response()->json([
            'corte' => $corte,
            'prev' => $prev,
            'totales' => $totales,
            'resueltos' => $resueltos,
            'turnados' => $turnados,
            'siguen' => $siguen,
            'otros' => $otros,
            'nuevos' => $nuevos,
        ]);
    }
}
