<?php

namespace App\Services\WhatsApp;

use App\Models\Actividad;
use App\Models\Hechos;
use App\Models\Lesionado;
use App\Models\OperativoDispositivo;
use App\Models\Personal;
use App\Models\PersonalAsignacion;
use App\Models\PuestaDisposicion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppQueryService
{
    protected WhatsAppRenderService $renderService;
    protected WhatsAppMenuService $menuService;

    public function __construct(WhatsAppRenderService $renderService, WhatsAppMenuService $menuService)
    {
        $this->renderService = $renderService;
        $this->menuService = $menuService;
    }

    public function executeOpenAI($user, array $context, array $json): ?array
    {
        $json = $this->normalizarConsultaOpenAI($json);
        $accion = (string) ($json['accion'] ?? '');

        switch ($accion) {
            case 'contar_hechos':
                return $this->contarHechos($user, $context, $json);

            case 'detalle_hecho':
                return $this->detalleHechoOpenAI($user, $context, $json);

            case 'lista_hechos':
                return $this->listaHechos($user, $context, $json);

            case 'estadistica_hechos':
            case 'resumen_hechos':
                return $this->estadisticaHechos($user, $context, $json);

            case 'personal_armado':
                return $this->personalArmadoOpenAI($user, $context, $json);

            case 'personal_activo':
                return $this->personalActivoOpenAI($user, $context, $json);

            case 'actividades':
            case 'estadistica_actividades':
                return $this->actividadesOpenAI($user, $context, $json, false);

            case 'lista_actividades':
                return $this->actividadesOpenAI($user, $context, $json, true);

            case 'operativos':
            case 'estadistica_operativos':
                return $this->operativosOpenAI($user, $context, $json, false);

            case 'lista_operativos':
                return $this->operativosOpenAI($user, $context, $json, true);

            case 'puestas_disposicion':
            case 'estadistica_puestas_disposicion':
                if (!empty($json['id'])) {
                    return $this->detallePuestaOpenAI($user, $context, $json);
                }

                return $this->puestasOpenAI($user, $context, $json, false);

            case 'lista_puestas_disposicion':
                return $this->puestasOpenAI($user, $context, $json, true);

            case 'detalle_puesta_disposicion':
                return $this->detallePuestaOpenAI($user, $context, $json);

            default:
                return null;
        }
    }

    public function executeImmediate($user, array $context, string $module, string $action): array
    {
        if ($action === 'hechos_hoy') {
            return $this->hechosHoy($user, $context, $module, false);
        }

        if ($action === 'mis_hechos_hoy') {
            return $this->hechosHoy($user, $context, $module, true);
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

        if ($action === 'personal_armado') {
            return $this->personalArmado($user, $context, $module);
        }

        if ($action === 'personal_activo') {
            return $this->personalActivo($user, $context, $module);
        }

        if ($action === 'actividades_hoy') {
            return $this->actividadesHoy($user, $context, $module);
        }

        if ($action === 'operativos_hoy') {
            return $this->operativosHoy($user, $context, $module);
        }

        if ($action === 'puestas_hoy') {
            return $this->puestasHoy($user, $context, $module);
        }

        if ($action === 'operativos_tipo') {
            return [
                'text' => "Escribe el tipo de operativo.\n\nEjemplo:\nCASCO",
            ];
        }

        return [
            'text' => 'Esa opción todavía no está disponible.',
        ];
    }

    public function executeWithParam($user, array $context, string $module, string $action, string $paramType, string $value): array
    {
        if ($action === 'hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $context, $module, $value, false);
        }

        if ($action === 'mis_hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $context, $module, $value, true);
        }

        if ($action === 'detalle_folio') {
            return $this->detallePorFolio($user, $context, $module, $value, false);
        }

        if ($action === 'mi_detalle_folio') {
            return $this->detallePorFolio($user, $context, $module, $value, true);
        }

        if ($action === 'actividades_rango') {
            return $this->actividadesPorRango($user, $context, $module, $value);
        }

        if ($action === 'operativos_tipo') {
            return $this->operativosPorTipo($user, $context, $module, $value);
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

        $unidadId = $this->resolveUnitIdFromContext($user, $context, null);

        $query = Hechos::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta);

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);

        if (!empty($context['solo_propios'])) {
            $query->where('created_by', $user->id);
        }

        if (!empty($filters['situacion'])) {
            $this->whereUpperEquals($query, 'situacion', (string) $filters['situacion']);
        }

        $this->aplicarFiltroTipoHecho($query, $filters);

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
        $lesionadosBase = $this->lesionadosQueryFromHechos($query);
        $lesionados = (clone $lesionadosBase)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) <> ?", ['FALLECIDO'])
            ->count('lesionados.id');
        $fallecidos = (clone $lesionadosBase)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) = ?", ['FALLECIDO'])
            ->count('lesionados.id');

        $resueltos = $this->countBySituacion($query, 'RESUELTO');
        $pendientes = $this->countBySituacion($query, 'PENDIENTE');
        $turnados = $this->countBySituacion($query, 'TURNADO');
        $reportes = $this->countBySituacion($query, 'REPORTE');

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
                    $unidadId,
                    [
                        'Lesionados: ' . $this->formatNumber($lesionados),
                        'Hechos relacionados: ' . $this->formatNumber($hechos),
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
                    $unidadId,
                    [
                        'Fallecidos: ' . $this->formatNumber($fallecidos),
                        'Hechos relacionados: ' . $this->formatNumber($hechos),
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
                    $unidadId,
                    [
                        'Hechos con motocicletas: ' . $this->formatNumber($hechos),
                        'Lesionados: ' . $this->formatNumber($lesionados),
                        'Fallecidos: ' . $this->formatNumber($fallecidos),
                        'Resueltos: ' . $this->formatNumber($resueltos),
                        'Pendientes: ' . $this->formatNumber($pendientes),
                        'Turnado: ' . $this->formatNumber($turnados),
                        'Reporte: ' . $this->formatNumber($reportes),
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
                    $unidadId,
                    [
                        "Situación: {$situacion}",
                        'Hechos: ' . $this->formatNumber($hechos),
                        'Lesionados: ' . $this->formatNumber($lesionados),
                        'Fallecidos: ' . $this->formatNumber($fallecidos),
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
                    $unidadId,
                    [
                        "Tipo de hecho: {$tipoHecho}",
                        'Hechos: ' . $this->formatNumber($hechos),
                        'Lesionados: ' . $this->formatNumber($lesionados),
                        'Fallecidos: ' . $this->formatNumber($fallecidos),
                        'Resueltos: ' . $this->formatNumber($resueltos),
                        'Pendientes: ' . $this->formatNumber($pendientes),
                        'Turnado: ' . $this->formatNumber($turnados),
                        'Reporte: ' . $this->formatNumber($reportes),
                    ]
                ),
            ];
        }

        $lineasTop = [];

        foreach ($tiposTop as $row) {
            $lineasTop[] = $row->label . ': ' . $this->formatNumber((int) $row->total);
        }

        return [
            'text' => $this->buildQuickMessage(
                'Estadística rápida de siniestros',
                $desde,
                $hasta,
                $unidadId,
                array_merge([
                    'Hechos: ' . $this->formatNumber($hechos),
                    'Lesionados: ' . $this->formatNumber($lesionados),
                    'Fallecidos: ' . $this->formatNumber($fallecidos),
                    'Resueltos: ' . $this->formatNumber($resueltos),
                    'Pendientes: ' . $this->formatNumber($pendientes),
                    'Turnado: ' . $this->formatNumber($turnados),
                    'Reporte: ' . $this->formatNumber($reportes),
                ], $lineasTop)
            ),
        ];
    }

    protected function contarHechos($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $query = $this->hechosBaseQuery($user, $context, $json, $unidadId);
        $total = (clone $query)->count();

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Conteo de hechos',
                $this->periodoTexto($filtros),
                ['Hechos: ' . $this->formatNumber($total)]
            ),
        ];
    }

    protected function estadisticaHechos($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $query = $this->hechosBaseQuery($user, $context, $json, $unidadId);

        $hechos = (clone $query)->count();
        $lesionadosBase = $this->lesionadosQueryFromHechos($query);
        $lesionados = (clone $lesionadosBase)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) <> ?", ['FALLECIDO'])
            ->count('lesionados.id');
        $fallecidos = (clone $lesionadosBase)
            ->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) = ?", ['FALLECIDO'])
            ->count('lesionados.id');

        $lineas = [
            'Hechos: ' . $this->formatNumber($hechos),
            'Lesionados: ' . $this->formatNumber($lesionados),
            'Fallecidos: ' . $this->formatNumber($fallecidos),
            'Resueltos: ' . $this->formatNumber($this->countBySituacion($query, 'RESUELTO')),
            'Pendientes: ' . $this->formatNumber($this->countBySituacion($query, 'PENDIENTE')),
            'Turnado: ' . $this->formatNumber($this->countBySituacion($query, 'TURNADO')),
            'Reporte: ' . $this->formatNumber($this->countBySituacion($query, 'REPORTE')),
        ];

        $tipos = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(tipo_hecho), ''), 'SIN TIPO') as tipo_hecho_label, COUNT(*) as total")
            ->groupBy('tipo_hecho_label')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        foreach ($tipos as $tipo) {
            $lineas[] = $tipo->tipo_hecho_label . ': ' . $this->formatNumber((int) $tipo->total);
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Estadística de hechos',
                $this->periodoTexto($filtros),
                $lineas
            ),
        ];
    }

    protected function listaHechos($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $rows = $this->hechosBaseQuery($user, $context, $json, $unidadId)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(20)
            ->get();

        $lineas = [];

        if ($rows->isEmpty()) {
            $lineas[] = 'No se encontraron hechos.';
        }

        foreach ($rows as $row) {
            $lineas[] = 'ID ' . $row->id
                . ' | ' . $this->formatDate($row->fecha)
                . ' ' . $this->formatTime($row->hora)
                . ' | ' . ($row->tipo_hecho ?: 'SIN TIPO')
                . ' | ' . ($row->situacion ?: 'SIN SITUACIÓN');
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Lista de hechos',
                $this->periodoTexto($filtros),
                $lineas
            ),
        ];
    }

    protected function detalleHechoOpenAI($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $hechoId = $json['id'] ?? null;

        if (!$hechoId) {
            return [
                'text' => 'Hecho no encontrado',
            ];
        }

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where('id', $hechoId);

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);

        $hecho = $query->first();

        if (!$hecho) {
            return [
                'text' => 'Hecho no encontrado',
            ];
        }

        return $this->renderService->renderDetalleHecho($hecho);
    }

    protected function personalArmadoOpenAI($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);

        return $this->personalArmadoReporte($unidadId);
    }

    protected function personalActivoOpenAI($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);

        return $this->personalActivoReporte($unidadId);
    }

    protected function actividadesOpenAI($user, array $context, array $json, bool $lista): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $query = Actividad::query();

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);
        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        if (!empty($filtros['municipio'])) {
            $this->whereUpperEquals($query, 'municipio', (string) $filtros['municipio']);
        }

        if (!empty($filtros['delegacion_id'])) {
            $query->where('delegacion_id', (int) $filtros['delegacion_id']);
        }

        if ($lista) {
            $rows = (clone $query)
                ->orderByDesc('fecha')
                ->orderByDesc('hora')
                ->limit(20)
                ->get();

            $lineas = [];

            if ($rows->isEmpty()) {
                $lineas[] = 'No se encontraron actividades.';
            }

            foreach ($rows as $row) {
                $lineas[] = $this->formatDate($row->fecha)
                    . ' ' . $this->formatTime($row->hora)
                    . ' | ' . ($row->nombre ?: 'SIN NOMBRE')
                    . ' | Cantidad: ' . $this->formatNumber((int) ($row->cantidad ?? 0));
            }

            return [
                'text' => $this->renderService->renderReporte(
                    $unidadId,
                    'Lista de actividades',
                    $this->periodoTexto($filtros),
                    $lineas
                ),
            ];
        }

        $total = (clone $query)->count();
        $cantidad = (clone $query)->sum('cantidad');
        $personasAlcanzadas = (clone $query)->sum('personas_alcanzadas');
        $personasParticipantes = (clone $query)->sum('personas_participantes');
        $personasDetenidas = (clone $query)->sum('personas_detenidas');

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Actividades',
                $this->periodoTexto($filtros),
                [
                    'Registros: ' . $this->formatNumber($total),
                    'Cantidad reportada: ' . $this->formatNumber((int) $cantidad),
                    'Personas alcanzadas: ' . $this->formatNumber((int) $personasAlcanzadas),
                    'Personas participantes: ' . $this->formatNumber((int) $personasParticipantes),
                    'Personas detenidas: ' . $this->formatNumber((int) $personasDetenidas),
                ]
            ),
        ];
    }

    protected function operativosOpenAI($user, array $context, array $json, bool $lista): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $query = OperativoDispositivo::query();

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);
        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');
        $this->aplicarFiltroTipoOperativo($query, $filtros);

        if (!empty($filtros['delegacion_id'])) {
            $query->where('delegacion_id', (int) $filtros['delegacion_id']);
        }

        if ($lista) {
            $rows = (clone $query)
                ->orderByDesc('fecha')
                ->orderByDesc('hora')
                ->limit(20)
                ->get();

            $lineas = [];

            if ($rows->isEmpty()) {
                $lineas[] = 'No se encontraron operativos.';
            }

            foreach ($rows as $row) {
                $lineas[] = $this->formatDate($row->fecha)
                    . ' ' . $this->formatTime($row->hora)
                    . ' | ' . ($row->tipo_reporte ?: 'SIN TIPO')
                    . ' | ' . ($row->asunto ?: ($row->lugar ?: 'SIN ASUNTO'));
            }

            return [
                'text' => $this->renderService->renderReporte(
                    $unidadId,
                    'Lista de operativos',
                    $this->periodoTexto($filtros),
                    $lineas
                ),
            ];
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Operativos',
                $this->periodoTexto($filtros),
                [
                    'Registros: ' . $this->formatNumber((clone $query)->count()),
                    'Vehículos inspeccionados: ' . $this->formatNumber((int) (clone $query)->sum('vehiculos_inspeccionados')),
                    'Personas inspeccionadas: ' . $this->formatNumber((int) (clone $query)->sum('personas_inspeccionadas')),
                    'Vehículos impactados: ' . $this->formatNumber((int) (clone $query)->sum('vehiculos_impactados')),
                    'Personas impactadas: ' . $this->formatNumber((int) (clone $query)->sum('personas_impactadas')),
                    'Estado de fuerza: ' . $this->formatNumber((int) (clone $query)->sum('estado_fuerza_participante')),
                    'Kilómetros recorridos: ' . $this->formatDecimal((float) (clone $query)->sum('kilometros_recorridos')),
                    'Acompañamientos: ' . $this->formatNumber((int) (clone $query)->sum('acompanamientos')),
                    'Abanderamientos: ' . $this->formatNumber((int) (clone $query)->sum('abanderamientos')),
                    'Auxilios viales: ' . $this->formatNumber((int) (clone $query)->sum('auxilios_viales')),
                    'Puestas a disposición: ' . $this->formatNumber((int) (clone $query)->sum('puestas_disposicion')),
                ]
            ),
        ];
    }

    protected function puestasOpenAI($user, array $context, array $json, bool $lista): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $filtros = $this->filtros($json);
        $query = PuestaDisposicion::query();

        $this->applyUnitFilter($query, 'unidad_id', $unidadId);
        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha_puesta', 'hora_puesta');

        if (!empty($filtros['tipo_puesta'])) {
            $this->whereUpperEquals($query, 'tipo_puesta', (string) $filtros['tipo_puesta']);
        }

        if (!empty($filtros['estatus'])) {
            $this->whereUpperEquals($query, 'estatus', (string) $filtros['estatus']);
        }

        if (!empty($filtros['delegacion_id'])) {
            $query->where('delegacion_id', (int) $filtros['delegacion_id']);
        }

        if ($lista) {
            $rows = (clone $query)
                ->orderByDesc('fecha_puesta')
                ->orderByDesc('hora_puesta')
                ->limit(20)
                ->get();

            $lineas = [];

            if ($rows->isEmpty()) {
                $lineas[] = 'No se encontraron puestas a disposición.';
            }

            foreach ($rows as $row) {
                $lineas[] = 'ID ' . $row->id
                    . ' | ' . $this->formatDate($row->fecha_puesta)
                    . ' ' . $this->formatTime($row->hora_puesta)
                    . ' | No. ' . ($row->numero_puesta ?: 'S/N')
                    . ' | ' . ($row->tipo_puesta ?: 'SIN TIPO')
                    . ' | ' . ($row->estatus ?: 'SIN ESTATUS');
            }

            return [
                'text' => $this->renderService->renderReporte(
                    $unidadId,
                    'Lista de puestas a disposición',
                    $this->periodoTexto($filtros),
                    $lineas
                ),
            ];
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Puestas a disposición',
                $this->periodoTexto($filtros),
                [
                    'Puestas a disposición: ' . $this->formatNumber((clone $query)->count()),
                    'Activas: ' . $this->formatNumber($this->countByColumn($query, 'estatus', 'ACTIVA')),
                    'Canceladas: ' . $this->formatNumber($this->countByColumn($query, 'estatus', 'CANCELADA')),
                ]
            ),
        ];
    }

    protected function detallePuestaOpenAI($user, array $context, array $json): array
    {
        $unidadId = $this->resolveUnitIdForJson($user, $context, $json);
        $puestaId = $json['id'] ?? null;

        if (!$puestaId) {
            return [
                'text' => 'Puesta a disposición no encontrada',
            ];
        }

        $query = PuestaDisposicion::query()
            ->with(['personas', 'vehiculos', 'objetos'])
            ->where(function ($q) use ($puestaId) {
                $q->where('id', $puestaId)
                    ->orWhere('numero_puesta', $puestaId);
            });

        $this->applyUnitFilter($query, 'unidad_id', $unidadId);

        $puesta = $query->first();

        if (!$puesta) {
            return [
                'text' => 'Puesta a disposición no encontrada',
            ];
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Detalle de puesta a disposición',
                $this->periodoTexto([
                    'fecha' => $this->formatDate($puesta->fecha_puesta),
                    'hora_inicio' => null,
                    'hora_fin' => null,
                ]),
                [
                    'ID: ' . $puesta->id,
                    'Número: ' . ($puesta->numero_puesta ?: 'S/N'),
                    'Tipo: ' . ($puesta->tipo_puesta ?: 'SIN TIPO'),
                    'Estatus: ' . ($puesta->estatus ?: 'SIN ESTATUS'),
                    'Policía: ' . ($puesta->nombre_policia ?: 'SIN DATO'),
                    'MP: ' . ($puesta->nombre_mp ?: 'SIN DATO'),
                    'Personas: ' . $this->formatNumber($puesta->personas->count()),
                    'Vehículos: ' . $this->formatNumber($puesta->vehiculos->count()),
                    'Objetos: ' . $this->formatNumber($puesta->objetos->count()),
                ]
            ),
        ];
    }

    protected function hechosHoy($user, array $context, string $module, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $hoy = now()->toDateString();

        $query = Hechos::query()
            ->whereDate('fecha', $hoy)
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);

        if ($soloPropios) {
            $query->where('created_by', $user->id);
        }

        $hechos = $query->limit(15)->get();
        $lineas = [];

        if ($hechos->isEmpty()) {
            $lineas[] = $soloPropios ? 'No encontré hechos tuyos el día de hoy.' : 'No encontré hechos el día de hoy.';
        }

        foreach ($hechos as $hecho) {
            $lineas[] = 'ID ' . $hecho->id
                . ' | ' . $this->formatDate($hecho->fecha)
                . ' ' . $this->formatTime($hecho->hora)
                . ' | ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                $soloPropios ? 'Mis hechos de hoy' : 'Hechos de hoy',
                $hoy,
                $lineas
            ),
        ];
    }

    protected function buscarHechosPorPlaca($user, array $context, string $module, string $placa, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
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

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);

        if ($soloPropios) {
            $query->where('created_by', $user->id);
        }

        $hechos = $query->limit(10)->get();
        $lineas = [];

        if ($hechos->isEmpty()) {
            $lineas[] = $soloPropios
                ? "No encontré hechos tuyos con las placas {$placa}."
                : "No encontré hechos con las placas {$placa}.";
        }

        foreach ($hechos as $hecho) {
            $lineas[] = 'ID ' . $hecho->id
                . ' | ' . $this->formatDate($hecho->fecha)
                . ' ' . $this->formatTime($hecho->hora)
                . ' | ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Hechos por placas',
                'SIN PERIODO ESPECIFICADO',
                $lineas
            ),
        ];
    }

    protected function detallePorFolio($user, array $context, string $module, string $folio, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where('id', $folio);

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);

        if ($soloPropios) {
            $query->where('created_by', $user->id);
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

    protected function personalArmado($user, array $context, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);

        return $this->personalArmadoReporte($unidadId);
    }

    protected function personalActivo($user, array $context, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);

        return $this->personalActivoReporte($unidadId);
    }

    protected function personalArmadoReporte(?int $unidadId): array
    {
        $rows = PersonalAsignacion::query()
            ->join('personals', 'personal_asignacions.personal_id', '=', 'personals.id')
            ->join('armamentos', 'personal_asignacions.armamento_id', '=', 'armamentos.id')
            ->where('personal_asignacions.activo', 1)
            ->where('personals.estatus', 'ACTIVO');

        $this->applyUnitFilter($rows, 'personals.unidad_id', $unidadId);

        $rows = $rows->select([
                'personals.nombre',
                'personals.ap_paterno',
                'personals.ap_materno',
                'personals.grado',
                'personals.puesto',
                'armamentos.tipo',
                'armamentos.marca',
                'armamentos.modelo',
                'armamentos.matricula',
                'armamentos.calibre',
            ])
            ->orderBy('personals.nombre')
            ->limit(50)
            ->get();

        $lineas = ['Total de elementos armados: ' . $this->formatNumber($rows->count())];

        if ($rows->isEmpty()) {
            $lineas[] = 'No se encontraron elementos armados.';
        }

        foreach ($rows as $row) {
            $nombre = trim($row->nombre . ' ' . $row->ap_paterno . ' ' . $row->ap_materno);
            $arma = trim($row->tipo . ' ' . $row->marca . ' ' . $row->modelo);

            $lineas[] = $nombre
                . ' | ' . trim(($row->grado ?? '') . ' / ' . ($row->puesto ?? ''))
                . ' | ' . ($arma !== '' ? $arma : 'ARMA SIN DATO')
                . ' | Matrícula: ' . ($row->matricula ?? 'S/N')
                . ' | Calibre: ' . ($row->calibre ?? 'S/N');
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Relación de personal armado',
                'SIN PERIODO ESPECIFICADO',
                $lineas
            ),
        ];
    }

    protected function personalActivoReporte(?int $unidadId): array
    {
        $query = Personal::query()
            ->where('estatus', 'ACTIVO');

        $this->applyUnitFilter($query, 'unidad_id', $unidadId);

        $rows = $query->orderBy('nombre')
            ->limit(80)
            ->get(['nombre', 'ap_paterno', 'ap_materno', 'grado', 'puesto']);

        $lineas = ['Total de personal activo: ' . $this->formatNumber($rows->count())];

        if ($rows->isEmpty()) {
            $lineas[] = 'No se encontró personal activo.';
        }

        foreach ($rows as $row) {
            $nombre = trim($row->nombre . ' ' . $row->ap_paterno . ' ' . $row->ap_materno);
            $lineas[] = $nombre . ' | ' . ($row->grado ?? 'S/G') . ' | ' . ($row->puesto ?? 'S/P');
        }

        return [
            'text' => $this->renderService->renderReporte(
                $unidadId,
                'Personal activo',
                'SIN PERIODO ESPECIFICADO',
                $lineas
            ),
        ];
    }

    protected function actividadesHoy($user, array $context, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $hoy = now()->toDateString();

        $json = [
            'unidad_id' => $unidadId,
            'filtros' => [
                'fecha' => $hoy,
            ],
        ];

        return $this->actividadesOpenAI($user, ['acceso_total' => true], $json, true);
    }

    protected function actividadesPorRango($user, array $context, string $module, string $value): array
    {
        [$desde, $hasta] = $this->parseDateRange($value);

        if (!$desde || !$hasta) {
            return [
                'text' => "No pude interpretar el rango.\n\nUsa este formato:\n2026-04-01 al 2026-04-15",
            ];
        }

        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $json = [
            'unidad_id' => $unidadId,
            'filtros' => [
                'fecha_inicio' => $desde,
                'fecha_fin' => $hasta,
            ],
        ];

        return $this->actividadesOpenAI($user, ['acceso_total' => true], $json, true);
    }

    protected function operativosHoy($user, array $context, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $hoy = now()->toDateString();
        $json = [
            'unidad_id' => $unidadId,
            'filtros' => [
                'fecha' => $hoy,
            ],
        ];

        return $this->operativosOpenAI($user, ['acceso_total' => true], $json, true);
    }

    protected function operativosPorTipo($user, array $context, string $module, string $tipo): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $json = [
            'unidad_id' => $unidadId,
            'filtros' => [
                'tipo_operativo' => $tipo,
            ],
        ];

        return $this->operativosOpenAI($user, ['acceso_total' => true], $json, true);
    }

    protected function puestasHoy($user, array $context, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $context, $module);
        $hoy = now()->toDateString();
        $json = [
            'unidad_id' => $unidadId,
            'filtros' => [
                'fecha' => $hoy,
            ],
        ];

        return $this->puestasOpenAI($user, ['acceso_total' => true], $json, true);
    }

    protected function hechosBaseQuery($user, array $context, array $json, ?int $unidadId): Builder
    {
        $filtros = $this->filtros($json);
        $query = Hechos::query();

        $this->applyUnitFilter($query, 'unidad_org_id', $unidadId);
        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        $this->aplicarFiltroTipoHecho($query, $filtros);

        if (!empty($filtros['situacion'])) {
            $this->whereUpperEquals($query, 'situacion', (string) $filtros['situacion']);
        }

        if (!empty($filtros['municipio'])) {
            $this->whereUpperEquals($query, 'municipio', (string) $filtros['municipio']);
        }

        if (!empty($filtros['delegacion_id'])) {
            $query->where('delegacion_id', (int) $filtros['delegacion_id']);
        }

        return $query;
    }

    protected function normalizarConsultaOpenAI(array $json): array
    {
        $accion = (string) ($json['accion'] ?? 'no_valida');
        $aliases = [
            'estadistica_actividades' => 'actividades',
            'estadistica_operativos' => 'operativos',
            'estadistica_puestas_disposicion' => 'puestas_disposicion',
        ];

        $json['accion'] = $aliases[$accion] ?? $accion;
        $json['filtros'] = $this->filtros($json);

        if (isset($json['unidad_id']) && $json['unidad_id'] !== null && $json['unidad_id'] !== '') {
            $json['unidad_id'] = (int) $json['unidad_id'];
        } else {
            $json['unidad_id'] = null;
        }

        if (isset($json['id']) && $json['id'] !== null && $json['id'] !== '') {
            $json['id'] = (int) $json['id'];
        } else {
            $json['id'] = null;
        }

        return $json;
    }

    protected function filtros(array $json): array
    {
        $filtros = is_array($json['filtros'] ?? null) ? $json['filtros'] : [];

        return array_merge([
            'fecha' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'tipo_hecho' => null,
            'situacion' => null,
            'tipo_operativo' => null,
            'tipo_puesta' => null,
            'estatus' => null,
            'municipio' => null,
            'delegacion_id' => null,
        ], $filtros);
    }

    protected function resolveUnitIdForJson($user, array $context, array $json): ?int
    {
        $requested = isset($json['unidad_id']) && $json['unidad_id'] !== null
            ? (int) $json['unidad_id']
            : null;

        return $this->resolveUnitIdFromContext($user, $context, $requested);
    }

    protected function resolveUnitIdFromContext($user, array $context, ?int $requested): ?int
    {
        if (($context['acceso_total'] ?? false) === true) {
            return $this->isValidUnidadId($requested) ? $requested : null;
        }

        $unidadId = isset($context['unidad_id']) && $context['unidad_id'] !== null
            ? (int) $context['unidad_id']
            : ($user->unidad_id ? (int) $user->unidad_id : null);

        return $this->isValidUnidadId($unidadId) ? $unidadId : 0;
    }

    protected function resolveUnitIdFromModule($user, array $context, string $module): ?int
    {
        if (($context['acceso_total'] ?? false) === true) {
            return $this->mapModuleToUnitId($module);
        }

        $unidadId = isset($context['unidad_id']) && $context['unidad_id'] !== null
            ? (int) $context['unidad_id']
            : ($user->unidad_id ? (int) $user->unidad_id : null);

        return $this->isValidUnidadId($unidadId) ? $unidadId : 0;
    }

    protected function isValidUnidadId(?int $unidadId): bool
    {
        return in_array($unidadId, [1, 2, 3, 4, 5], true);
    }

    protected function applyUnitFilter($query, string $column, ?int $unidadId): void
    {
        if ($unidadId !== null) {
            $query->where($column, $unidadId);
        }
    }

    protected function aplicarFiltrosFechaHora($query, array $filtros, string $campoFecha = 'fecha', ?string $campoHora = 'hora')
    {
        if (!empty($filtros['fecha'])) {
            $query->whereDate($campoFecha, $filtros['fecha']);
        } else {
            if (!empty($filtros['fecha_inicio'])) {
                $query->whereDate($campoFecha, '>=', $filtros['fecha_inicio']);
            }

            if (!empty($filtros['fecha_fin'])) {
                $query->whereDate($campoFecha, '<=', $filtros['fecha_fin']);
            }
        }

        if ($campoHora) {
            if (!empty($filtros['hora_inicio'])) {
                $query->whereTime($campoHora, '>=', $filtros['hora_inicio']);
            }

            if (!empty($filtros['hora_fin'])) {
                $query->whereTime($campoHora, '<=', $filtros['hora_fin']);
            }
        }

        return $query;
    }

    protected function aplicarFiltroTipoHecho($query, array $filtros): void
    {
        if (empty($filtros['tipo_hecho'])) {
            return;
        }

        $tipos = $this->resolverTerminosTipoHecho((string) $filtros['tipo_hecho']);

        if (count($tipos) === 1) {
            $this->whereUpperEquals($query, 'tipo_hecho', $tipos[0]);
            return;
        }

        $query->where(function ($q) use ($tipos) {
            foreach ($tipos as $tipo) {
                $q->orWhereRaw("UPPER(TRIM(COALESCE(tipo_hecho, ''))) = ?", [$this->upper($tipo)]);
            }
        });
    }

    protected function resolverTerminosTipoHecho(string $tipo): array
    {
        $buscado = $this->normalizarTextoBusqueda($tipo);

        $choques = [
            'COLISIÓN POR ALCANCE',
            'COLISIÓN POR CAMBIO DE CARRIL',
            'COLISIÓN POR INVASIÓN DE CARRIL',
            'COLISIÓN POR CORTE DE CIRCULACIÓN',
            'COLISIÓN CONTRA OBJETO FIJO',
            'COLISIÓN POR MANIOBRA DE REVERSA',
            'COLISIÓN POR NO RESPETAR SEMÁFORO',
        ];

        if (in_array($buscado, ['CHOQUE', 'CHOQUES', 'COLISION', 'COLISIONES'], true)) {
            return $choques;
        }

        if ($this->startsWith($buscado, 'CHOQUEPOR')) {
            $tipo = 'COLISIÓN POR ' . trim(substr($this->upper($tipo), strlen('CHOQUE POR')));
        } elseif ($this->startsWith($buscado, 'CHOQUECONTRA')) {
            $tipo = 'COLISIÓN CONTRA ' . trim(substr($this->upper($tipo), strlen('CHOQUE CONTRA')));
        } elseif ($this->startsWith($buscado, 'CHOQUECON')) {
            $tipo = 'COLISIÓN CON ' . trim(substr($this->upper($tipo), strlen('CHOQUE CON')));
        }

        return [$this->upper($tipo)];
    }

    protected function aplicarFiltroTipoOperativo($query, array $filtros): void
    {
        if (empty($filtros['tipo_operativo'])) {
            return;
        }

        $tipos = $this->resolverTerminosTipoOperativo((string) $filtros['tipo_operativo']);

        $query->where(function ($q) use ($tipos) {
            foreach ($tipos as $tipo) {
                $q->orWhereRaw("UPPER(COALESCE(tipo_reporte, '')) LIKE ?", ['%' . $tipo . '%'])
                    ->orWhereRaw("UPPER(COALESCE(asunto, '')) LIKE ?", ['%' . $tipo . '%'])
                    ->orWhereRaw("UPPER(COALESCE(descripcion, '')) LIKE ?", ['%' . $tipo . '%'])
                    ->orWhereRaw("UPPER(COALESCE(narrativa, '')) LIKE ?", ['%' . $tipo . '%']);
            }
        });
    }

    protected function resolverTerminosTipoOperativo(string $tipo): array
    {
        $buscado = $this->normalizarTextoBusqueda($tipo);
        $terminos = [$this->upper($tipo)];

        foreach ($this->guardianesDispositivos() as $dispositivo) {
            $nombre = (string) ($dispositivo['nombre'] ?? '');
            $aliases = is_array($dispositivo['aliases'] ?? null) ? $dispositivo['aliases'] : [];
            $candidatos = array_merge([$nombre], $aliases);

            foreach ($candidatos as $candidato) {
                if ($this->normalizarTextoBusqueda((string) $candidato) === $buscado) {
                    foreach ($candidatos as $termino) {
                        $terminos[] = $this->upper((string) $termino);
                    }

                    return array_values(array_unique(array_filter($terminos)));
                }
            }
        }

        return array_values(array_unique(array_filter($terminos)));
    }

    protected function guardianesDispositivos(): array
    {
        $dispositivos = config('guardianes_camino.dispositivos', []);

        return is_array($dispositivos) ? $dispositivos : [];
    }

    protected function lesionadosQueryFromHechos(Builder $hechosQuery): Builder
    {
        return Lesionado::query()
            ->whereIn('hecho_id', (clone $hechosQuery)->select('id'));
    }

    protected function countBySituacion(Builder $query, string $situacion): int
    {
        return (clone $query)
            ->whereRaw("UPPER(TRIM(COALESCE(situacion, ''))) = ?", [$this->upper($situacion)])
            ->count();
    }

    protected function countByColumn(Builder $query, string $column, string $value): int
    {
        return (clone $query)
            ->whereRaw("UPPER(TRIM(COALESCE({$column}, ''))) = ?", [$this->upper($value)])
            ->count();
    }

    protected function whereUpperEquals($query, string $column, string $value): void
    {
        $query->whereRaw("UPPER(TRIM(COALESCE({$column}, ''))) = ?", [$this->upper($value)]);
    }

    protected function periodoTexto(array $filtros): string
    {
        $periodo = 'SIN PERIODO ESPECIFICADO';

        if (!empty($filtros['fecha'])) {
            $periodo = (string) $filtros['fecha'];
        } elseif (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) {
            $periodo = ($filtros['fecha_inicio'] ?? '...') . ' al ' . ($filtros['fecha_fin'] ?? '...');
        }

        if (!empty($filtros['hora_inicio']) || !empty($filtros['hora_fin'])) {
            $periodo .= ' de ' . ($filtros['hora_inicio'] ?? '00:00:00') . ' a ' . ($filtros['hora_fin'] ?? '23:59:59');
        }

        return $periodo;
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

    protected function buildQuickMessage(string $asunto, string $desde, string $hasta, ?int $unidadId, array $lineas): string
    {
        return $this->renderService->renderReporte(
            $unidadId,
            $asunto,
            $desde . ' al ' . $hasta,
            $lineas
        );
    }

    protected function mapModuleToUnitId(string $module): ?int
    {
        switch ($module) {
            case 'siniestros':
                return 1;
            case 'delegaciones':
                return 2;
            case 'coordinacion':
            case 'seguridad_vial':
                return 3;
            case 'carreteras':
                return 4;
            case 'vialidades':
                return 5;
            default:
                return null;
        }
    }

    protected function parseDateRange(string $value): array
    {
        $value = trim($value);

        if (preg_match('/(\d{4}-\d{2}-\d{2})\s+al\s+(\d{4}-\d{2}-\d{2})/i', $value, $matches)) {
            return [$matches[1], $matches[2]];
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})\s+a\s+(\d{4}-\d{2}-\d{2})/i', $value, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, null];
    }

    protected function normalizarPlaca(string $placa): string
    {
        $placa = mb_strtoupper(trim($placa), 'UTF-8');
        $placa = str_replace(['-', ' ', '.'], '', $placa);

        return $placa;
    }

    protected function formatNumber(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }

    protected function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    protected function formatDate($value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 'SIN FECHA';
        }

        return substr($value, 0, 10);
    }

    protected function formatTime($value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 'SIN HORA';
        }

        return substr($value, 0, 5);
    }

    protected function upper(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }

    protected function normalizarTextoBusqueda(string $value): string
    {
        $value = $this->upper($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($ascii !== false) {
            $value = $ascii;
        }

        return preg_replace('/[^A-Z0-9]+/', '', $value) ?: '';
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
