<?php

namespace App\Console\Commands;

use App\Services\ResumenTodasUnidadesWhatsAppService;
use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarResumenTodasUnidadesWhatsApp extends Command
{
    protected $signature = 'whatsapp:resumen-todas-unidades {--to=} {--corte=} {--sin-template} {--dry-run}';
    protected $description = 'Envia el resumen de todas las unidades por WhatsApp con corte de 19:00 a 19:00';

    public function handle(WhatsAppCloudService $whatsApp, ResumenTodasUnidadesWhatsAppService $resumenService): int
    {
        $timezone = 'America/Mexico_City';
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        $resumen = $resumenService->generar($corte);
        $mensaje = (string) $resumen['mensaje'];
        $usarTemplate = !$this->option('sin-template');
        $template = (string) config('services.whatsapp.todas_unidades.template', '');
        $templateLayout = (string) config('services.whatsapp.todas_unidades.template_layout', 'diario');
        $language = (string) config('services.whatsapp.todas_unidades.template_language', 'es_MX');
        $templateChunkChars = (int) config('services.whatsapp.todas_unidades.template_chunk_chars', 30000);
        $textChunkChars = (int) config('services.whatsapp.todas_unidades.text_chunk_chars', 3900);

        if ($this->option('dry-run')) {
            $this->line('--- RANGO ---');
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);
            $this->line('--- CANAL WHATSAPP ---');

            if ($usarTemplate && $template !== '') {
                $this->line('Modo: plantilla body-only');
                $this->line('Template: ' . $template . ' (' . $language . ')');
                $this->line('Layout: ' . $templateLayout);

                if ($this->usaTemplateBloque($templateLayout)) {
                    $chunks = $resumenService->whatsAppTemplateChunks($mensaje, $templateChunkChars);
                    $this->line('Partes: ' . count($chunks));

                    foreach ($chunks as $chunk) {
                        $this->line('Parte ' . $chunk['part'] . '/' . $chunk['total'] . ': ' . mb_strlen($chunk['body'], 'UTF-8') . ' caracteres');
                    }
                } else {
                    $params = $resumen['template_params'] ?? [];
                    $this->line('Variables: ' . count($params));
                    $this->line('Texto final aproximado: ' . mb_strlen($mensaje, 'UTF-8') . ' caracteres');

                    foreach ($params as $index => $param) {
                        $this->line('{{' . ($index + 1) . '}}: ' . mb_strlen((string) $param, 'UTF-8') . ' caracteres');
                    }
                }
            } else {
                $chunks = $resumenService->whatsAppTextChunks($mensaje, $textChunkChars);
                $this->line('Modo: texto libre');
                $this->line('Partes: ' . count($chunks) . ' (solo funciona con ventana de 24 horas abierta)');

                foreach ($chunks as $index => $chunk) {
                    $this->line('Parte ' . ($index + 1) . '/' . count($chunks) . ': ' . mb_strlen($chunk, 'UTF-8') . ' caracteres');
                }
            }

            return self::SUCCESS;
        }

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.todas_unidades.to')
            ?: config('services.whatsapp.default_to')
        );

        $recipients = $this->recipients($to);

        if (empty($recipients)) {
            $this->error('No hay numero destino. Define WHATSAPP_TODAS_UNIDADES_TO o usa --to=');
            return self::FAILURE;
        }

        if ($usarTemplate && $template === '') {
            $this->error('No hay plantilla configurada. Define WHATSAPP_TODAS_UNIDADES_TEMPLATE o usa --sin-template solo si la ventana de 24 horas esta abierta.');
            return self::FAILURE;
        }

        $failures = 0;
        $sentRecipients = 0;
        $sentMessages = 0;

        foreach ($recipients as $recipient) {
            try {
                if ($usarTemplate && $this->usaTemplateBloque($templateLayout)) {
                    $responses = $this->sendTemplateChunks($whatsApp, $resumenService, $recipient, $mensaje, $template, $language, $templateChunkChars);
                } elseif ($usarTemplate) {
                    $responses = $this->sendDailyTemplate($whatsApp, $recipient, $template, $resumen['template_params'] ?? [], $language);
                } else {
                    $responses = $this->sendTextChunks($whatsApp, $resumenService, $recipient, $mensaje, $textChunkChars);
                }

                $sentMessages += count($responses);
                $sentRecipients++;
            } catch (\Throwable $e) {
                Log::error('Error enviando resumen todas unidades WhatsApp', [
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
            $this->error("Resumen procesado con {$sentRecipients} destinatario(s), {$sentMessages} mensaje(s) aceptado(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Resumen enviado a {$sentRecipients} destinatario(s) en {$sentMessages} mensaje(s).");
        return self::SUCCESS;
    }

    protected function sendDailyTemplate(
        WhatsAppCloudService $whatsApp,
        string $recipient,
        string $template,
        array $params,
        string $language
    ): array {
        $response = $whatsApp->sendTemplate($recipient, $template, $params, $language);

        $messageId = $this->handleMetaResponse(
            $response,
            $recipient,
            'template ' . $template . ' con ' . count($params) . ' variables'
        );

        return [$messageId];
    }

    protected function sendTemplateChunks(
        WhatsAppCloudService $whatsApp,
        ResumenTodasUnidadesWhatsAppService $resumenService,
        string $recipient,
        string $mensaje,
        string $template,
        string $language,
        int $templateChunkChars
    ): array {
        $accepted = [];
        $chunks = $resumenService->whatsAppTemplateChunks($mensaje, $templateChunkChars);

        foreach ($chunks as $chunk) {
            $response = $whatsApp->sendTemplate($recipient, $template, $chunk['parameters'], $language);

            $messageId = $this->handleMetaResponse(
                $response,
                $recipient,
                'template ' . $template . ' parte ' . $chunk['part'] . '/' . $chunk['total']
            );

            $accepted[] = $messageId;
        }

        return $accepted;
    }

    protected function sendTextChunks(
        WhatsAppCloudService $whatsApp,
        ResumenTodasUnidadesWhatsAppService $resumenService,
        string $recipient,
        string $mensaje,
        int $textChunkChars
    ): array {
        $accepted = [];
        $chunks = $resumenService->whatsAppTextChunks($mensaje, $textChunkChars);

        foreach ($chunks as $index => $chunk) {
            $response = $whatsApp->sendText($recipient, $chunk);

            $messageId = $this->handleMetaResponse(
                $response,
                $recipient,
                'texto libre parte ' . ($index + 1) . '/' . count($chunks)
            );

            $accepted[] = $messageId;
        }

        return $accepted;
    }

    protected function handleMetaResponse(array $response, string $recipient, string $context): string
    {
        Log::info('Respuesta WhatsApp resumen todas unidades', [
            'to' => $recipient,
            'context' => $context,
            'response' => $response,
        ]);

        $this->line('--- RESPUESTA META (' . $recipient . ', ' . $context . ') ---');
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!($response['ok'] ?? false)) {
            throw new \RuntimeException('Meta rechazo el envio de ' . $context . '.');
        }

        $body = $response['body'] ?? [];
        $messageId = $body['messages'][0]['id'] ?? null;

        if (!$messageId) {
            throw new \RuntimeException('Meta respondio sin message id para ' . $context . '.');
        }

        $this->info('Mensaje aceptado por Meta para ' . $recipient . ' (' . $context . '). ID: ' . $messageId);

        return (string) $messageId;
    }

    protected function usaTemplateBloque(string $templateLayout): bool
    {
        return mb_strtolower(trim($templateLayout), 'UTF-8') === 'bloque';
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
