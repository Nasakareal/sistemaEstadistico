<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function __construct(
        WhatsAppInboundService $inboundService,
        WhatsAppUserResolverService $userResolverService,
        WhatsAppMenuService $menuService,
        WhatsAppStateService $stateService,
        WhatsAppQueryService $queryService
    ) {
        $this->inboundService = $inboundService;
        $this->userResolverService = $userResolverService;
        $this->menuService = $menuService;
        $this->stateService = $stateService;
        $this->queryService = $queryService;
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

        if (($input['type'] ?? '') === 'text') {

            $mensajeTexto = trim((string) ($input['value'] ?? ''));

            if ($mensajeTexto !== '') {

                try {
                    $openai = app(\App\Services\OpenAIService::class);
                    $resultado = $openai->interpretar($mensajeTexto);

                    $texto = $resultado['output'][0]['content'][0]['text'] ?? null;
                    $json = json_decode($texto, true);

                    if ($json && isset($json['accion'])) {

                        switch ($json['accion']) {

                            case 'contar_hechos':
                                $fecha = now();

                                if (($json['filtros']['fecha'] ?? '') === 'ayer') {
                                    $fecha = now()->subDay();
                                }

                                if (($json['filtros']['fecha'] ?? '') === 'antier') {
                                    $fecha = now()->subDays(2);
                                }

                                $total = \App\Models\Hechos::whereDate('fecha', $fecha)->count();
                                $this->sendText($from, "TOTAL DE HECHOS HOY: " . $total);
                                return;

                            case 'detalle_hecho':
                                $hecho = \App\Models\Hechos::find($json['id']);

                                if (!$hecho) {
                                    $this->sendText($from, "Hecho no encontrado");
                                    return;
                                }

                                $this->sendText($from, "HECHO {$hecho->id} - {$hecho->tipo_hecho}");
                                return;
                        }
                    }

                } catch (\Throwable $e) {
                    \Log::error('Error OpenAI', ['error' => $e->getMessage()]);
                }
            }
        }

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
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendText sin configuración', ['to' => $to]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $text,
                ],
            ]);

        $json = $response->json();

        Log::info('WA sendText response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
    }

    protected function sendInteractive(string $to, array $interactive): array
    {
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendInteractive sin configuración', ['to' => $to]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive,
            ]);

        $json = $response->json();

        Log::info('WA sendInteractive response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
    }

    protected function sendImage(string $to, string $imageUrl): array
    {
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendImage sin configuración', [
                'to' => $to,
                'imageUrl' => $imageUrl,
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                ],
            ]);

        $json = $response->json();

        Log::info('WA sendImage response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
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

    protected function isResetCommand(string $value): bool
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');

        return in_array($value, ['MENU', 'MENÚ', 'INICIO', 'HOLA', 'RESET'], true);
    }
}
