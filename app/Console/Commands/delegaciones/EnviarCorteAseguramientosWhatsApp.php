<?php

namespace App\Console\Commands\delegaciones;

use App\Services\DelegacionesCorteAseguramientosWhatsAppService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarCorteAseguramientosWhatsApp extends Command
{
    protected $signature = 'whatsapp:delegaciones-corte-aseguramientos
        {--to= : Numeros destino separados por coma, espacio o punto y coma}
        {--corte= : Fecha/hora de corte para simular o reenviar}
        {--sin-template : Envia texto libre; solo sirve con ventana de 24 horas abierta}
        {--dry-run : Muestra el texto y no envia}
        {--demo : Usa datos de ejemplo para revisar formato; requiere --dry-run}
        {--force : Reenvia aunque ya exista guardia de envio}';

    protected $description = 'Envia la tarjeta de cortes de aseguramientos relevantes de Delegaciones por WhatsApp';

    public function handle(
        DelegacionesCorteAseguramientosWhatsAppService $service,
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard
    ): int {
        $timezone = (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        if ($this->option('demo') && !$this->option('dry-run')) {
            $this->error('La opcion --demo solo se permite con --dry-run para evitar envios de datos de ejemplo.');
            return Command::FAILURE;
        }

        $resumen = $this->option('demo')
            ? $service->generarDemo($corte)
            : $service->generar($corte);
        $mensaje = (string) $resumen['mensaje'];
        $params = $resumen['params'];
        $usarTemplate = !$this->option('sin-template');

        $template = trim((string) config(
            'services.whatsapp.delegaciones.cortes_template',
            'delegaciones_corte_aseguramientos_v1'
        ));
        $language = trim((string) config(
            'services.whatsapp.delegaciones.cortes_template_language',
            'es_MX'
        )) ?: 'es_MX';

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.delegaciones.cortes_to')
            ?: config('services.whatsapp.delegaciones.alertas_to')
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
            $this->line($usarTemplate ? ($template . ' (' . $language . ')') : 'SIN TEMPLATE / TEXTO LIBRE');
            $this->line('--- VARIABLES META ---');

            foreach ($params as $index => $value) {
                $this->line('{{' . ($index + 1) . '}} ' . str_replace("\n", '\n', (string) $value));
            }

            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);

            return Command::SUCCESS;
        }

        if (empty($recipients)) {
            $this->error('No hay numeros destino. Define WHATSAPP_DELEGACIONES_CORTES_TO o usa --to=');
            return Command::FAILURE;
        }

        if ($usarTemplate && $template === '') {
            $this->error('No hay plantilla configurada. Define WHATSAPP_DELEGACIONES_CORTES_TEMPLATE.');
            return Command::FAILURE;
        }

        $failures = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($recipients as $recipient) {
            if (!$this->option('force') && !$sendGuard->reserve('delegaciones-corte-aseguramientos', $periodKey, $recipient, 15)) {
                $this->warn('Corte ya enviado o en proceso para ' . $recipient . ' en ' . $periodKey . '. Usa --force para reenviar.');
                $skipped++;
                continue;
            }

            try {
                $response = $usarTemplate
                    ? $whatsApp->sendTemplate($recipient, $template, $params, $language)
                    : $whatsApp->sendText($recipient, $mensaje);

                $messageId = $this->handleMetaResponse(
                    $response,
                    $recipient,
                    $usarTemplate ? 'template ' . $template : 'texto libre'
                );

                $sendGuard->markSent(
                    'delegaciones-corte-aseguramientos',
                    $periodKey,
                    $recipient,
                    $messageId,
                    15
                );
                $sent++;
            } catch (\Throwable $e) {
                $sendGuard->release('delegaciones-corte-aseguramientos', $periodKey, $recipient);

                Log::error('Error enviando corte Delegaciones WhatsApp', [
                    'to' => $recipient,
                    'period_key' => $periodKey,
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
            $this->error("Corte procesado con {$sent} envio(s), {$skipped} omitido(s) y {$failures} error(es).");
            return Command::FAILURE;
        }

        $this->info("Corte enviado a {$sent} destinatario(s). Omitidos por duplicado: {$skipped}.");
        return Command::SUCCESS;
    }

    private function handleMetaResponse(array $response, string $recipient, string $context): string
    {
        Log::info('Respuesta WhatsApp corte Delegaciones', [
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

    private function recipients(string $configured): array
    {
        $parts = preg_split('/[\s,;|]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
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
