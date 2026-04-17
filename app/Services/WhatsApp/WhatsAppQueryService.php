<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use Illuminate\Support\Facades\DB;

class WhatsAppQueryService
{
    protected WhatsAppRenderService $renderService;

    public function __construct(WhatsAppRenderService $renderService)
    {
        $this->renderService = $renderService;
    }

    public function executeImmediate($user, array $context, string $module, string $action): array
    {
        if ($module !== 'siniestros') {
            return [
                'text' => 'Esa opción todavía no está disponible.',
            ];
        }

        if ($action === 'hechos_hoy') {
            return $this->hechosHoy($user, false);
        }

        if ($action === 'mis_hechos_hoy') {
            return $this->hechosHoy($user, true);
        }

        if ($action === 'estadisticas_rapidas') {
            return [
                'text' => 'Selecciona la estadística rápida que deseas consultar.',
            ];
        }

        if ($action === 'estadistica_resumen_general') {
            return [
                'text' => 'Selecciona el periodo a consultar.',
            ];
        }

        if ($action === 'estadistica_motocicletas') {
            return [
                'text' => 'Selecciona el periodo a consultar.',
            ];
        }

        if ($action === 'estadistica_lesionados') {
            return [
                'text' => 'Selecciona el periodo a consultar.',
            ];
        }

        if ($action === 'estadistica_fallecidos') {
            return [
                'text' => 'Selecciona el periodo a consultar.',
            ];
        }

        if ($action === 'estadistica_situacion') {
            return [
                'text' => 'Selecciona la situación y después el periodo.',
            ];
        }

        if ($action === 'estadistica_tipo_hecho') {
            return [
                'text' => 'Selecciona el tipo de hecho y después el periodo.',
            ];
        }

        return [
            'text' => 'Esa opción todavía no está disponible.',
        ];
    }

    public function executeWithParam($user, array $context, string $module, string $action, string $paramType, string $value): array
    {
        if ($module !== 'siniestros') {
            return [
                'text' => 'Ese módulo todavía no está disponible.',
            ];
        }

        if ($action === 'hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $value, false);
        }

        if ($action === 'mis_hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $value, true);
        }

        if ($action === 'detalle_folio') {
            return $this->detallePorFolio($user, $value, false);
        }

        if ($action === 'mi_detalle_folio') {
            return $this->detallePorFolio($user, $value, true);
        }

        return [
            'text' => 'No pude procesar esa consulta.',
        ];
    }

    public function executeQuickStat($user, array $context, string $action, string $period, array $filters = []): array
    {
        [$desde, $hasta] = $this->resolvePeriod($period);

        if (!$desde || !$hasta) {
            return [
                'text' => 'No pude identificar el periodo solicitado.',
            ];
        }

        $query = Hechos::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta);

        if (!empty($context['solo_propios'])) {
            $query->where('user_id', $user->id);
        }

        if (!empty($filters['situacion'])) {
            $query->where('situacion', $filters['situacion']);
        }

        if (!empty($filters['tipo_hecho'])) {
            $query->where('tipo_hecho', $filters['tipo_hecho']);
        }

        if ($action === 'estadistica_motocicletas') {
            $query->whereHas('vehiculos', function ($q) {
                $q->whereIn('tipo', [
                    'Trabajo',
                    'Cruiser',
                    'Doble Propósito',
                    'Scooter',
                    'Enduro',
                    'Naked',
                    'Pista',
                    'Chopper',
                    'Cuatrimoto',
                ]);
            });
        }

        if ($action === 'estadistica_situacion' && empty($filters['situacion'])) {
            return [
                'text' => 'Falta indicar la situación a consultar.',
            ];
        }

        if ($action === 'estadistica_tipo_hecho' && empty($filters['tipo_hecho'])) {
            return [
                'text' => 'Falta indicar el tipo de hecho a consultar.',
            ];
        }

        $hechos = (clone $query)->count();

        $lesionadosQuery = DB::table('lesionados')
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id')
            ->whereDate('hechos.fecha', '>=', $desde)
            ->whereDate('hechos.fecha', '<=', $hasta);

        if (!empty($context['solo_propios'])) {
            $lesionadosQuery->where('hechos.user_id', $user->id);
        }

        if (!empty($filters['situacion'])) {
            $lesionadosQuery->where('hechos.situacion', $filters['situacion']);
        }

        if (!empty($filters['tipo_hecho'])) {
            $lesionadosQuery->where('hechos.tipo_hecho', $filters['tipo_hecho']);
        }

        if ($action === 'estadistica_motocicletas') {
            $lesionadosQuery->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('hecho_vehiculo')
                    ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id')
                    ->whereColumn('hecho_vehiculo.hecho_id', 'hechos.id')
                    ->whereIn('vehiculos.tipo', [
                        'Trabajo',
                        'Cruiser',
                        'Doble Propósito',
                        'Scooter',
                        'Enduro',
                        'Naked',
                        'Pista',
                        'Chopper',
                        'Cuatrimoto',
                    ]);
            });
        }

        $lesionados = (clone $lesionadosQuery)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) <> 'FALLECIDO'")
            ->count('lesionados.id');

        $fallecidos = (clone $lesionadosQuery)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion,''))) = 'FALLECIDO'")
            ->count('lesionados.id');

        $resueltos = (clone $query)->where('situacion', 'RESUELTO')->count();
        $pendientes = (clone $query)->where('situacion', 'PENDIENTE')->count();
        $turnados = (clone $query)->where('situacion', 'TURNADO')->count();
        $reportes = (clone $query)->where('situacion', 'REPORTE')->count();

        $tiposTop = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(tipo_hecho), ''), 'SIN TIPO') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        if ($action === 'estadistica_lesionados') {
            return [
                'text' => $this->buildQuickMessage(
                    'Estadística rápida de lesionados',
                    $desde,
                    $hasta,
                    [
                        "- Lesionados: " . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        "- Hechos relacionados: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        if ($action === 'estadistica_fallecidos') {
            return [
                'text' => $this->buildQuickMessage(
                    'Estadística rápida de fallecidos',
                    $desde,
                    $hasta,
                    [
                        "- Fallecidos: " . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        "- Hechos relacionados: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        if ($action === 'estadistica_motocicletas') {
            return [
                'text' => $this->buildQuickMessage(
                    'Estadística rápida de motocicletas',
                    $desde,
                    $hasta,
                    [
                        "- Hechos con motocicletas: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        "- Lesionados: " . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        "- Fallecidos: " . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        "- Resueltos: " . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                        "- Pendientes: " . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                        "- Turnado: " . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                        "- Reporte: " . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        if ($action === 'estadistica_situacion') {
            $situacion = (string) ($filters['situacion'] ?? 'SIN SITUACIÓN');

            return [
                'text' => $this->buildQuickMessage(
                    'Estadística rápida por situación',
                    $desde,
                    $hasta,
                    [
                        "- Situación: {$situacion}",
                        "- Hechos: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        "- Lesionados: " . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        "- Fallecidos: " . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        if ($action === 'estadistica_tipo_hecho') {
            $tipoHecho = (string) ($filters['tipo_hecho'] ?? 'SIN TIPO');

            return [
                'text' => $this->buildQuickMessage(
                    'Estadística rápida por tipo de hecho',
                    $desde,
                    $hasta,
                    [
                        "- Tipo de hecho: {$tipoHecho}",
                        "- Hechos: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        "- Lesionados: " . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        "- Fallecidos: " . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        "- Resueltos: " . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                        "- Pendientes: " . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                        "- Turnado: " . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                        "- Reporte: " . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        $lineasTop = [];
        foreach ($tiposTop as $row) {
            $lineasTop[] = "- {$row->label}: " . str_pad((string) $row->total, 2, '0', STR_PAD_LEFT);
        }

        return [
            'text' => $this->buildQuickMessage(
                'Estadística rápida de siniestros',
                $desde,
                $hasta,
                array_merge([
                    "- Hechos: " . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                    "- Lesionados: " . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                    "- Fallecidos: " . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                    "- Resueltos: " . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                    "- Pendientes: " . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                    "- Turnado: " . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                    "- Reporte: " . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
                ], $lineasTop)
            ),
        ];
    }

    protected function hechosHoy($user, bool $soloPropios): array
    {
        $query = Hechos::query()
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hoy = now()->toDateString();

        $hechos = $query
            ->whereDate('fecha', $hoy)
            ->limit(15)
            ->get();

        if ($hechos->isEmpty()) {
            return [
                'text' => $soloPropios ? 'No encontré hechos tuyos el día de hoy.' : 'No encontré hechos el día de hoy.',
            ];
        }

        $lineas = [];
        $lineas[] = $soloPropios ? 'Tus hechos de hoy:' : 'Hechos de hoy:';
        $lineas[] = '';

        foreach ($hechos as $hecho) {
            $lineas[] = 'ID: ' . $hecho->id
                . ' | FECHA: ' . ((string) $hecho->fecha)
                . ' ' . substr((string) $hecho->hora, 0, 5)
                . ' | TIPO: ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ESTATUS: ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function buscarHechosPorPlaca($user, string $placa, bool $soloPropios): array
    {
        $placaNormalizada = $this->normalizarPlaca($placa);

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->whereHas('vehiculos', function ($q) use ($placaNormalizada) {
                $q->whereRaw(
                    "REPLACE(REPLACE(REPLACE(UPPER(placas), '-', ''), ' ', ''), '.', '') = ?",
                    [$placaNormalizada]
                );
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hechos = $query->limit(10)->get();

        if ($hechos->isEmpty()) {
            return [
                'text' => $soloPropios
                    ? "No encontré hechos tuyos con las placas {$placa}."
                    : "No encontré hechos con las placas {$placa}.",
            ];
        }

        $lineas = [];
        $lineas[] = $soloPropios
            ? 'Encontré ' . $hechos->count() . " hecho(s) tuyos con las placas {$placa}:"
            : 'Encontré ' . $hechos->count() . " hecho(s) con las placas {$placa}:";
        $lineas[] = '';

        foreach ($hechos as $hecho) {
            $lineas[] = 'ID: ' . $hecho->id
                . ' | FECHA: ' . ((string) $hecho->fecha)
                . ' ' . substr((string) $hecho->hora, 0, 5)
                . ' | TIPO: ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ESTATUS: ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function detallePorFolio($user, string $folio, bool $soloPropios): array
    {
        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where('id', $folio);

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hecho = $query->first();

        if (!$hecho) {
            return [
                'text' => $soloPropios
                    ? "No encontré un hecho tuyo con el ID {$folio}."
                    : "No encontré el hecho con ID {$folio}.",
            ];
        }

        return $this->renderService->renderDetalleHecho($hecho);
    }

    protected function resolvePeriod(string $period): array
    {
        $today = now()->startOfDay();

        if ($period === 'hoy') {
            return [$today->toDateString(), $today->toDateString()];
        }

        if ($period === 'ayer') {
            $ayer = now()->subDay()->startOfDay();
            return [$ayer->toDateString(), $ayer->toDateString()];
        }

        if ($period === 'este_mes') {
            return [now()->startOfMonth()->toDateString(), now()->toDateString()];
        }

        if ($period === 'mes_anterior') {
            $inicio = now()->subMonthNoOverflow()->startOfMonth();
            $fin = now()->subMonthNoOverflow()->endOfMonth();

            return [$inicio->toDateString(), $fin->toDateString()];
        }

        return [null, null];
    }

    protected function buildQuickMessage(string $asunto, string $desde, string $hasta, array $lineas): string
    {
        return implode("\n", array_merge([
            'GUARDIA CIVIL',
            'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.',
            '',
            'UNIDAD DE ATENCIÓN A SINIESTROS.',
            '',
            'ASUNTO: ' . $asunto . '.',
            '',
            'PERIODO: ' . $desde . ' al ' . $hasta . '.',
            '',
            'RESULTADO:'
        ], $lineas, [
            '',
            'PARA CONOCIMIENTO DE LA SUPERIORIDAD.'
        ]));
    }

    protected function normalizarPlaca(string $placa): string
    {
        $placa = mb_strtoupper(trim($placa), 'UTF-8');
        $placa = str_replace(['-', ' ', '.'], '', $placa);

        return $placa;
    }
}
