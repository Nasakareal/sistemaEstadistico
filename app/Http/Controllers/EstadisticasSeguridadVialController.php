<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Services\SeguridadVialPowerPointService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EstadisticasSeguridadVialController extends Controller
{
    public function index(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);

        return view('admin.settings.estadisticas_seguridad_vial.index', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ]);
    }

    public function caratula(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);
        $reporte = $this->construirReporte($fechaInicio, $fechaFin);

        return view('admin.settings.estadisticas_seguridad_vial.partials.caratula', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'reporte' => $reporte,
        ]);
    }

    public function comparativaCiudades(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);
        $reporte = $this->construirReporte($fechaInicio, $fechaFin);

        return view('admin.settings.estadisticas_seguridad_vial.partials.comparativa_ciudades', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'reporte' => $reporte,
        ]);
    }

    public function dataComparativaCiudades(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);

        return response()->json($this->construirReporte($fechaInicio, $fechaFin));
    }

    public function descargarPowerPoint(Request $request, SeguridadVialPowerPointService $powerPoint)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);
        $reporte = $this->construirReporte($fechaInicio, $fechaFin);
        $path = $powerPoint->generar($reporte);
        $filename = "informe_seguridad_vial_{$fechaInicio}_{$fechaFin}.pptx";

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ])
            ->deleteFileAfterSend(true);
    }

    public function hechosPorMunicipio(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);

        return response()->json($this->construirReporte($fechaInicio, $fechaFin)['ranking_municipios']);
    }

    public function hechosPorTipo(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($request);

        return response()->json($this->construirReporte($fechaInicio, $fechaFin)['graficas']['por_tipo']);
    }

    private function resolverPeriodo(Request $request): array
    {
        $inicio = $request->get('fecha_inicio') ?: Carbon::now()->startOfMonth()->toDateString();
        $fin = $request->get('fecha_fin') ?: Carbon::now()->toDateString();

        $fechaInicio = Carbon::parse($inicio)->toDateString();
        $fechaFin = Carbon::parse($fin)->toDateString();

        if ($fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        return [$fechaInicio, $fechaFin];
    }

    private function construirReporte(string $fechaInicio, string $fechaFin): array
    {
        $hechos = Hechos::query()
            ->withCount([
                'lesionados',
                'vehiculos',
                'lesionados as fallecidos_count' => function ($query) {
                    $query->whereRaw('UPPER(TRIM(tipo_lesion)) = ?', ['FALLECIDO']);
                },
            ])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get([
                'id',
                'fecha',
                'hora',
                'municipio',
                'tipo_hecho',
                'situacion',
                'es_relevante',
            ]);

        $totalHechos = $hechos->count();
        $totalLesionados = (int) $hechos->sum('lesionados_count');
        $totalFallecidos = (int) $hechos->sum('fallecidos_count');
        $totalVehiculos = (int) $hechos->sum('vehiculos_count');
        $totalRelevantes = $hechos->filter(fn ($hecho) => (bool) $hecho->es_relevante)->count();

        $municipios = $hechos
            ->groupBy(fn ($hecho) => $this->normalizarEtiqueta($hecho->municipio, 'SIN MUNICIPIO'))
            ->map(function ($items, $municipio) use ($totalHechos) {
                $total = $items->count();

                return [
                    'municipio' => $municipio,
                    'hechos' => $total,
                    'siniestros' => $total,
                    'lesionados' => (int) $items->sum('lesionados_count'),
                    'fallecidos' => (int) $items->sum('fallecidos_count'),
                    'vehiculos' => (int) $items->sum('vehiculos_count'),
                    'relevantes' => $items->filter(fn ($hecho) => (bool) $hecho->es_relevante)->count(),
                    'participacion' => $totalHechos > 0 ? round(($total / $totalHechos) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('hechos')
            ->values();

        $porTipo = $hechos
            ->groupBy(fn ($hecho) => $this->normalizarEtiqueta($hecho->tipo_hecho, 'SIN TIPO'))
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->take(10);

        $porSituacion = $hechos
            ->groupBy(fn ($hecho) => $this->normalizarEtiqueta($hecho->situacion, 'SIN SITUACION'))
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $diasSemana = [
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            7 => 'DOMINGO',
        ];

        $porDiaBase = collect($diasSemana)->mapWithKeys(fn ($label) => [$label => 0]);

        $hechos
            ->groupBy(fn ($hecho) => Carbon::parse($hecho->fecha)->dayOfWeekIso)
            ->each(function ($items, $dia) use ($porDiaBase, $diasSemana) {
                $label = $diasSemana[(int) $dia] ?? null;

                if ($label && $porDiaBase->has($label)) {
                    $porDiaBase->put($label, $items->count());
                }
            });

        $porHoraBase = collect(range(0, 23))
            ->mapWithKeys(fn ($hour) => [sprintf('%02d:00', $hour) => 0]);

        $hechos
            ->groupBy(fn ($hecho) => $this->horaKey($hecho->hora ?? null))
            ->map(fn ($items) => $items->count())
            ->each(function ($total, $hora) use ($porHoraBase) {
                if ($porHoraBase->has($hora)) {
                    $porHoraBase->put($hora, $total);
                }
            });

        $topMunicipio = $municipios->first();
        $fechaInicioCarbon = Carbon::parse($fechaInicio);
        $fechaFinCarbon = Carbon::parse($fechaFin);
        $dias = $fechaInicioCarbon->diffInDays($fechaFinCarbon) + 1;
        $horaPico = $porHoraBase->sortDesc()->keys()->first() ?? 'SIN HORA';
        $diaPico = $porDiaBase->sortDesc()->keys()->first() ?? 'SIN DATOS';

        return [
            'periodo' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'texto' => $this->textoPeriodo($fechaInicioCarbon, $fechaFinCarbon),
                'dias' => $dias,
            ],
            'kpis' => [
                'total_hechos' => $totalHechos,
                'total_siniestros' => $totalHechos,
                'total_lesionados' => $totalLesionados,
                'total_fallecidos' => $totalFallecidos,
                'total_vehiculos' => $totalVehiculos,
                'total_relevantes' => $totalRelevantes,
                'municipios_con_hechos' => $municipios->count(),
                'promedio_diario' => round($totalHechos / max(1, $dias), 1),
                'municipio_principal' => $topMunicipio['municipio'] ?? 'SIN DATOS',
                'municipio_principal_total' => $topMunicipio['hechos'] ?? 0,
                'hora_pico' => $horaPico,
                'hora_pico_total' => (int) ($porHoraBase[$horaPico] ?? 0),
                'dia_pico' => $diaPico,
                'dia_pico_total' => (int) ($porDiaBase[$diaPico] ?? 0),
                'tipo_principal' => $porTipo->keys()->first() ?? 'SIN DATOS',
                'tipo_principal_total' => (int) ($porTipo->first() ?? 0),
            ],
            'ranking_municipios' => $municipios,
            'graficas' => [
                'municipios' => [
                    'labels' => $municipios->take(12)->pluck('municipio')->values(),
                    'series' => $municipios->take(12)->pluck('hechos')->values(),
                ],
                'por_tipo' => [
                    'labels' => $porTipo->keys()->values(),
                    'series' => $porTipo->values(),
                ],
                'por_situacion' => [
                    'labels' => $porSituacion->keys()->values(),
                    'series' => $porSituacion->values(),
                ],
                'por_dia' => [
                    'labels' => $porDiaBase->keys()->values(),
                    'series' => $porDiaBase->values(),
                ],
                'por_hora' => [
                    'labels' => $porHoraBase->keys()->values(),
                    'series' => $porHoraBase->values(),
                ],
            ],
        ];
    }

    private function normalizarEtiqueta($valor, string $fallback): string
    {
        $texto = preg_replace('/\s+/', ' ', trim((string) $valor));

        if ($texto === '') {
            return $fallback;
        }

        return mb_strtoupper($texto, 'UTF-8');
    }

    private function horaKey($hora): string
    {
        if ($hora instanceof \DateTimeInterface) {
            return $hora->format('H:00');
        }

        if (preg_match('/\b(\d{1,2}):(\d{2})/', (string) $hora, $match)) {
            $hour = max(0, min(23, (int) $match[1]));

            return sprintf('%02d:00', $hour);
        }

        return '00:00';
    }

    private function textoPeriodo(Carbon $fechaInicio, Carbon $fechaFin): string
    {
        if ($fechaInicio->isSameDay($fechaFin)) {
            return $fechaInicio->translatedFormat('d \d\e F \d\e Y');
        }

        return $fechaInicio->translatedFormat('d \d\e F \d\e Y')
            . ' al '
            . $fechaFin->translatedFormat('d \d\e F \d\e Y');
    }
}
