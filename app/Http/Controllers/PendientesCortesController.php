<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use Illuminate\Http\Request;

class PendientesCortesController extends Controller
{
    public function index(Request $request)
    {
        $cortes = PendientesCorte::orderByDesc('corte_fecha')->paginate(30);

        return view('hechos.pendientes_cortes.index', compact('cortes'));
    }

    public function show(Request $request, PendientesCorte $corte)
    {
        $prev = PendientesCorte::where('corte_fecha', '<', $corte->corte_fecha)
            ->orderByDesc('corte_fecha')
            ->first();

        $idsPrev = $prev
            ? PendientesCorteDetalle::where('pendientes_corte_id', $prev->id)
                ->pluck('hecho_id')->unique()->values()->all()
            : [];

        $idsNow = PendientesCorteDetalle::where('pendientes_corte_id', $corte->id)
            ->pluck('hecho_id')->unique()->values()->all();

        $idsAll = array_values(array_unique(array_merge($idsPrev, $idsNow)));

        $hechosAll = count($idsAll)
            ? Hechos::whereIn('id', $idsAll)
                ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion'])
                ->get()
                ->keyBy('id')
            : collect();

        $resueltos = [];
        $turnados = [];
        $siguen = [];
        $otros = [];

        foreach ($idsPrev as $id) {
            $h = $hechosAll->get($id);
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

        $nuevos = collect($idsNow)
            ->filter(fn ($id) => !isset($setPrev[$id]))
            ->map(fn ($id) => $hechosAll->get($id))
            ->filter()
            ->values();

        $totales = [
            'previos' => count($idsPrev),
            'resueltos' => count($resueltos),
            'turnados' => count($turnados),
            'siguen_pendiente' => count($siguen),
            'otros' => count($otros),
            'nuevos_pendientes' => $nuevos->count(),
        ];

        return view('hechos.pendientes_cortes.show', compact(
            'corte',
            'prev',
            'totales',
            'resueltos',
            'turnados',
            'siguen',
            'otros',
            'nuevos'
        ));
    }
}
