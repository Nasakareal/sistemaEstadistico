<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsApp\WhatsAppInboundService;
use App\Services\WhatsApp\WhatsAppUserResolverService;
use App\Services\WhatsApp\WhatsAppMenuService;
use App\Services\WhatsApp\WhatsAppStateService;
use App\Services\WhatsApp\WhatsAppQueryService;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppInboundService $inboundService;
    protected WhatsAppUserResolverService $userResolverService;
    protected WhatsAppMenuService $menuService;
    protected WhatsAppStateService $stateService;
    protected WhatsAppQueryService $queryService;
    protected WhatsAppCloudService $cloudService;

    public function __construct(
        WhatsAppInboundService $inboundService,
        WhatsAppUserResolverService $userResolverService,
        WhatsAppMenuService $menuService,
        WhatsAppStateService $stateService,
        WhatsAppQueryService $queryService,
        WhatsAppCloudService $cloudService
    ) {
        $this->inboundService = $inboundService;
        $this->userResolverService = $userResolverService;
        $this->menuService = $menuService;
        $this->stateService = $stateService;
        $this->queryService = $queryService;
        $this->cloudService = $cloudService;
    }

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyToken = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token !== '' && hash_equals($verifyToken, (string) $token)) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WA Cloud webhook recibido', [
            'object' => $payload['object'] ?? null,
            'entries_count' => isset($payload['entry']) && is_array($payload['entry']) ? count($payload['entry']) : 0,
        ]);

        $messages = $this->inboundService->extractMessages($payload);
        $statuses = $this->inboundService->extractStatuses($payload);

        foreach ($statuses as $status) {
            Log::info('WA estado mensaje', $status);
        }

        foreach ($messages as $message) {
            try {
                $from = $this->normalizePhone((string) ($message['from'] ?? ''));

                if ($from !== '' && $this->shouldForwardToEquinosBridge($from)) {
                    $this->forwardToEquinosWebhook($payload, $message, $from);
                    continue;
                }

                $this->processIncomingMessage($message);
            } catch (\Throwable $e) {
                Log::error('WA error procesando mensaje', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'from' => $message['from'] ?? null,
                    'type' => $message['type'] ?? null,
                ]);
            }
        }

        return response()->json(['ok' => true], 200);
    }

    protected function processIncomingMessage(array $message): void
    {
        $from = $this->normalizePhone((string) ($message['from'] ?? ''));
        $type = (string) ($message['type'] ?? '');

        if ($from === '') {
            Log::warning('WA mensaje sin remitente válido', ['message' => $message]);
            return;
        }

        $input = $this->inboundService->extractUserInput($message);

        Log::info('WA mensaje procesable', [
            'from' => $from,
            'type' => $type,
            'input_type' => $input['type'] ?? null,
            'input_value' => $input['value'] ?? null,
        ]);

        $user = $this->userResolverService->findAuthorizedUserByPhone($from);

        if (!$user) {
            $this->sendText($from, 'Número no autorizado para consultas.');
            return;
        }

        $context = $this->userResolverService->resolveContext($user);

        if (($input['type'] ?? '') === 'text') {
            $mensajeTexto = trim((string) ($input['value'] ?? ''));

            if (
                $mensajeTexto !== ''
                && !$this->isResetCommand($mensajeTexto)
                && !in_array(mb_strtolower($mensajeTexto, 'UTF-8'), ['1', '2', '3', '4', '5', '6'], true)
                && !$this->startsWith((string) ($input['value'] ?? ''), 'module:')
                && !$this->startsWith((string) ($input['value'] ?? ''), 'action:')
                && !$this->startsWith((string) ($input['value'] ?? ''), 'filter:')
                && !$this->startsWith((string) ($input['value'] ?? ''), 'period:')
            ) {
                try {
                    $openai = app(\App\Services\OpenAIService::class);
                    $json = $openai->interpretar($mensajeTexto);

                    \Log::info('OPENAI RESPUESTA', $json);

                    if (is_array($json) && isset($json['accion']) && $json['accion'] !== 'no_valida') {
                        $respondido = $this->resolverConsultaOpenAI($from, $user, $context, $json);

                        if ($respondido) {
                            return;
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Error OpenAI', ['error' => $e->getMessage()]);
                }
            }
        }

        $state = $this->stateService->getContext($from);

        if ($this->isResetCommand((string) ($input['value'] ?? ''))) {
            $this->stateService->clear($from);
            $this->startConversation($from, $user, $context);
            return;
        }

        if (empty($state)) {
            $this->startConversation($from, $user, $context);
            return;
        }

        $step = (string) ($state['step'] ?? '');

        if ($step === 'choose_module') {
            $this->handleChooseModule($from, $user, $context, $input);
            return;
        }

        if ($step === 'choose_action') {
            $this->handleChooseAction($from, $user, $context, $state, $input);
            return;
        }

        if ($step === 'choose_quick_stat_action') {
            $this->handleChooseQuickStatAction($from, $user, $context, $state, $input);
            return;
        }

        if ($step === 'choose_quick_stat_filter') {
            $this->handleChooseQuickStatFilter($from, $user, $context, $state, $input);
            return;
        }

        if ($step === 'choose_quick_stat_period') {
            $this->handleChooseQuickStatPeriod($from, $user, $context, $state, $input);
            return;
        }

        if ($step === 'await_param') {
            $this->handleAwaitParam($from, $user, $context, $state, $input);
            return;
        }

        $this->stateService->clear($from);
        $this->startConversation($from, $user, $context);
    }

    protected function startConversation(string $from, $user, array $context): void
    {
        if (($context['acceso_total'] ?? false) === true) {
            $this->stateService->putContext($from, [
                'user_id' => $user->id,
                'step' => 'choose_module',
                'module' => null,
                'action' => null,
                'scope' => $context,
            ]);

            $packet = $this->menuService->buildRootMenu($user, $context);
            $this->sendPacket($from, $packet);
            return;
        }

        $module = $context['default_module'] ?? ($context['modules'][0] ?? null);

        if (!$module) {
            $this->sendText($from, 'Tu usuario no tiene módulos disponibles para consulta.');
            return;
        }

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_action',
            'module' => $module,
            'action' => null,
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildModuleMenu($user, $context, $module);
        $this->sendPacket($from, $packet);
    }

    protected function handleChooseModule(string $from, $user, array $context, array $input): void
    {
        $module = $this->menuService->resolveModuleSelection($input, $context);

        if (!$module) {
            $packet = $this->menuService->buildRootMenu($user, $context, 'Selecciona una unidad válida.');
            $this->sendPacket($from, $packet);
            return;
        }

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_action',
            'module' => $module,
            'action' => null,
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildModuleMenu($user, $context, $module);
        $this->sendPacket($from, $packet);
    }

    protected function handleChooseAction(string $from, $user, array $context, array $state, array $input): void
    {
        $module = (string) ($state['module'] ?? '');
        $action = $this->menuService->resolveActionSelection($input, $module, $context);

        if (!$action) {
            $packet = $this->menuService->buildModuleMenu($user, $context, $module, 'Selecciona una opción válida.');
            $this->sendPacket($from, $packet);
            return;
        }

        if ($action['key'] === 'estadisticas_rapidas') {
            $this->stateService->putContext($from, [
                'user_id' => $user->id,
                'step' => 'choose_quick_stat_action',
                'module' => $module,
                'action' => null,
                'scope' => $context,
            ]);

            $packet = $this->menuService->buildQuickStatsMenu($user, $context);
            $this->sendPacket($from, $packet);
            return;
        }

        if (($action['requires_param'] ?? false) === false) {
            $result = $this->queryService->executeImmediate($user, $context, $module, $action['key']);
            $this->sendPacket($from, $result);

            $this->stateService->putContext($from, [
                'user_id' => $user->id,
                'step' => 'choose_action',
                'module' => $module,
                'action' => null,
                'scope' => $context,
            ]);

            $packet = $this->menuService->buildModuleMenu($user, $context, $module);
            $this->sendPacket($from, $packet);
            return;
        }

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'await_param',
            'module' => $module,
            'action' => $action['key'],
            'param_type' => $action['param_type'] ?? 'text',
            'scope' => $context,
        ]);

        $prompt = $this->menuService->buildActionPrompt($module, $action['key'], $context);
        $this->sendPacket($from, $prompt);
    }

    protected function handleChooseQuickStatAction(string $from, $user, array $context, array $state, array $input): void
    {
        $module = (string) ($state['module'] ?? 'siniestros');
        $action = $this->menuService->resolveActionSelection($input, $module, $context);

        if (!$action) {
            $packet = $this->menuService->buildQuickStatsMenu($user, $context, 'Selecciona una estadística válida.');
            $this->sendPacket($from, $packet);
            return;
        }

        if ($action['key'] === 'estadistica_situacion') {
            $this->stateService->putContext($from, [
                'user_id' => $user->id,
                'step' => 'choose_quick_stat_filter',
                'module' => $module,
                'action' => $action['key'],
                'filter_field' => 'situacion',
                'scope' => $context,
            ]);

            $packet = $this->menuService->buildSituacionMenu();
            $this->sendPacket($from, $packet);
            return;
        }

        if ($action['key'] === 'estadistica_tipo_hecho') {
            $this->stateService->putContext($from, [
                'user_id' => $user->id,
                'step' => 'choose_quick_stat_filter',
                'module' => $module,
                'action' => $action['key'],
                'filter_field' => 'tipo_hecho',
                'scope' => $context,
            ]);

            $packet = $this->menuService->buildTipoHechoMenu();
            $this->sendPacket($from, $packet);
            return;
        }

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_quick_stat_period',
            'module' => $module,
            'action' => $action['key'],
            'filters' => [],
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildQuickStatsPeriodMenu($action['key']);
        $this->sendPacket($from, $packet);
    }

    protected function handleChooseQuickStatFilter(string $from, $user, array $context, array $state, array $input): void
    {
        $action = (string) ($state['action'] ?? '');
        $expectedField = (string) ($state['filter_field'] ?? '');
        $filter = $this->menuService->resolveFilterSelection($input);

        if (!$filter || ($filter['field'] ?? '') !== $expectedField) {
            if ($expectedField === 'situacion') {
                $packet = $this->menuService->buildSituacionMenu('Selecciona una situación válida.');
                $this->sendPacket($from, $packet);
                return;
            }

            if ($expectedField === 'tipo_hecho') {
                $packet = $this->menuService->buildTipoHechoMenu('Selecciona un tipo de hecho válido.');
                $this->sendPacket($from, $packet);
                return;
            }

            $this->sendText($from, 'No pude identificar el filtro solicitado.');
            return;
        }

        $filters = [
            $filter['field'] => $filter['value'],
        ];

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_quick_stat_period',
            'module' => (string) ($state['module'] ?? 'siniestros'),
            'action' => $action,
            'filters' => $filters,
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildQuickStatsPeriodMenu($action);
        $this->sendPacket($from, $packet);
    }

    protected function handleChooseQuickStatPeriod(string $from, $user, array $context, array $state, array $input): void
    {
        $period = $this->menuService->resolvePeriodSelection($input);
        $action = (string) ($state['action'] ?? '');

        if (!$period || ($period['action'] ?? '') !== $action) {
            $packet = $this->menuService->buildQuickStatsPeriodMenu($action, 'Selecciona un periodo válido.');
            $this->sendPacket($from, $packet);
            return;
        }

        if (($period['period'] ?? '') === 'personalizado') {
            $packet = $this->menuService->buildQuickStatsPeriodMenu($action, 'Por ahora usa Hoy, Ayer, Este mes o Mes anterior.');
            $this->sendPacket($from, $packet);
            return;
        }

        $filters = is_array($state['filters'] ?? null) ? $state['filters'] : [];

        $result = $this->queryService->executeQuickStat(
            $user,
            $context,
            $action,
            (string) $period['period'],
            $filters
        );

        $this->sendPacket($from, $result);

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_action',
            'module' => (string) ($state['module'] ?? 'siniestros'),
            'action' => null,
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildModuleMenu($user, $context, (string) ($state['module'] ?? 'siniestros'));
        $this->sendPacket($from, $packet);
    }

    protected function handleAwaitParam(string $from, $user, array $context, array $state, array $input): void
    {
        $module = (string) ($state['module'] ?? '');
        $action = (string) ($state['action'] ?? '');
        $paramType = (string) ($state['param_type'] ?? 'text');
        $value = trim((string) ($input['value'] ?? ''));

        if ($value === '') {
            $prompt = $this->menuService->buildActionPrompt($module, $action, $context, 'Necesito un valor para continuar.');
            $this->sendPacket($from, $prompt);
            return;
        }

        $result = $this->queryService->executeWithParam(
            $user,
            $context,
            $module,
            $action,
            $paramType,
            $value
        );

        $this->sendPacket($from, $result);

        $this->stateService->putContext($from, [
            'user_id' => $user->id,
            'step' => 'choose_action',
            'module' => $module,
            'action' => null,
            'scope' => $context,
        ]);

        $packet = $this->menuService->buildModuleMenu($user, $context, $module);
        $this->sendPacket($from, $packet);
    }

    protected function resolverConsultaOpenAI(string $from, $user, array $context, array $json): bool
    {
        $packet = $this->queryService->executeOpenAI($user, $context, $json);

        if (!$packet) {
            return false;
        }

        $this->sendPacket($from, $packet);

        return true;
    }

    protected function resolverUnidadConsulta($user, array $json): ?int
    {
        $unidadSolicitada = isset($json['unidad_id']) && $json['unidad_id'] !== null
            ? (int) $json['unidad_id']
            : null;

        if ($user->hasRole('Superadmin')) {
            return $unidadSolicitada ?: ($user->unidad_id ? (int) $user->unidad_id : null);
        }

        return $user->unidad_id ? (int) $user->unidad_id : null;
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

    protected function obtenerNombreUnidad(?int $unidadId): ?string
    {
        if (!$unidadId) {
            return null;
        }

        $unidad = \App\Models\Unidad::find($unidadId);

        return $unidad ? $unidad->nombre : null;
    }

    protected function construirLineaUnidad(?int $unidadId): string
    {
        if (!$unidadId || $unidadId === 3) {
            return '';
        }

        $nombre = $this->obtenerNombreUnidad($unidadId);

        if (!$nombre) {
            return '';
        }

        return mb_strtoupper(trim($nombre), 'UTF-8') . ".\n\n";
    }

    protected function resolverEstadisticaHechos(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\Hechos::query();

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        if (!empty($filtros['tipo_hecho'])) {
            $query->where('tipo_hecho', $filtros['tipo_hecho']);
        }

        if (!empty($filtros['situacion'])) {
            $query->where('situacion', $filtros['situacion']);
        }

        $hechos = (clone $query)->count();
        $lesionados = \App\Models\Lesionado::query()
            ->whereIn('hecho_id', (clone $query)->select('id'))
            ->count();
        $fallecidos = \App\Models\Lesionado::query()
            ->whereIn('hecho_id', (clone $query)->select('id'))
            ->where('tipo_lesion', 'LIKE', '%fallecid%')
            ->count();
        $resueltos = (clone $query)->where('situacion', 'RESUELTO')->count();
        $pendientes = (clone $query)->where('situacion', 'PENDIENTE')->count();
        $turnados = (clone $query)->where('situacion', 'TURNADO')->count();
        $reportes = (clone $query)->where('situacion', 'REPORTE')->count();

        $tipos = (clone $query)
            ->selectRaw('tipo_hecho, COUNT(*) as total')
            ->groupBy('tipo_hecho')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $periodo = 'SIN PERIODO ESPECIFICADO';

        if (!empty($filtros['fecha'])) {
            $periodo = $filtros['fecha'];
        } elseif (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) {
            $periodo = ($filtros['fecha_inicio'] ?? '...') . ' al ' . ($filtros['fecha_fin'] ?? '...');
        }

        $texto = "GUARDIA CIVIL\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.\n\n";
        $texto .= $this->construirLineaUnidad($unidadId);
        $texto .= "ASUNTO: Estadística rápida de siniestros.\n\n";
        $texto .= "PERIODO: {$periodo}.\n\n";
        $texto .= "RESULTADO:\n";
        $texto .= "- Hechos: {$hechos}\n";
        $texto .= "- Lesionados: {$lesionados}\n";
        $texto .= "- Fallecidos: {$fallecidos}\n";
        $texto .= "- Resueltos: {$resueltos}\n";
        $texto .= "- Pendientes: {$pendientes}\n";
        $texto .= "- Turnado: {$turnados}\n";
        $texto .= "- Reporte: {$reportes}\n";

        foreach ($tipos as $tipo) {
            $texto .= "- {$tipo->tipo_hecho}: {$tipo->total}\n";
        }

        $texto .= "\nPARA CONOCIMIENTO DE LA SUPERIORIDAD.";

        $this->sendText($from, $texto);
        return true;
    }

    protected function resolverDetalleHecho(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $hechoId = $json['id'] ?? null;

        if (!$hechoId) {
            $this->sendText($from, 'Hecho no encontrado');
            return true;
        }

        $query = \App\Models\Hechos::query()->where('id', $hechoId);

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $hecho = $query->first();

        if (!$hecho) {
            $this->sendText($from, 'Hecho no encontrado');
            return true;
        }

        $texto = "HECHO {$hecho->id}\n";
        $texto .= "FECHA: {$hecho->fecha}\n";
        $texto .= "HORA: {$hecho->hora}\n";
        $texto .= "TIPO: {$hecho->tipo_hecho}\n";
        $texto .= "SITUACIÓN: {$hecho->situacion}\n";
        $texto .= "MUNICIPIO: {$hecho->municipio}\n";
        $texto .= "LUGAR: " . trim(($hecho->calle ?? '') . ' ' . ($hecho->entre_calles ?? '')) . "\n";
        $texto .= "PERITO: {$hecho->perito}";

        $this->sendText($from, $texto);
        return true;
    }

    protected function resolverPersonalArmado(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);

        $rows = \App\Models\PersonalAsignacion::query()
            ->join('personals', 'personal_asignacions.personal_id', '=', 'personals.id')
            ->join('armamentos', 'personal_asignacions.armamento_id', '=', 'armamentos.id')
            ->where('personal_asignacions.activo', 1)
            ->when($unidadId, fn ($q) => $q->where('personals.unidad_id', $unidadId))
            ->where('personals.estatus', 'ACTIVO')
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
            ->orderBy('personals.ap_paterno')
            ->orderBy('personals.ap_materno')
            ->orderBy('personals.nombre')
            ->get();

        if ($rows->isEmpty()) {
            $this->sendText($from, 'No se encontraron elementos armados.');
            return true;
        }

        $texto = "RELACIÓN DE PERSONAL ARMADO\n\n";

        foreach ($rows as $row) {
            $nombre = \App\Models\Personal::formarNombreCompleto($row->nombre, $row->ap_paterno, $row->ap_materno);
            $arma = trim($row->tipo . ' ' . $row->marca . ' ' . $row->modelo);

            $texto .= "- {$nombre}\n";
            $texto .= "  {$row->grado} / {$row->puesto}\n";
            $texto .= "  {$arma}\n";
            $texto .= "  Matrícula: {$row->matricula} | Calibre: {$row->calibre}\n\n";
        }

        $this->sendText($from, trim($texto));
        return true;
    }

    protected function resolverPersonalActivo(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);

        $rows = \App\Models\Personal::query()
            ->when($unidadId, fn ($q) => $q->where('unidad_id', $unidadId))
            ->where('estatus', 'ACTIVO')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get(['nombre', 'ap_paterno', 'ap_materno', 'grado', 'puesto']);

        if ($rows->isEmpty()) {
            $this->sendText($from, 'No se encontró personal activo.');
            return true;
        }

        $texto = "PERSONAL ACTIVO\n\n";

        foreach ($rows as $row) {
            $nombre = $row->nombre_completo;
            $texto .= "- {$nombre} | {$row->grado} | {$row->puesto}\n";
        }

        $this->sendText($from, trim($texto));
        return true;
    }

    protected function resolverEstadisticaActividades(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\Actividad::query();

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        $total = $query->count();

        $this->sendText($from, "TOTAL DE ACTIVIDADES: {$total}");
        return true;
    }

    protected function resolverListaActividades(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\Actividad::query();

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        $rows = $query->orderByDesc('fecha')->orderByDesc('hora')->limit(20)->get();

        if ($rows->isEmpty()) {
            $this->sendText($from, 'No se encontraron actividades.');
            return true;
        }

        $texto = "LISTA DE ACTIVIDADES\n\n";

        foreach ($rows as $row) {
            $texto .= "- {$row->fecha} {$row->hora} | {$row->nombre}\n";
        }

        $this->sendText($from, trim($texto));
        return true;
    }

    protected function resolverEstadisticaOperativos(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\OperativoDispositivo::query()
            ->aprobados();

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        if (!empty($filtros['tipo_operativo'])) {
            $query->where('tipo_reporte', $filtros['tipo_operativo']);
        }

        $total = (clone $query)->count();
        $vehiculosInspeccionados = (clone $query)->sum('vehiculos_inspeccionados');
        $personasInspeccionadas = (clone $query)->sum('personas_inspeccionadas');
        $vehiculosImpactados = (clone $query)->sum('vehiculos_impactados');
        $personasImpactadas = (clone $query)->sum('personas_impactadas');
        $estadoFuerza = (clone $query)->sum('estado_fuerza_participante');
        $kilometros = (clone $query)->sum('kilometros_recorridos');

        $texto = "ESTADÍSTICA DE OPERATIVOS\n\n";
        $texto .= "- Registros: {$total}\n";
        $texto .= "- Vehículos inspeccionados: {$vehiculosInspeccionados}\n";
        $texto .= "- Personas inspeccionadas: {$personasInspeccionadas}\n";
        $texto .= "- Vehículos impactados: {$vehiculosImpactados}\n";
        $texto .= "- Personas impactadas: {$personasImpactadas}\n";
        $texto .= "- Estado de fuerza: {$estadoFuerza}\n";
        $texto .= "- Kilómetros recorridos: {$kilometros}";

        $this->sendText($from, $texto);
        return true;
    }

    protected function resolverListaOperativos(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\OperativoDispositivo::query()
            ->aprobados();

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha', 'hora');

        if (!empty($filtros['tipo_operativo'])) {
            $query->where('tipo_reporte', $filtros['tipo_operativo']);
        }

        $rows = $query->orderByDesc('fecha')->orderByDesc('hora')->limit(20)->get();

        if ($rows->isEmpty()) {
            $this->sendText($from, 'No se encontraron operativos.');
            return true;
        }

        $texto = "LISTA DE OPERATIVOS\n\n";

        foreach ($rows as $row) {
            $texto .= "- {$row->fecha} {$row->hora} | {$row->tipo_reporte} | {$row->asunto}\n";
        }

        $this->sendText($from, trim($texto));
        return true;
    }

    protected function resolverEstadisticaPuestas(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\PuestaDisposicion::query();

        if ($unidadId) {
            $query->where('unidad_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha_puesta', 'hora_puesta');

        if (!empty($filtros['tipo_puesta'])) {
            $query->where('tipo_puesta', $filtros['tipo_puesta']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('estatus', $filtros['estatus']);
        }

        $total = $query->count();

        $this->sendText($from, "TOTAL DE PUESTAS A DISPOSICIÓN: {$total}");
        return true;
    }

    protected function resolverListaPuestas(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $filtros = $json['filtros'] ?? [];

        $query = \App\Models\PuestaDisposicion::query();

        if ($unidadId) {
            $query->where('unidad_id', $unidadId);
        }

        $this->aplicarFiltrosFechaHora($query, $filtros, 'fecha_puesta', 'hora_puesta');

        if (!empty($filtros['tipo_puesta'])) {
            $query->where('tipo_puesta', $filtros['tipo_puesta']);
        }

        if (!empty($filtros['estatus'])) {
            $query->where('estatus', $filtros['estatus']);
        }

        $rows = $query->orderByDesc('fecha_puesta')->orderByDesc('hora_puesta')->limit(20)->get();

        if ($rows->isEmpty()) {
            $this->sendText($from, 'No se encontraron puestas a disposición.');
            return true;
        }

        $texto = "LISTA DE PUESTAS A DISPOSICIÓN\n\n";

        foreach ($rows as $row) {
            $texto .= "- {$row->fecha_puesta} {$row->hora_puesta} | {$row->numero_puesta} | {$row->tipo_puesta} | {$row->estatus}\n";
        }

        $this->sendText($from, trim($texto));
        return true;
    }

    protected function resolverDetallePuesta(string $from, $user, array $context, array $json): bool
    {
        $unidadId = $this->resolverUnidadConsulta($user, $json);
        $puestaId = $json['id'] ?? null;

        if (!$puestaId) {
            $this->sendText($from, 'Puesta a disposición no encontrada');
            return true;
        }

        $query = \App\Models\PuestaDisposicion::query()->where('id', $puestaId);

        if ($unidadId) {
            $query->where('unidad_id', $unidadId);
        }

        $puesta = $query->first();

        if (!$puesta) {
            $this->sendText($from, 'Puesta a disposición no encontrada');
            return true;
        }

        $texto = "PUESTA A DISPOSICIÓN {$puesta->id}\n";
        $texto .= "NÚMERO: {$puesta->numero_puesta}\n";
        $texto .= "FECHA: {$puesta->fecha_puesta}\n";
        $texto .= "HORA: {$puesta->hora_puesta}\n";
        $texto .= "TIPO: {$puesta->tipo_puesta}\n";
        $texto .= "ESTATUS: {$puesta->estatus}\n";
        $texto .= "POLICÍA: {$puesta->nombre_policia}\n";
        $texto .= "MP: {$puesta->nombre_mp}";

        $this->sendText($from, $texto);
        return true;
    }

    protected function sendPacket(string $to, array $packet): void
    {
        if (!empty($packet['text'])) {
            $this->sendText($to, (string) $packet['text']);
        }

        if (!empty($packet['interactive']) && is_array($packet['interactive'])) {
            $this->sendInteractive($to, $packet['interactive']);
        }

        if (!empty($packet['images']) && is_array($packet['images'])) {
            foreach ($packet['images'] as $imageUrl) {
                if (!empty($imageUrl)) {
                    $this->sendImage($to, (string) $imageUrl);
                }
            }
        }
    }

    protected function sendText(string $to, string $text): array
    {
        return $this->cloudService->sendText($to, $text);
    }

    protected function sendInteractive(string $to, array $interactive): array
    {
        return $this->cloudService->sendInteractive($to, $interactive);
    }

    protected function sendImage(string $to, string $imageUrl): array
    {
        return $this->cloudService->sendImage($to, $imageUrl);
    }

    protected function getWhatsAppConfig(): array
    {
        $graphVersion = (string) config('services.whatsapp.graph_version', 'v19.0');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $token = (string) (
            config('services.whatsapp.token')
            ?: config('services.whatsapp.access_token')
            ?: env('WHATSAPP_ACCESS_TOKEN')
        );

        return [
            'graph_version' => $graphVersion !== '' ? $graphVersion : 'v19.0',
            'phone_number_id' => $phoneNumberId,
            'token' => $token,
        ];
    }

    protected function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }

    protected function shouldForwardToEquinosBridge(string $from): bool
    {
        if (!filter_var(config('services.whatsapp.equinos_bridge.enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $phones = $this->normalizeEquinosBridgePhones((array) config('services.whatsapp.equinos_bridge.phones', []));

        return in_array($from, $phones, true);
    }

    protected function normalizeEquinosBridgePhones(array $phones): array
    {
        $normalized = [];

        foreach ($phones as $phone) {
            $value = $this->normalizePhone((string) $phone);

            if ($value === '') {
                continue;
            }

            $normalized[] = $value;

            foreach ($this->mexicoPhoneVariants($value) as $variant) {
                $normalized[] = $variant;
            }
        }

        return array_values(array_unique($normalized));
    }

    protected function mexicoPhoneVariants(string $phone): array
    {
        if (preg_match('/^521(\d{10})$/', $phone, $matches)) {
            return ['52' . $matches[1]];
        }

        if (preg_match('/^52(\d{10})$/', $phone, $matches)) {
            return ['521' . $matches[1]];
        }

        return [];
    }

    protected function forwardToEquinosWebhook(array $payload, array $message, string $from): bool
    {
        $url = trim((string) config('services.whatsapp.equinos_bridge.url', ''));

        if ($url === '') {
            Log::warning('WA Equinos bridge sin URL configurada', [
                'from' => $from,
            ]);

            return false;
        }

        try {
            $response = Http::timeout((int) config('services.whatsapp.equinos_bridge.timeout', 60))
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            Log::info('WA Equinos bridge response', [
                'from' => $from,
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->json(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WA Equinos bridge error', [
                'from' => $from,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function isResetCommand(string $value): bool
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');

        return in_array($value, ['MENU', 'MENÚ', 'INICIO', 'HOLA', 'RESET'], true);
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
