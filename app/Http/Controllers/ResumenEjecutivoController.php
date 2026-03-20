<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ResumenEjecutivoController extends Controller
{
    public function index()
    {
        $fechas = Hechos::query()
            ->select('fecha')
            ->distinct()
            ->orderByDesc('fecha')
            ->pluck('fecha');

        return view('resumen_ejecutivo.index', compact('fechas'));
    }

    public function show($fecha)
    {
        return view('resumen_ejecutivo.show', [
            'fecha' => $fecha
        ]);
    }

    public function data($fecha)
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        $inicio = Carbon::parse($fecha)->startOfDay();
        $fin    = Carbon::parse($fecha)->endOfDay();

        $hechos = Hechos::query()
            ->with(['vehiculos', 'lesionados'])
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get();

        $totalHechos = $hechos->count();
        $totalLesionados = $hechos->sum(fn($h) => $h->lesionados->count());
        $totalVehiculos = $hechos->sum(fn($h) => $h->vehiculos->count());

        $porTipoGroup = $hechos
            ->groupBy(fn($h) => $h->tipo_hecho ?? 'SIN TIPO')
            ->map(fn($items) => $items->count())
            ->sortDesc()
            ->take(10);

        $porHoraAgrupado = $hechos
            ->groupBy(function ($h) {
                return $h->hora ? substr($h->hora, 0, 2) . ':00' : 'SIN HORA';
            })
            ->map(fn($items) => $items->count())
            ->sortKeys();

        $hechosRelevantes = $hechos
            ->map(function ($hecho) {
                $score = 0;

                $lesionadosCount = $hecho->lesionados->count();
                $vehiculosCount  = $hecho->vehiculos->count();
                $tipoHecho       = strtoupper((string) ($hecho->tipo_hecho ?? ''));

                if ($lesionadosCount > 0) $score += 4;
                if ($vehiculosCount >= 3) $score += 2;
                if (str_contains($tipoHecho, 'VOLCADURA')) $score += 4;
                if (str_contains($tipoHecho, 'ATROPELL')) $score += 5;
                if (str_contains($tipoHecho, 'PEATON')) $score += 5;
                if (str_contains($tipoHecho, 'CHOQUE')) $score += 2;

                $hecho->score_relevancia = $score;

                return $hecho;
            })
            ->filter(fn($h) => $h->score_relevancia >= 4)
            ->sortByDesc('score_relevancia')
            ->take(5)
            ->values()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'folio' => $h->folio ?? $h->id,
                    'tipo_hecho' => $h->tipo_hecho,
                    'fecha' => $h->fecha,
                    'hora' => $h->hora,
                    'ubicacion' => $h->ubicacion ?? $h->lugar ?? 'Sin ubicación',
                    'score' => $h->score_relevancia,
                    'lesionados' => $h->lesionados->count(),
                    'vehiculos' => $h->vehiculos->count(),
                    'lat' => $h->lat,
                    'lng' => $h->lng,
                    'url' => route('hechos.show', $h->id),
                ];
            });

        return response()->json([
            'kpis' => [
                'total_hechos' => $totalHechos,
                'total_lesionados' => $totalLesionados,
                'total_vehiculos' => $totalVehiculos,
                'total_relevantes' => $hechosRelevantes->count(),
            ],
            'graficas' => [
                'por_tipo' => [
                    'labels' => $porTipoGroup->keys()->values(),
                    'series' => $porTipoGroup->values(),
                ],
                'por_hora' => [
                    'labels' => $porHoraAgrupado->keys()->values(),
                    'series' => $porHoraAgrupado->values(),
                ],
            ],
            'relevantes' => $hechosRelevantes,
            'fecha' => $fecha,
        ]);
    }
}
