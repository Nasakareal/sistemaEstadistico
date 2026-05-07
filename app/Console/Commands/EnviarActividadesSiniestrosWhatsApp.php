<?php

namespace App\Console\Commands;

use App\Services\ActividadInformeService;
use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnviarActividadesSiniestrosWhatsApp extends Command
{
    protected $signature = 'whatsapp:actividades-siniestros {--to=} {--fecha=} {--regenerar} {--dry-run}';
    protected $description = 'Envia el PDF diario de actividades de siniestros por WhatsApp';

    public function handle(WhatsAppCloudService $whatsApp, ActividadInformeService $informeService): int
    {
        $timezone = 'America/Mexico_City';
        $fecha = $this->resolveFecha($timezone);
        $archivo = 'actividades_' . $fecha . '.pdf';
        $rutaRelativa = 'cortes/actividades/' . $archivo;
        $disk = Storage::disk('local');

        if ($this->option('regenerar') || !$disk->exists($rutaRelativa)) {
            $archivo = $informeService->generarYGuardarEnCortes($fecha, new Request());
            $rutaRelativa = 'cortes/actividades/' . $archivo;
        }

        $rutaAbsoluta = $disk->path($rutaRelativa);

        if (!is_file($rutaAbsoluta)) {
            $this->error('No se encontro el PDF de actividades: ' . $rutaAbsoluta);
            return self::FAILURE;
        }

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.siniestros.actividades_to')
            ?: config('services.whatsapp.siniestros.resumen_to')
            ?: config('services.whatsapp.siniestros.tarjeta_hechos_to')
            ?: config('services.whatsapp.siniestros.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);

        if (empty($recipients)) {
            $this->error('No hay numero destino. Define WHATSAPP_SINIESTROS_ACTIVIDADES_TO o usa --to=');
            return self::FAILURE;
        }

        $fechaTexto = mb_strtoupper(
            Carbon::createFromFormat('Y-m-d', $fecha, $timezone)->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
            'UTF-8'
        );
        $caption = 'Estadistica diaria de actividades de siniestros ' . $fechaTexto . '.';

        if ($this->option('dry-run')) {
            $this->line('--- PDF ---');
            $this->line($rutaAbsoluta);
            $this->line('--- DESTINATARIOS ---');
            $this->line(implode(', ', $recipients));
            $this->line('--- CAPTION ---');
            $this->line($caption);

            return self::SUCCESS;
        }

        $upload = $whatsApp->uploadMedia($rutaAbsoluta, 'application/pdf');

        Log::info('Respuesta WhatsApp upload actividades siniestros', $upload);

        $this->line('--- RESPUESTA META UPLOAD ---');
        $this->line(json_encode($upload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!($upload['ok'] ?? false)) {
            $this->error('Meta rechazo la subida del PDF.');
            return self::FAILURE;
        }

        $mediaId = $upload['body']['id'] ?? null;

        if (!$mediaId) {
            $this->error('Meta respondio sin media id al subir el PDF.');
            return self::FAILURE;
        }

        $failures = 0;
        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                $response = $whatsApp->sendDocument(
                    $recipient,
                    (string) $mediaId,
                    $archivo,
                    $caption
                );

                Log::info('Respuesta WhatsApp actividades siniestros', $response);

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

                $this->info('PDF aceptado por Meta para ' . $recipient . '. ID: ' . $messageId);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Error enviando PDF de actividades WhatsApp', [
                    'to' => $recipient,
                    'archivo' => $rutaAbsoluta,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Error enviando a ' . $recipient . ': ' . $e->getMessage());
                $failures++;
            }
        }

        $this->line('--- PDF ENVIADO ---');
        $this->line($rutaAbsoluta);

        if ($failures > 0) {
            $this->error("PDF procesado con {$sent} enviado(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("PDF enviado a {$sent} destinatario(s).");
        return self::SUCCESS;
    }

    protected function resolveFecha(string $timezone): string
    {
        $fecha = $this->option('fecha');

        if ($fecha) {
            return Carbon::parse((string) $fecha, $timezone)->format('Y-m-d');
        }

        return Carbon::now($timezone)->format('Y-m-d');
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
