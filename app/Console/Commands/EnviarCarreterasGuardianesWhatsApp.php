<?php

namespace App\Console\Commands;

use App\Services\CarreterasGuardianesDiarioWhatsAppService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarCarreterasGuardianesWhatsApp extends Command
{
    protected $signature = 'whatsapp:carreteras-guardianes-diario
        {--to= : Numeros destino separados por coma, espacio o punto y coma}
        {--corte= : Fecha/hora de emision para simular o reenviar}
        {--sin-template : Envia texto libre; solo sirve con ventana de 24 horas abierta}
        {--dry-run : Muestra el texto y no envia}
        {--demo : Usa datos en cero para revisar formato; requiere --dry-run}
        {--force : Reenvia aunque ya exista guardia de envio}';

    protected $description = 'Envia el consolidado diario de Guardianes del Camino Carreteras por WhatsApp con corte de 17:00 a 17:00';

    public function handle(
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard,
        CarreterasGuardianesDiarioWhatsAppService $service
    ): int {
        $timezone = (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        if ($this->option('demo') && !$this->option('dry-run')) {
            $this->error('La opcion --demo solo se permite con --dry-run para evitar envios de datos de ejemplo.');
            return self::FAILURE;
        }

        $resumen = $this->option('demo')
            ? $service->generarDemo($corte)
            : $service->generar($corte);

        $mensaje = (string) $resumen['mensaje'];
        $usarTemplate = !$this->option('sin-template');
        $templateLayout = mb_strtolower(trim((string) config('services.whatsapp.carreteras_guardianes.template_layout', 'tres_partes')), 'UTF-8') ?: 'tres_partes';
        $templateParts = [
            1 => trim((string) config('services.whatsapp.carreteras_guardianes.template_part_1', 'carreteras_guardianes_consolidado_p1')),
            2 => trim((string) config('services.whatsapp.carreteras_guardianes.template_part_2', 'carreteras_guardianes_consolidado_p2')),
            3 => trim((string) config('services.whatsapp.carreteras_guardianes.template_part_3', 'carreteras_guardianes_consolidado_p3')),
        ];
        $blockTemplate = trim((string) config(
            'services.whatsapp.carreteras_guardianes.block_template',
            'carreteras_guardianes_consolidado_bloque'
        ));
        $language = trim((string) config(
            'services.whatsapp.carreteras_guardianes.template_language',
            'es_MX'
        )) ?: 'es_MX';
        $templateChunkChars = (int) config('services.whatsapp.carreteras_guardianes.template_chunk_chars', 850);
        $textChunkChars = (int) config('services.whatsapp.carreteras_guardianes.text_chunk_chars', 3900);

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.carreteras_guardianes.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);
        $periodKey = $resumen['fin']->format('Y-m-d_H:i');

        if ($this->option('dry-run')) {
            $this->line('--- RANGO ---');
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' ' . $timezone);
            $this->line('--- EMISION ---');
            $this->line($resumen['emitido']->format('Y-m-d H:i:s') . ' ' . $timezone);
            $this->line('--- DESTINATARIOS ---');
            $this->line(empty($recipients) ? 'SIN CONFIGURAR' : implode(', ', $recipients));
            $this->line('--- PLANTILLA ---');
            if ($usarTemplate && $this->usaTemplateTresPartes($templateLayout)) {
                $this->line($templateParts[1] . ', ' . $templateParts[2] . ', ' . $templateParts[3] . ' (' . $language . ')');
            } elseif ($usarTemplate) {
                $this->line($blockTemplate . ' (' . $language . ')');
            } else {
                $this->line('SIN TEMPLATE / TEXTO LIBRE');
            }
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);
            $this->line('--- PARTES ---');

            $chunks = $this->previewMessages($service, $resumen, $mensaje, $usarTemplate, $templateLayout, $templateChunkChars, $textChunkChars);

            foreach ($chunks as $index => $chunk) {
                $body = is_array($chunk) ? ($chunk['body'] ?? '') : $chunk;
                $part = is_array($chunk) ? $chunk['part'] : ($index + 1);
                $total = is_array($chunk) ? $chunk['total'] : count($chunks);
                $params = is_array($chunk) ? count($chunk['parameters'] ?? []) : 0;
                $this->line('Parte ' . $part . '/' . $total . ': ' . mb_strlen((string) $body, 'UTF-8') . ' caracteres' . ($params ? ', ' . $params . ' variables' : ''));
            }

            return self::SUCCESS;
        }

        if (empty($recipients)) {
            $this->error('No hay numeros destino. Define WHATSAPP_CARRETERAS_GUARDIANES_TO o usa --to=');
            return self::FAILURE;
        }

        if ($usarTemplate && $this->usaTemplateTresPartes($templateLayout) && in_array('', $templateParts, true)) {
            $this->error('Falta una plantilla. Define WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_PARTE_1, PARTE_2 y PARTE_3.');
            return self::FAILURE;
        }

        if ($usarTemplate && !$this->usaTemplateTresPartes($templateLayout) && $blockTemplate === '') {
            $this->error('No hay plantilla de bloque configurada. Define WHATSAPP_CARRETERAS_GUARDIANES_BLOCK_TEMPLATE o usa --sin-template si la ventana de 24 horas esta abierta.');
            return self::FAILURE;
        }

        $failures = 0;
        $sentRecipients = 0;
        $sentMessages = 0;
        $skipped = 0;

        foreach ($recipients as $recipient) {
            if (!$this->option('force') && !$sendGuard->reserve('carreteras-guardianes-diario', $periodKey, $recipient)) {
                $this->warn('Consolidado ya enviado o en proceso para ' . $recipient . ' en el corte ' . $periodKey . '. Usa --force para reenviar.');
                $skipped++;
                continue;
            }

            try {
                if ($usarTemplate && $this->usaTemplateTresPartes($templateLayout)) {
                    $messageIds = $this->sendThreePartTemplates($whatsApp, $recipient, $resumen, $templateParts, $language);
                } elseif ($usarTemplate) {
                    $messageIds = $this->sendTemplateChunks($whatsApp, $service, $recipient, $mensaje, $blockTemplate, $language, $templateChunkChars);
                } else {
                    $messageIds = $this->sendTextChunks($whatsApp, $service, $recipient, $mensaje, $textChunkChars);
                }

                $sendGuard->markSent(
                    'carreteras-guardianes-diario',
                    $periodKey,
                    $recipient,
                    implode(',', $messageIds)
                );

                $sentRecipients++;
                $sentMessages += count($messageIds);
            } catch (\Throwable $e) {
                $sendGuard->release('carreteras-guardianes-diario', $periodKey, $recipient);

                Log::error('Error enviando consolidado Carreteras Guardianes WhatsApp', [
                    'to' => $recipient,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Error enviando a ' . $recipient . ': ' . $e->getMessage());
                $failures++;
            }
        }

        $this->line('--- RANGO ---');
        $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' ' . $timezone);
        $this->line('--- MENSAJE ARMADO ---');
        $this->line($mensaje);

        if ($failures > 0) {
            $this->error("Consolidado procesado con {$sentRecipients} destinatario(s), {$sentMessages} mensaje(s), {$skipped} omitido(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Consolidado enviado a {$sentRecipients} destinatario(s) en {$sentMessages} mensaje(s). Omitidos por duplicado: {$skipped}.");
        return self::SUCCESS;
    }

    protected function previewMessages(
        CarreterasGuardianesDiarioWhatsAppService $service,
        array $resumen,
        string $mensaje,
        bool $usarTemplate,
        string $templateLayout,
        int $templateChunkChars,
        int $textChunkChars
    ): array {
        if (!$usarTemplate) {
            return $service->textChunks($mensaje, $textChunkChars);
        }

        if ($this->usaTemplateTresPartes($templateLayout)) {
            return $resumen['template_parts'] ?? [];
        }

        return $service->templateChunks($mensaje, $templateChunkChars);
    }

    protected function sendThreePartTemplates(
        WhatsAppCloudService $whatsApp,
        string $recipient,
        array $resumen,
        array $templateParts,
        string $language
    ): array {
        $accepted = [];
        $messages = $resumen['template_parts'] ?? [];

        foreach ($messages as $message) {
            $part = (int) ($message['part'] ?? 0);
            $template = $templateParts[$part] ?? '';

            if ($part < 1 || $part > 3 || $template === '') {
                throw new \RuntimeException('No hay plantilla configurada para la parte ' . $part . '.');
            }

            $response = $whatsApp->sendTemplate($recipient, $template, $message['parameters'] ?? [], $language);

            $accepted[] = $this->handleMetaResponse(
                $response,
                $recipient,
                'template ' . $template . ' parte ' . $part . '/3 con ' . count($message['parameters'] ?? []) . ' variables'
            );
        }

        return $accepted;
    }

    protected function sendTemplateChunks(
        WhatsAppCloudService $whatsApp,
        CarreterasGuardianesDiarioWhatsAppService $service,
        string $recipient,
        string $mensaje,
        string $template,
        string $language,
        int $templateChunkChars
    ): array {
        $accepted = [];
        $chunks = $service->templateChunks($mensaje, $templateChunkChars);

        foreach ($chunks as $chunk) {
            $response = $whatsApp->sendTemplate($recipient, $template, $chunk['parameters'], $language);

            $accepted[] = $this->handleMetaResponse(
                $response,
                $recipient,
                'template ' . $template . ' parte ' . $chunk['part'] . '/' . $chunk['total']
            );
        }

        return $accepted;
    }

    protected function sendTextChunks(
        WhatsAppCloudService $whatsApp,
        CarreterasGuardianesDiarioWhatsAppService $service,
        string $recipient,
        string $mensaje,
        int $textChunkChars
    ): array {
        $accepted = [];
        $chunks = $service->textChunks($mensaje, $textChunkChars);

        foreach ($chunks as $index => $chunk) {
            $response = $whatsApp->sendText($recipient, $chunk);

            $accepted[] = $this->handleMetaResponse(
                $response,
                $recipient,
                'texto libre parte ' . ($index + 1) . '/' . count($chunks)
            );
        }

        return $accepted;
    }

    protected function handleMetaResponse(array $response, string $recipient, string $context): string
    {
        Log::info('Respuesta WhatsApp consolidado Carreteras Guardianes', [
            'to' => $recipient,
            'context' => $context,
            'response' => $response,
        ]);

        $this->line('--- RESPUESTA META (' . $recipient . ', ' . $context . ') ---');
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!($response['ok'] ?? false)) {
            throw new \RuntimeException('Meta rechazo el envio de ' . $context . '.');
        }

        $messageId = $response['body']['messages'][0]['id'] ?? null;

        if (!$messageId) {
            throw new \RuntimeException('Meta respondio sin message id para ' . $context . '.');
        }

        $this->info('Mensaje aceptado por Meta para ' . $recipient . ' (' . $context . '). ID: ' . $messageId);

        return (string) $messageId;
    }

    protected function usaTemplateTresPartes(string $templateLayout): bool
    {
        return in_array(mb_strtolower(trim($templateLayout), 'UTF-8'), ['tres_partes', '3_partes', 'tripartita'], true);
    }

    protected function recipients(string $configured): array
    {
        $parts = preg_split('/[\s,;]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
        $numbers = [];

        foreach ($parts ?: [] as $part) {
            $number = preg_replace('/\D+/', '', (string) $part);

            if ($number !== '' && strlen($number) >= 10 && strlen($number) <= 15) {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }
}
