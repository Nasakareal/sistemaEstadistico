<?php

namespace App\Services\WhatsApp;

use App\Models\ReporteCiudadanoSiniestro;
use App\Services\WhatsAppCloudService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CitizenIncidentReportService
{
    private const STATE_TTL_MINUTES = 60;

    protected WhatsAppCloudService $cloudService;

    public function __construct(WhatsAppCloudService $cloudService)
    {
        $this->cloudService = $cloudService;
    }

    public function enabled(): bool
    {
        return (bool) config('services.whatsapp.citizen_reports.enabled', true);
    }

    public function handle(string $from, array $message, array $input): void
    {
        if ($this->isDuplicate($message)) {
            return;
        }

        $state = $this->state($from);
        $value = trim((string) ($input['value'] ?? ''));
        $normalized = $this->normalizeText($value);

        if (in_array($normalized, ['cancelar', 'salir'], true)) {
            $this->clear($from);
            $this->cloudService->sendText(
                $from,
                'El reporte fue cancelado. Escribe cualquier mensaje si deseas iniciar uno nuevo.'
            );
            return;
        }

        if (in_array($normalized, ['menu', 'menú', 'inicio'], true)) {
            $this->clear($from);
            $this->showMenu($from);
            return;
        }

        if (empty($state)) {
            $this->showMenu($from);
            return;
        }

        switch ((string) ($state['step'] ?? '')) {
            case 'await_start':
                $this->handleStart($from, $normalized);
                return;

            case 'await_location':
                $this->handleLocation($from, $message, $input, $state);
                return;

            case 'await_type':
                $this->handleType($from, $input, $state);
                return;

            case 'await_vehicles':
                $this->handleFreeText($from, $input, $state, 'vehiculos', 'await_injured');
                return;

            case 'await_injured':
                $this->handleInjured($from, $input, $state);
                return;

            case 'await_risks':
                $this->handleRisks($from, $input, $state);
                return;

            case 'await_confirmation':
                $this->handleConfirmation($from, $input, $state);
                return;
        }

        $this->clear($from);
        $this->showMenu($from);
    }

    protected function showMenu(string $from, ?string $notice = null): void
    {
        $this->putState($from, [
            'step' => 'await_start',
            'data' => [],
        ]);

        $text = $notice ? trim($notice) . "\n\n" : '';
        $text .= "Hola. Este es el canal de reportes ciudadanos de Seguridad Vial.\n\n"
            . "Aquí puedes reportar un siniestro de tránsito. Si hay peligro inmediato o una emergencia médica, llama también al 911.\n\n"
            . "Tu reporte será enviado a las unidades para su atención y validación.";

        $this->cloudService->sendText($from, $text);
        $this->cloudService->sendInteractive($from, [
            'type' => 'button',
            'body' => ['text' => 'Selecciona una opción para continuar.'],
            'footer' => ['text' => 'No compartas identificaciones ni datos médicos sensibles.'],
            'action' => [
                'buttons' => [[
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'citizen:start',
                        'title' => 'Reportar siniestro',
                    ],
                ]],
            ],
        ]);
    }

    protected function handleStart(string $from, string $value): void
    {
        if (!in_array($value, ['citizen:start', 'reportar', 'reportar siniestro', '1'], true)) {
            $this->showMenu($from, 'Para iniciar, toca el botón Reportar siniestro.');
            return;
        }

        $this->putState($from, [
            'step' => 'await_location',
            'data' => [],
        ]);

        $this->cloudService->sendText(
            $from,
            "1 de 5 — Ubicación\n\nEn WhatsApp selecciona Ubicación > Enviar tu ubicación actual. No selecciones Ubicación en tiempo real.\n\nTambién puedes escribir la dirección exacta, incluyendo calle, cruces, colonia, municipio y alguna referencia.\n\nEscribe CANCELAR en cualquier momento para salir."
        );
    }

    protected function handleLocation(string $from, array $message, array $input, array $state): void
    {
        $data = (array) ($state['data'] ?? []);
        $location = is_array($message['location'] ?? null) ? $message['location'] : [];

        if ((string) ($message['type'] ?? '') === 'unsupported') {
            $this->cloudService->sendText(
                $from,
                'WhatsApp no permite procesar la ubicación en tiempo real mediante este canal. Selecciona Ubicación > Enviar tu ubicación actual, o escribe la dirección exacta.'
            );
            return;
        }

        if (!empty($location)) {
            $latitude = isset($location['latitude']) ? (float) $location['latitude'] : null;
            $longitude = isset($location['longitude']) ? (float) $location['longitude'] : null;

            if (!$this->validCoordinates($latitude, $longitude)) {
                $this->cloudService->sendText($from, 'La ubicación recibida no es válida. Compártela nuevamente o escribe la dirección exacta.');
                return;
            }

            $description = trim(implode(', ', array_filter([
                trim((string) ($location['name'] ?? '')),
                trim((string) ($location['address'] ?? '')),
            ])));

            $data['ubicacion'] = $description !== '' ? $description : 'Ubicación compartida por WhatsApp';
            $data['latitud'] = $latitude;
            $data['longitud'] = $longitude;
        } else {
            $value = $this->textValue($input);

            if (mb_strlen($value) < 8) {
                $this->cloudService->sendText($from, 'Necesito una dirección más precisa o una ubicación compartida desde WhatsApp.');
                return;
            }

            $data['ubicacion'] = $this->limit($value, 500);
            $data['latitud'] = null;
            $data['longitud'] = null;
        }

        $this->putState($from, ['step' => 'await_type', 'data' => $data]);
        $this->sendTypeMenu($from);
    }

    protected function sendTypeMenu(string $from, ?string $notice = null): void
    {
        if ($notice) {
            $this->cloudService->sendText($from, $notice);
        }

        $this->cloudService->sendInteractive($from, [
            'type' => 'list',
            'header' => ['type' => 'text', 'text' => '2 de 5 — Tipo de siniestro'],
            'body' => ['text' => 'Selecciona la opción que mejor describa lo ocurrido.'],
            'footer' => ['text' => 'Reporte ciudadano'],
            'action' => [
                'button' => 'Elegir tipo',
                'sections' => [[
                    'title' => 'Tipo de hecho',
                    'rows' => [
                        ['id' => 'citizen:type:choque', 'title' => 'Choque'],
                        ['id' => 'citizen:type:volcadura', 'title' => 'Volcadura'],
                        ['id' => 'citizen:type:atropellamiento', 'title' => 'Atropellamiento'],
                        ['id' => 'citizen:type:salida_camino', 'title' => 'Salida del camino'],
                        ['id' => 'citizen:type:otro', 'title' => 'Otro'],
                    ],
                ]],
            ],
        ]);
    }

    protected function handleType(string $from, array $input, array $state): void
    {
        $value = $this->normalizeText((string) ($input['value'] ?? ''));
        $types = [
            'citizen:type:choque' => 'Choque',
            'citizen:type:volcadura' => 'Volcadura',
            'citizen:type:atropellamiento' => 'Atropellamiento',
            'citizen:type:salida_camino' => 'Salida del camino',
            'citizen:type:otro' => 'Otro',
            'choque' => 'Choque',
            'volcadura' => 'Volcadura',
            'atropellamiento' => 'Atropellamiento',
            'salida del camino' => 'Salida del camino',
            'otro' => 'Otro',
        ];

        if (!isset($types[$value])) {
            $this->sendTypeMenu($from, 'No pude identificar el tipo de siniestro.');
            return;
        }

        $data = (array) ($state['data'] ?? []);
        $data['tipo_siniestro'] = $types[$value];
        $this->putState($from, ['step' => 'await_vehicles', 'data' => $data]);

        $this->cloudService->sendText(
            $from,
            "3 de 5 — Vehículos\n\n¿Cuántos vehículos están involucrados y de qué tipo son?\n\nEjemplo: 2 vehículos, una motocicleta negra y una camioneta blanca."
        );
    }

    protected function handleFreeText(
        string $from,
        array $input,
        array $state,
        string $field,
        string $nextStep
    ): void {
        $value = $this->textValue($input);

        if (mb_strlen($value) < 3) {
            $this->cloudService->sendText($from, 'Necesito un poco más de información para continuar.');
            return;
        }

        $data = (array) ($state['data'] ?? []);
        $data[$field] = $this->limit($value, 1000);
        $this->putState($from, ['step' => $nextStep, 'data' => $data]);

        if ($nextStep === 'await_injured') {
            $this->sendInjuredMenu($from);
        }
    }

    protected function sendInjuredMenu(string $from, ?string $notice = null): void
    {
        if ($notice) {
            $this->cloudService->sendText($from, $notice);
        }

        $this->cloudService->sendInteractive($from, [
            'type' => 'button',
            'body' => ['text' => "4 de 5 — Personas lesionadas\n\n¿Hay personas lesionadas o atrapadas? Si conoces la cantidad, también puedes escribirla."],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'citizen:injured:yes', 'title' => 'Sí hay']],
                    ['type' => 'reply', 'reply' => ['id' => 'citizen:injured:no', 'title' => 'No hay']],
                    ['type' => 'reply', 'reply' => ['id' => 'citizen:injured:unknown', 'title' => 'No sé']],
                ],
            ],
        ]);
    }

    protected function handleInjured(string $from, array $input, array $state): void
    {
        $value = $this->textValue($input);
        $normalized = $this->normalizeText($value);
        $options = [
            'citizen:injured:yes' => 'Sí, hay personas lesionadas o atrapadas',
            'citizen:injured:no' => 'No se observan personas lesionadas',
            'citizen:injured:unknown' => 'El reportante no sabe si hay personas lesionadas',
        ];

        if (isset($options[$normalized])) {
            $value = $options[$normalized];
        } elseif (mb_strlen($value) < 2) {
            $this->sendInjuredMenu($from, 'Selecciona una opción o escribe la cantidad y condición aproximada.');
            return;
        }

        $data = (array) ($state['data'] ?? []);
        $data['lesionados'] = $this->limit($value, 1000);
        $this->putState($from, ['step' => 'await_risks', 'data' => $data]);

        $this->cloudService->sendText(
            $from,
            "5 de 5 — Riesgos y observaciones\n\nIndica si la circulación está bloqueada, si hay incendio, derrame, personas atrapadas u otro riesgo. Agrega cualquier referencia útil.\n\nSi no observas riesgos adicionales, escribe NINGUNO."
        );
    }

    protected function handleRisks(string $from, array $input, array $state): void
    {
        $value = $this->textValue($input);

        if (mb_strlen($value) < 3) {
            $this->cloudService->sendText($from, 'Describe brevemente los riesgos o escribe NINGUNO.');
            return;
        }

        $data = (array) ($state['data'] ?? []);
        $data['riesgos_observaciones'] = $this->limit($value, 1500);
        $this->putState($from, ['step' => 'await_confirmation', 'data' => $data]);

        $this->cloudService->sendText($from, "Revisa tu reporte:\n\n" . $this->summary($from, $data, false));
        $this->cloudService->sendInteractive($from, [
            'type' => 'button',
            'body' => ['text' => '¿Deseas enviarlo a las unidades de Seguridad Vial?'],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'citizen:submit', 'title' => 'Enviar reporte']],
                    ['type' => 'reply', 'reply' => ['id' => 'citizen:cancel', 'title' => 'Cancelar']],
                ],
            ],
        ]);
    }

    protected function handleConfirmation(string $from, array $input, array $state): void
    {
        $value = $this->normalizeText((string) ($input['value'] ?? ''));

        if (in_array($value, ['citizen:cancel', 'cancelar', 'no'], true)) {
            $this->clear($from);
            $this->cloudService->sendText($from, 'El reporte fue cancelado. Escribe cualquier mensaje para volver al menú.');
            return;
        }

        if (!in_array($value, ['citizen:submit', 'enviar', 'enviar reporte', 'si', 'sí'], true)) {
            $this->cloudService->sendText($from, 'Toca Enviar reporte para confirmar o escribe CANCELAR.');
            return;
        }

        if (!$this->allowedToSubmit($from)) {
            $this->clear($from);
            $this->cloudService->sendText(
                $from,
                'Se alcanzó el límite temporal de reportes para este número. Si existe peligro inmediato, llama al 911.'
            );
            return;
        }

        $data = (array) ($state['data'] ?? []);

        try {
            $report = $this->persist($from, $data);
            $results = $this->notifyRecipients($report);
            $successful = count(array_filter($results, static function (array $result): bool {
                return (bool) ($result['ok'] ?? false);
            }));
            $expected = count($results);

            $report->update([
                'notificado_at' => $successful > 0 ? now() : null,
                'resultados_notificacion' => $results,
            ]);

            $this->clear($from);

            if ($expected > 0 && $successful === $expected) {
                $this->cloudService->sendText(
                    $from,
                    "Gracias. Tu reporte {$report->folio} ya fue enviado a las unidades de Seguridad Vial para su atención y validación.\n\nSi la situación cambia o existe peligro inmediato, llama al 911."
                );
            } elseif ($successful > 0) {
                $this->cloudService->sendText(
                    $from,
                    "Tu reporte {$report->folio} quedó registrado y se notificó parcialmente. Falta confirmar uno o más destinatarios; si existe peligro inmediato, llama al 911."
                );
            } else {
                $this->cloudService->sendText(
                    $from,
                    "Tu reporte {$report->folio} quedó registrado, pero no fue posible confirmar la notificación por WhatsApp. Si existe peligro inmediato, llama al 911."
                );
            }
        } catch (\Throwable $e) {
            Log::error('No fue posible registrar el reporte ciudadano de siniestro', [
                'from' => $from,
                'error' => $e->getMessage(),
            ]);

            $this->cloudService->sendText(
                $from,
                'No fue posible registrar el reporte en este momento. Intenta nuevamente; si existe peligro inmediato, llama al 911.'
            );
        }
    }

    protected function persist(string $from, array $data): ReporteCiudadanoSiniestro
    {
        return ReporteCiudadanoSiniestro::create([
            'folio' => $this->newFolio(),
            'telefono_reportante' => preg_replace('/\D+/', '', $from),
            'ubicacion' => (string) $data['ubicacion'],
            'latitud' => $data['latitud'] ?? null,
            'longitud' => $data['longitud'] ?? null,
            'tipo_siniestro' => (string) $data['tipo_siniestro'],
            'vehiculos' => (string) $data['vehiculos'],
            'lesionados' => (string) $data['lesionados'],
            'riesgos_observaciones' => (string) $data['riesgos_observaciones'],
            'estatus' => 'pendiente_validacion',
            'reportado_at' => now(),
        ]);
    }

    protected function notifyRecipients(ReporteCiudadanoSiniestro $report): array
    {
        $recipients = $this->recipients();
        $summary = $this->summary($report->telefono_reportante, $report->toArray(), true, $report->folio);
        $template = trim((string) config('services.whatsapp.citizen_reports.template', ''));
        $language = (string) config('services.whatsapp.citizen_reports.template_language', 'es_MX');
        $results = [];

        foreach ($recipients as $recipient) {
            $response = $template !== ''
                ? $this->cloudService->sendTemplate(
                    $recipient,
                    $template,
                    $this->templateParameters($report),
                    $language
                )
                : $this->cloudService->sendText($recipient, $summary);

            $results[] = [
                'recipient' => $recipient,
                'ok' => (bool) ($response['ok'] ?? false),
                'status' => $response['status'] ?? null,
                'message_id' => $response['body']['messages'][0]['id'] ?? null,
                'error' => $response['body']['error']['message'] ?? ($response['body']['error'] ?? null),
            ];
        }

        return $results;
    }

    protected function templateParameters(ReporteCiudadanoSiniestro $report): array
    {
        $location = $this->limit((string) $report->ubicacion, 180);

        if ($report->latitud !== null && $report->longitud !== null) {
            $location .= ' | Mapa: https://maps.google.com/?q=' . $report->latitud . ',' . $report->longitud;
        }

        return [
            (string) $report->folio,
            ($report->reportado_at ?: now())->format('d/m/Y H:i'),
            '+' . preg_replace('/\D+/', '', (string) $report->telefono_reportante),
            $location,
            $this->limit((string) $report->tipo_siniestro, 80),
            $this->limit((string) $report->vehiculos, 160),
            $this->limit((string) $report->lesionados, 140),
            $this->limit((string) $report->riesgos_observaciones, 220),
        ];
    }

    protected function summary(
        string $from,
        array $data,
        bool $internal,
        ?string $folio = null
    ): string {
        $latitude = $data['latitud'] ?? null;
        $longitude = $data['longitud'] ?? null;
        $location = (string) ($data['ubicacion'] ?? '');

        if ($latitude !== null && $longitude !== null) {
            $location .= "\nMapa: https://maps.google.com/?q={$latitude},{$longitude}";
        }

        $lines = [];

        if ($internal) {
            $lines[] = '🚨 REPORTE CIUDADANO NO VERIFICADO';
            $lines[] = 'Folio: ' . ($folio ?: 'Pendiente');
            $lines[] = 'Fecha: ' . now()->format('d/m/Y H:i');
            $lines[] = 'Reportante: +' . preg_replace('/\D+/', '', $from);
        }

        $lines[] = 'Ubicación: ' . $location;
        $lines[] = 'Tipo: ' . (string) ($data['tipo_siniestro'] ?? '');
        $lines[] = 'Vehículos: ' . (string) ($data['vehiculos'] ?? '');
        $lines[] = 'Lesionados: ' . (string) ($data['lesionados'] ?? '');
        $lines[] = 'Riesgos/observaciones: ' . (string) ($data['riesgos_observaciones'] ?? '');

        if ($internal) {
            $lines[] = 'Validar la información antes de incorporarla como hecho oficial.';
        }

        return implode("\n", $lines);
    }

    protected function recipients(): array
    {
        $configured = config('services.whatsapp.citizen_reports.to', '');
        $values = is_array($configured) ? $configured : explode(',', (string) $configured);
        $recipients = [];

        foreach ($values as $value) {
            $phone = preg_replace('/\D+/', '', (string) $value);

            if (strlen($phone) === 10) {
                $phone = '521' . $phone;
            } elseif (strlen($phone) === 12 && str_starts_with($phone, '52')) {
                $phone = '521' . substr($phone, 2);
            }

            if ($phone !== '') {
                $recipients[] = $phone;
            }
        }

        return array_values(array_unique($recipients));
    }

    protected function textValue(array $input): string
    {
        $type = (string) ($input['type'] ?? '');

        if (!in_array($type, ['text', 'button', 'list'], true)) {
            return '';
        }

        return trim((string) ($input['value'] ?? ''));
    }

    protected function normalizeText(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    protected function limit(string $value, int $length): string
    {
        return mb_substr(trim($value), 0, $length);
    }

    protected function validCoordinates(?float $latitude, ?float $longitude): bool
    {
        return $latitude !== null
            && $longitude !== null
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }

    protected function newFolio(): string
    {
        do {
            $folio = 'RC-' . now()->format('Ymd') . '-' . random_int(100000, 999999);
        } while (ReporteCiudadanoSiniestro::query()->where('folio', $folio)->exists());

        return $folio;
    }

    protected function isDuplicate(array $message): bool
    {
        $messageId = trim((string) ($message['id'] ?? ''));

        if ($messageId === '') {
            return false;
        }

        return !Cache::add('whatsapp:citizen-message:' . sha1($messageId), true, now()->addDay());
    }

    protected function allowedToSubmit(string $from): bool
    {
        $limit = max(1, (int) config('services.whatsapp.citizen_reports.max_per_hour', 3));
        $key = 'whatsapp:citizen-report-limit:'
            . sha1(preg_replace('/\D+/', '', $from))
            . ':' . now()->format('YmdH');

        Cache::add($key, 0, now()->addHours(2));

        return Cache::increment($key) <= $limit;
    }

    protected function state(string $from): array
    {
        $state = Cache::get($this->stateKey($from), []);

        return is_array($state) ? $state : [];
    }

    protected function putState(string $from, array $state): void
    {
        Cache::put($this->stateKey($from), $state, now()->addMinutes(self::STATE_TTL_MINUTES));
    }

    protected function clear(string $from): void
    {
        Cache::forget($this->stateKey($from));
    }

    protected function stateKey(string $from): string
    {
        return 'whatsapp:citizen-report:' . sha1(preg_replace('/\D+/', '', $from));
    }
}
