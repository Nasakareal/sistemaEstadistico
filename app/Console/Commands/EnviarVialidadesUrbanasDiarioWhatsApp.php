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
        {--force : Reenvia aunque ya exista guardia de envio}';

    protected $description = 'Envia el concentrado diario de Vialidades Urbanas por WhatsApp con corte de 18:00 a 18:00';

    public function handle(
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard,
        VialidadesUrbanasDiarioWhatsAppService $service
    ): int {
        $timezone = 'America/Mexico_City';
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        $resumen = $service->generar($corte);
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
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
            $this->line('--- DESTINATARIOS ---');
            $this->line(empty($recipients) ? 'SIN CONFIGURAR' : implode(', ', $recipients));
            $this->line('--- PLANTILLA ---');
            $this->line($usarTemplate ? ($template . ' (' . $language . ')') : 'SIN TEMPLATE / TEXTO LIBRE');
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);
            $this->line('--- PARTES ---');

            $chunks = $usarTemplate
                ? $service->templateChunks($mensaje, $templateChunkChars)
                : $service->textChunks($mensaje, $textChunkChars);

            foreach ($chunks as $index => $chunk) {
                $body = is_array($chunk) ? $chunk['body'] : $chunk;
                $part = is_array($chunk) ? $chunk['part'] : ($index + 1);
                $total = is_array($chunk) ? $chunk['total'] : count($chunks);
                $this->line('Parte ' . $part . '/' . $total . ': ' . mb_strlen((string) $body, 'UTF-8') . ' caracteres');
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
                    ? $this->sendTemplateChunks($whatsApp, $service, $recipient, $mensaje, $template, $language, $templateChunkChars)
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
        $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
        $this->line('--- MENSAJE ARMADO ---');
        $this->line($mensaje);

        if ($failures > 0) {
            $this->error("Concentrado procesado con {$sentRecipients} destinatario(s), {$sentMessages} mensaje(s), {$skipped} omitido(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Concentrado enviado a {$sentRecipients} destinatario(s) en {$sentMessages} mensaje(s). Omitidos por duplicado: {$skipped}.");
        return self::SUCCESS;
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

            if ($number !== '') {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }
}
