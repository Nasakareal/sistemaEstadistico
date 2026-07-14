<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Services\Fotos\HechoFotoStorage;
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

        $hechos = Hechos::query()
            ->with([
                'vehiculos',
                'lesionados',
                'creator',
                'marcadoRelevantePor',
                'revisadoPor',
            ])
            ->whereDate('fecha', $fecha)
            ->get();

        $totalHechos = $hechos->count();
        $totalLesionados = $hechos->sum(fn($h) => $h->lesionados->count());
        $totalVehiculos = $hechos->sum(fn($h) => $h->vehiculos->count());

        $porTipoGroup = $hechos
            ->groupBy(fn($h) => $h->tipo_hecho ?: 'SIN TIPO')
            ->map(fn($items) => $items->count())
            ->sortDesc()
            ->take(10);

        $porHoraAgrupado = $hechos
            ->groupBy(function ($h) {
                return $h->hora ? substr((string) $h->hora, 0, 2) . ':00' : 'SIN HORA';
            })
            ->map(fn($items) => $items->count())
            ->sortKeys();

        $hechosRelevantes = $hechos
            ->filter(fn($h) => (bool) $h->es_relevante)
            ->sortBy([
                ['hora', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function ($h) {
                $fotoStorage = app(HechoFotoStorage::class);
                $fotoLugar = $fotoStorage->url($h->foto_lugar);
                $fotoLugar2 = $fotoStorage->url($h->foto_lugar_2);
                $fotoSituacion = $fotoStorage->url($h->foto_situacion);

                $ubicacionPartes = array_filter([
                    $h->calle,
                    $h->colonia,
                    $h->municipio,
                ]);

                $ubicacion = count($ubicacionPartes) ? implode(', ', $ubicacionPartes) : 'Sin ubicación';

                return [
                    'id' => $h->id,
                    'folio_c5i' => $h->folio_c5i,
                    'tipo_hecho' => $h->tipo_hecho,
                    'situacion' => $h->situacion,
                    'fecha' => $h->fecha,
                    'hora' => $h->hora,
                    'sector' => $h->sector,
                    'calle' => $h->calle,
                    'colonia' => $h->colonia,
                    'entre_calles' => $h->entre_calles,
                    'municipio' => $h->municipio,
                    'ubicacion' => $ubicacion,
                    'causas' => $h->causas,
                    'colision_camino' => $h->colision_camino,
                    'clima' => $h->clima,
                    'tiempo' => $h->tiempo,
                    'superficie_via' => $h->superficie_via,
                    'condiciones' => $h->condiciones,
                    'control_transito' => $h->control_transito,
                    'danos_patrimoniales' => $h->danos_patrimoniales,
                    'propiedades_afectadas' => $h->propiedades_afectadas,
                    'monto_danos_patrimoniales' => $h->monto_danos_patrimoniales,
                    'oficio_mp' => $h->oficio_mp,
                    'vehiculos_mp' => $h->vehiculos_mp,
                    'personas_mp' => $h->personas_mp,
                    'lat' => $h->lat,
                    'lng' => $h->lng,
                    'foto_lugar' => $fotoLugar,
                    'foto_lugar_2' => $fotoLugar2,
                    'foto_situacion' => $fotoSituacion,
                    'foto_principal' => $fotoSituacion ?: $fotoLugar ?: $fotoLugar2,
                    'lesionados_count' => $h->lesionados->count(),
                    'vehiculos_count' => $h->vehiculos->count(),
                    'creado_por' => optional($h->creator)->name,
                    'marcado_relevante_por' => optional($h->marcadoRelevantePor)->name,
                    'marcado_relevante_at' => $h->marcado_relevante_at,
                    'revisado_por' => optional($h->revisadoPor)->name,
                    'estado_revision' => $h->estado_revision,
                    'observacion_revision' => $h->observacion_revision,
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
