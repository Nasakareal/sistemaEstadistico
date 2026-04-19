<?php

namespace App\Services\WhatsApp;

use App\Models\Actividad;
use App\Models\Hechos;
use App\Models\Lesionado;
use App\Models\OperativoDispositivo;
use App\Models\Personal;
use App\Models\PersonalAsignacion;
use App\Models\PuestaDisposicion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsAppQueryService
{
    protected WhatsAppRenderService $renderService;
    protected WhatsAppMenuService $menuService;

    public function __construct(WhatsAppRenderService $renderService, WhatsAppMenuService $menuService)
    {
        $this->renderService = $renderService;
        $this->menuService = $menuService;
    }

    public function executeImmediate($user, array $context, string $module, string $action): array
    {
        if ($action === 'hechos_hoy') {
            return $this->hechosHoy($user, $module, false);
        }

        if ($action === 'mis_hechos_hoy') {
            return $this->hechosHoy($user, $module, true);
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
            return $this->personalArmado($user, $module);
        }

        if ($action === 'personal_activo') {
            return $this->personalActivo($user, $module);
        }

        if ($action === 'actividades_hoy') {
            return $this->actividadesHoy($user, $module);
        }

        if ($action === 'operativos_hoy') {
            return $this->operativosHoy($user, $module);
        }

        if ($action === 'puestas_hoy') {
            return $this->puestasHoy($user, $module);
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
            return $this->buscarHechosPorPlaca($user, $module, $value, false);
        }

        if ($action === 'mis_hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $module, $value, true);
        }

        if ($action === 'detalle_folio') {
            return $this->detallePorFolio($user, $module, $value, false);
        }

        if ($action === 'mi_detalle_folio') {
            return $this->detallePorFolio($user, $module, $value, true);
        }

        if ($action === 'actividades_rango') {
            return $this->actividadesPorRango($user, $module, $value);
        }

        if ($action === 'operativos_tipo') {
            return $this->operativosPorTipo($user, $module, $value);
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

        $unidadId = $this->resolveUnitIdFromContext($context, 'siniestros');

        $query = Hechos::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->where('unidad_org_id', $unidadId);

        if (!empty($context['solo_propios'])) {
            $query->where('created_by', $user->id);
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

        $lesionadosQuery = Lesionado::query()
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id')
            ->whereDate('hechos.fecha', '>=', $desde)
            ->whereDate('hechos.fecha', '<=', $hasta)
            ->where('hechos.unidad_org_id', $unidadId);

        if (!empty($context['solo_propios'])) {
            $lesionadosQuery->where('hechos.created_by', $user->id);
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
                    'siniestros',
                    [
                        '- Lesionados: ' . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        '- Hechos relacionados: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
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
                    'siniestros',
                    [
                        '- Fallecidos: ' . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        '- Hechos relacionados: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
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
                    'siniestros',
                    [
                        '- Hechos con motocicletas: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        '- Lesionados: ' . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        '- Fallecidos: ' . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        '- Resueltos: ' . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                        '- Pendientes: ' . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                        '- Turnado: ' . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                        '- Reporte: ' . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
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
                    'siniestros',
                    [
                        "- Situación: {$situacion}",
                        '- Hechos: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        '- Lesionados: ' . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        '- Fallecidos: ' . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
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
                    'siniestros',
                    [
                        "- Tipo de hecho: {$tipoHecho}",
                        '- Hechos: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                        '- Lesionados: ' . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                        '- Fallecidos: ' . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                        '- Resueltos: ' . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                        '- Pendientes: ' . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                        '- Turnado: ' . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                        '- Reporte: ' . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
                    ]
                ),
            ];
        }

        $lineasTop = [];

        foreach ($tiposTop as $row) {
            $lineasTop[] = '- ' . $row->label . ': ' . str_pad((string) $row->total, 2, '0', STR_PAD_LEFT);
        }

        return [
            'text' => $this->buildQuickMessage(
                'Estadística rápida de siniestros',
                $desde,
                $hasta,
                'siniestros',
                array_merge([
                    '- Hechos: ' . str_pad((string) $hechos, 2, '0', STR_PAD_LEFT),
                    '- Lesionados: ' . str_pad((string) $lesionados, 2, '0', STR_PAD_LEFT),
                    '- Fallecidos: ' . str_pad((string) $fallecidos, 2, '0', STR_PAD_LEFT),
                    '- Resueltos: ' . str_pad((string) $resueltos, 2, '0', STR_PAD_LEFT),
                    '- Pendientes: ' . str_pad((string) $pendientes, 2, '0', STR_PAD_LEFT),
                    '- Turnado: ' . str_pad((string) $turnados, 2, '0', STR_PAD_LEFT),
                    '- Reporte: ' . str_pad((string) $reportes, 2, '0', STR_PAD_LEFT),
                ], $lineasTop)
            ),
        ];
    }

    protected function hechosHoy($user, string $module, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $query = Hechos::query()
            ->where('unidad_org_id', $unidadId)
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('created_by', $user->id);
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
                . ' | FECHA: ' . (string) $hecho->fecha
                . ' ' . substr((string) $hecho->hora, 0, 5)
                . ' | TIPO: ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ESTATUS: ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function buscarHechosPorPlaca($user, string $module, string $placa, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);
        $placaNormalizada = $this->normalizarPlaca($placa);

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where('unidad_org_id', $unidadId)
            ->whereHas('vehiculos', function ($q) use ($placaNormalizada) {
                $q->whereRaw(
                    "REPLACE(REPLACE(REPLACE(UPPER(placas), '-', ''), ' ', ''), '.', '') = ?",
                    [$placaNormalizada]
                );
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('created_by', $user->id);
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
                . ' | FECHA: ' . (string) $hecho->fecha
                . ' ' . substr((string) $hecho->hora, 0, 5)
                . ' | TIPO: ' . ($hecho->tipo_hecho ?: 'SIN TIPO')
                . ' | ESTATUS: ' . ($hecho->situacion ?: 'SIN ESTATUS');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function detallePorFolio($user, string $module, string $folio, bool $soloPropios): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where('unidad_org_id', $unidadId)
            ->where('id', $folio);

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

    protected function personalArmado($user, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = PersonalAsignacion::query()
            ->join('personals', 'personal_asignacions.personal_id', '=', 'personals.id')
            ->join('armamentos', 'personal_asignacions.armamento_id', '=', 'armamentos.id')
            ->where('personal_asignacions.activo', 1)
            ->where('personals.estatus', 'ACTIVO')
            ->where('personals.unidad_id', $unidadId)
            ->select([
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
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => 'No se encontraron elementos armados.',
            ];
        }

        $lineas = [];
        $lineas[] = 'RELACIÓN DE PERSONAL ARMADO';
        $lineas[] = '';

        foreach ($rows as $row) {
            $nombre = trim($row->nombre . ' ' . $row->ap_paterno . ' ' . $row->ap_materno);
            $arma = trim($row->tipo . ' ' . $row->marca . ' ' . $row->modelo);

            $lineas[] = '- ' . $nombre;
            $lineas[] = '  ' . trim(($row->grado ?? '') . ' / ' . ($row->puesto ?? ''));
            $lineas[] = '  ' . $arma;
            $lineas[] = '  Matrícula: ' . ($row->matricula ?? 'S/N') . ' | Calibre: ' . ($row->calibre ?? 'S/N');
            $lineas[] = '';
        }

        return [
            'text' => trim(implode("\n", $lineas)),
        ];
    }

    protected function personalActivo($user, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = Personal::query()
            ->where('unidad_id', $unidadId)
            ->where('estatus', 'ACTIVO')
            ->orderBy('nombre')
            ->get(['nombre', 'ap_paterno', 'ap_materno', 'grado', 'puesto']);

        if ($rows->isEmpty()) {
            return [
                'text' => 'No se encontró personal activo.',
            ];
        }

        $lineas = [];
        $lineas[] = 'PERSONAL ACTIVO';
        $lineas[] = '';

        foreach ($rows as $row) {
            $nombre = trim($row->nombre . ' ' . $row->ap_paterno . ' ' . $row->ap_materno);
            $lineas[] = '- ' . $nombre . ' | ' . ($row->grado ?? 'S/G') . ' | ' . ($row->puesto ?? 'S/P');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function actividadesHoy($user, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = Actividad::query()
            ->where('unidad_org_id', $unidadId)
            ->whereDate('fecha', now()->toDateString())
            ->orderByDesc('hora')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => 'No se encontraron actividades el día de hoy.',
            ];
        }

        $lineas = [];
        $lineas[] = 'ACTIVIDADES DE HOY';
        $lineas[] = '';

        foreach ($rows as $row) {
            $lineas[] = '- ' . $row->fecha . ' ' . substr((string) $row->hora, 0, 5) . ' | ' . ($row->nombre ?: 'SIN NOMBRE');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function actividadesPorRango($user, string $module, string $value): array
    {
        [$desde, $hasta] = $this->parseDateRange($value);

        if (!$desde || !$hasta) {
            return [
                'text' => "No pude interpretar el rango.\n\nUsa este formato:\n2026-04-01 al 2026-04-15",
            ];
        }

        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = Actividad::query()
            ->where('unidad_org_id', $unidadId)
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => "No se encontraron actividades del {$desde} al {$hasta}.",
            ];
        }

        $lineas = [];
        $lineas[] = "ACTIVIDADES DEL {$desde} AL {$hasta}";
        $lineas[] = '';

        foreach ($rows as $row) {
            $lineas[] = '- ' . $row->fecha . ' ' . substr((string) $row->hora, 0, 5) . ' | ' . ($row->nombre ?: 'SIN NOMBRE');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function operativosHoy($user, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = OperativoDispositivo::query()
            ->where('unidad_org_id', $unidadId)
            ->whereDate('fecha', now()->toDateString())
            ->orderByDesc('hora')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => 'No se encontraron operativos el día de hoy.',
            ];
        }

        $lineas = [];
        $lineas[] = 'OPERATIVOS DE HOY';
        $lineas[] = '';

        foreach ($rows as $row) {
            $lineas[] = '- ' . $row->fecha . ' ' . substr((string) $row->hora, 0, 5)
                . ' | ' . ($row->tipo_reporte ?: 'SIN TIPO')
                . ' | ' . ($row->asunto ?: 'SIN ASUNTO');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function operativosPorTipo($user, string $module, string $tipo): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);
        $tipoNormalizado = mb_strtoupper(trim($tipo), 'UTF-8');

        $rows = OperativoDispositivo::query()
            ->where('unidad_org_id', $unidadId)
            ->where(function ($q) use ($tipoNormalizado) {
                $q->whereRaw('UPPER(COALESCE(tipo_reporte, "")) = ?', [$tipoNormalizado])
                    ->orWhereRaw('UPPER(COALESCE(asunto, "")) LIKE ?', ['%' . $tipoNormalizado . '%'])
                    ->orWhereRaw('UPPER(COALESCE(descripcion, "")) LIKE ?', ['%' . $tipoNormalizado . '%']);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => "No se encontraron operativos del tipo {$tipo}.",
            ];
        }

        $lineas = [];
        $lineas[] = "OPERATIVOS TIPO {$tipoNormalizado}";
        $lineas[] = '';

        foreach ($rows as $row) {
            $lineas[] = '- ' . $row->fecha . ' ' . substr((string) $row->hora, 0, 5)
                . ' | ' . ($row->tipo_reporte ?: 'SIN TIPO')
                . ' | ' . ($row->asunto ?: 'SIN ASUNTO');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function puestasHoy($user, string $module): array
    {
        $unidadId = $this->resolveUnitIdFromModule($user, $module);

        $rows = PuestaDisposicion::query()
            ->where('unidad_id', $unidadId)
            ->whereDate('fecha_puesta', now()->toDateString())
            ->orderByDesc('hora_puesta')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'text' => 'No se encontraron puestas a disposición el día de hoy.',
            ];
        }

        $lineas = [];
        $lineas[] = 'PUESTAS A DISPOSICIÓN DE HOY';
        $lineas[] = '';

        foreach ($rows as $row) {
            $lineas[] = '- ' . $row->fecha_puesta . ' ' . substr((string) $row->hora_puesta, 0, 5)
                . ' | ' . ($row->numero_puesta ?: 'SIN NÚMERO')
                . ' | ' . ($row->tipo_puesta ?: 'SIN TIPO')
                . ' | ' . ($row->estatus ?: 'SIN ESTATUS');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
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

    protected function buildQuickMessage(string $asunto, string $desde, string $hasta, string $module, array $lineas): string
    {
        $unidadLine = $this->buildUnidadLine($module);

        return implode("\n", array_merge([
            'GUARDIA CIVIL',
            'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.',
            '',
        ], $unidadLine !== '' ? [$unidadLine, ''] : [], [
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

    protected function buildUnidadLine(string $module): string
    {
        $unidad = $this->moduleUnidadNombre($module);

        if ($unidad === '' || $module === 'coordinacion' || $module === 'seguridad_vial') {
            return '';
        }

        return $unidad . '.';
    }

    protected function moduleUnidadNombre(string $module): string
    {
        switch ($module) {
            case 'siniestros':
                return 'UNIDAD DE ATENCIÓN A SINIESTROS';
            case 'delegaciones':
                return 'UNIDAD DE DELEGACIONES';
            case 'carreteras':
                return 'PROTECCIÓN A CARRETERAS';
            case 'vialidades':
                return 'PROTECCIÓN A VIALIDADES URBANAS';
            case 'fomento':
                return 'FOMENTO A LA CULTURA VIAL';
            default:
                return '';
        }
    }

    protected function resolveUnitIdFromContext(array $context, string $fallbackModule): ?int
    {
        $module = $context['default_module'] ?? $fallbackModule;

        return $this->mapModuleToUnitId((string) $module);
    }

    protected function resolveUnitIdFromModule($user, string $module): ?int
    {
        $mapped = $this->mapModuleToUnitId($module);

        if ($mapped !== null) {
            return $mapped;
        }

        return $user->unidad_id ? (int) $user->unidad_id : null;
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
            case 'fomento':
                return 6;
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
}
