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
        $templateParams = $resumen['template_params'] ?? [$mensaje];

        if ($this->option('dry-run')) {
            $this->line('--- RANGO ---');
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);

            return self::SUCCESS;
        }

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.todas_unidades.to')
            ?: config('services.whatsapp.default_to')
        );

        $template = (string) config('services.whatsapp.todas_unidades.template', '');
        $recipients = $this->recipients($to);

        if (empty($recipients)) {
            $this->error('No hay numero destino. Define WHATSAPP_TODAS_UNIDADES_TO o usa --to=');
            return self::FAILURE;
        }

        $failures = 0;
        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                if ($this->option('sin-template') || $template === '') {
                    $response = $whatsApp->sendText($recipient, $mensaje);
                } else {
                    $response = $whatsApp->sendTemplate($recipient, $template, $templateParams);
                }

                Log::info('Respuesta WhatsApp resumen todas unidades', $response);

                $this->line('--- RESPUESTA META (' . $recipient . ') ---');
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                if (!($response['ok'] ?? false)) {
                    $this->error('Meta rechazo el envio para ' . $recipient . '.');
                    $failures++;
                    continue;
                }

                $body = $response['body'] ?? [];
                $messageId = $body['messages'][0]['id'] ?? null;

                if (!$messageId) {
                    $this->error('Meta respondio sin message id para ' . $recipient . '.');
                    $failures++;
                    continue;
                }

                $this->info('Mensaje aceptado por Meta para ' . $recipient . '. ID: ' . $messageId);
                $sent++;
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
            $this->error("Resumen procesado con {$sent} enviado(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Resumen enviado a {$sent} destinatario(s).");
        return self::SUCCESS;
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
