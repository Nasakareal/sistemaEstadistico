<?php

namespace App\Console\Commands;

use App\Services\VialidadesUrbanasDiarioWhatsAppService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarVialidadesUrbanasDiarioWhatsApp extends Command
{
    protected $signature = 'whatsapp:vialidades-urbanas-diario
        {--to= : Numeros destino separados por coma, espacio o punto y coma}
        {--corte= : Fecha/hora de corte para simular o reenviar}
        {--sin-template : Envia texto libre; solo sirve con ventana de 24 horas abierta}
        {--dry-run : Muestra el texto y no envia}
        {--demo : Usa datos de ejemplo para revisar formato; requiere --dry-run}
        {--force : Reenvia aunque ya exista guardia de envio}';

    protected $description = 'Envia el concentrado diario de Vialidades Urbanas por WhatsApp con el corte configurado';

    public function handle(
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard,
        VialidadesUrbanasDiarioWhatsAppService $service
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

        $template = trim((string) config(
            'services.whatsapp.vialidades_urbanas.template',
            'reporte_vialidades_urbanas_bloque'
        ));
        $language = trim((string) config(
            'services.whatsapp.vialidades_urbanas.template_language',
            'es_MX'
        )) ?: 'es_MX';
        $templateLayout = trim((string) config('services.whatsapp.vialidades_urbanas.template_layout', 'bloque')) ?: 'bloque';
        $templateChunkChars = (int) config('services.whatsapp.vialidades_urbanas.template_chunk_chars', 850);
        $textChunkChars = (int) config('services.whatsapp.vialidades_urbanas.text_chunk_chars', 3900);

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.vialidades_urbanas.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);
        $periodKey = $resumen['fin']->format('Y-m-d_H:i');

        if ($this->option('dry-run')) {
            $this->line('--- RANGO ---');
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' ' . $timezone);
            $this->line('--- DESTINATARIOS ---');
            $this->line(empty($recipients) ? 'SIN CONFIGURAR' : implode(', ', $recipients));
            $this->line('--- PLANTILLA ---');
            $this->line($usarTemplate ? ($template . ' (' . $language . ', layout ' . $templateLayout . ')') : 'SIN TEMPLATE / TEXTO LIBRE');

            $chunks = $this->previewChunks($service, $resumen, $mensaje, $usarTemplate, $templateLayout, $templateChunkChars, $textChunkChars);

            $this->line('--- MENSAJE ARMADO ---');
            if ($usarTemplate && $this->usaTemplateDiario($templateLayout)) {
                foreach ($chunks as $index => $chunk) {
                    if ($index > 0) {
                        $this->line('');
                    }

                    $this->line($chunk['body'] ?? implode("\n", $chunk['lines'] ?? []));
                }
            } else {
                $this->line($mensaje);
            }

            $this->line('--- PARTES ---');

            foreach ($chunks as $index => $chunk) {
                $body = is_array($chunk)
                    ? ($chunk['body'] ?? implode("\n", $chunk['lines'] ?? []))
                    : $chunk;
                $part = is_array($chunk) ? $chunk['part'] : ($index + 1);
                $total = is_array($chunk) ? $chunk['total'] : count($chunks);
                $params = is_array($chunk) ? count($chunk['parameters'] ?? []) : 0;
                $this->line('Parte ' . $part . '/' . $total . ': ' . mb_strlen((string) $body, 'UTF-8') . ' caracteres' . ($params ? ', ' . $params . ' parametros' : ''));
            }

            return self::SUCCESS;
        }

        if (empty($recipients)) {
            $this->error('No hay numeros destino. Define WHATSAPP_VIALIDADES_URBANAS_TO o usa --to=');
            return self::FAILURE;
        }

        if ($usarTemplate && $template === '') {
            $this->error('No hay plantilla configurada. Define WHATSAPP_VIALIDADES_URBANAS_TEMPLATE.');
            return self::FAILURE;
        }

        $failures = 0;
        $sentRecipients = 0;
        $sentMessages = 0;
        $skipped = 0;

        foreach ($recipients as $recipient) {
            if (!$this->option('force') && !$sendGuard->reserve('vialidades-urbanas-diario', $periodKey, $recipient)) {
                $this->warn('Concentrado ya enviado o en proceso para ' . $recipient . ' en el corte ' . $periodKey . '. Usa --force para reenviar.');
                $skipped++;
                continue;
            }

            try {
                $messageIds = $usarTemplate
                    ? $this->sendTemplate($whatsApp, $service, $recipient, $resumen, $mensaje, $template, $language, $templateLayout, $templateChunkChars)
                    : $this->sendTextChunks($whatsApp, $service, $recipient, $mensaje, $textChunkChars);

                $sendGuard->markSent(
                    'vialidades-urbanas-diario',
                    $periodKey,
                    $recipient,
                    implode(',', $messageIds)
                );

                $sentRecipients++;
                $sentMessages += count($messageIds);
            } catch (\Throwable $e) {
                $sendGuard->release('vialidades-urbanas-diario', $periodKey, $recipient);

                Log::error('Error enviando concentrado Vialidades Urbanas WhatsApp', [
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
        if ($usarTemplate && $this->usaTemplateDiario($templateLayout)) {
            foreach ($this->previewChunks($service, $resumen, $mensaje, $usarTemplate, $templateLayout, $templateChunkChars, $textChunkChars) as $index => $chunk) {
                if ($index > 0) {
                    $this->line('');
                }

                $this->line($chunk['body'] ?? implode("\n", $chunk['lines'] ?? []));
            }
        } else {
            $this->line($mensaje);
        }

        if ($failures > 0) {
            $this->error("Concentrado procesado con {$sentRecipients} destinatario(s), {$sentMessages} mensaje(s), {$skipped} omitido(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Concentrado enviado a {$sentRecipients} destinatario(s) en {$sentMessages} mensaje(s). Omitidos por duplicado: {$skipped}.");
        return self::SUCCESS;
    }

    protected function previewChunks(
        VialidadesUrbanasDiarioWhatsAppService $service,
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

        if ($this->usaTemplateDiario($templateLayout)) {
            return $service->dailyTemplateMessages($resumen);
        }

        return $service->templateChunks($mensaje, $templateChunkChars);
    }

    protected function sendTemplate(
        WhatsAppCloudService $whatsApp,
        VialidadesUrbanasDiarioWhatsAppService $service,
        string $recipient,
        array $resumen,
        string $mensaje,
        string $template,
        string $language,
        string $templateLayout,
        int $templateChunkChars
    ): array {
        if ($this->usaTemplateDiario($templateLayout)) {
            return $this->sendDailyTemplate($whatsApp, $service, $recipient, $resumen, $template, $language);
        }

        return $this->sendTemplateChunks($whatsApp, $service, $recipient, $mensaje, $template, $language, $templateChunkChars);
    }

    protected function sendDailyTemplate(
        WhatsAppCloudService $whatsApp,
        VialidadesUrbanasDiarioWhatsAppService $service,
        string $recipient,
        array $resumen,
        string $template,
        string $language
    ): array {
        $message = $service->dailyTemplateMessages($resumen)[0];
        $response = $whatsApp->sendTemplate($recipient, $template, $message['parameters'], $language);

        return [$this->handleMetaResponse(
            $response,
            $recipient,
            'template ' . $template . ' diario con ' . count($message['parameters']) . ' variables'
        )];
    }

    protected function usaTemplateDiario(string $templateLayout): bool
    {
        return mb_strtolower(trim($templateLayout), 'UTF-8') === 'diario';
    }

    protected function sendTemplateChunks(
        WhatsAppCloudService $whatsApp,
        VialidadesUrbanasDiarioWhatsAppService $service,
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
        VialidadesUrbanasDiarioWhatsAppService $service,
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
        Log::info('Respuesta WhatsApp concentrado Vialidades Urbanas', [
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
