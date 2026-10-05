<?php

namespace App\Services;

use App\Models\ConstanciaManejo;
use App\Support\TelefonoMexico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConstanciaManejoWhatsAppService
{
    private WhatsAppCloudService $whatsApp;

    public function __construct(WhatsAppCloudService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    public function enviarConstanciaActivada(ConstanciaManejo $constancia): array
    {
        if (!(bool) config('services.whatsapp.constancias_manejo.enabled', true)) {
            return $this->resultado(false, 'deshabilitado');
        }

        $telefono = TelefonoMexico::normalize($constancia->telefono);
        if ($telefono === null || $telefono === '') {
            return $this->resultado(false, 'sin_telefono');
        }

        if (!preg_match('/^\d{10}$/', $telefono)) {
            Log::warning('Constancia activa con telefono invalido para WhatsApp.', [
                'constancia_id' => $constancia->id,
                'telefono_terminacion' => substr($telefono, -4),
            ]);

            return $this->resultado(false, 'telefono_invalido');
        }

        $template = trim((string) config(
            'services.whatsapp.constancias_manejo.template',
            'constancia_manejo_activada_v1'
        ));
        $language = trim((string) config(
            'services.whatsapp.constancias_manejo.template_language',
            'es_MX'
        )) ?: 'es_MX';
        $prefix = preg_replace('/\D+/', '', (string) config(
            'services.whatsapp.constancias_manejo.country_prefix',
            '521'
        )) ?: '521';

        if ($template === '') {
            Log::warning('No se envio constancia por WhatsApp: falta plantilla configurada.', [
                'constancia_id' => $constancia->id,
            ]);

            return $this->resultado(false, 'sin_plantilla');
        }

        $constancia->loadMissing(['modulo', 'examen', 'peritoActivador']);
        $filename = 'constancia_' . Str::slug($constancia->folio, '_') . '.pdf';
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'constancia_manejo_' . Str::uuid() . '.pdf';

        try {
            $logoDataUri = $this->imagenDataUri(public_path('img/michoacan_vertical.png'));
            $pdf = Pdf::loadView('constancias_manejo.lote_pdf', [
                'constancias' => collect([$constancia]),
                'logoDataUri' => $logoDataUri,
            ])->setPaper('letter', 'portrait');
            file_put_contents($tempPath, $pdf->output());

            $upload = $this->whatsApp->uploadMedia($tempPath, 'application/pdf');
            $mediaId = data_get($upload, 'body.id');
            if (!($upload['ok'] ?? false) || !$mediaId) {
                Log::warning('No se pudo subir la constancia para WhatsApp.', [
                    'constancia_id' => $constancia->id,
                    'status' => $upload['status'] ?? null,
                ]);

                return $this->resultado(false, 'error_subida');
            }

            $response = $this->whatsApp->sendDocumentTemplate(
                $prefix . $telefono,
                $template,
                (string) $mediaId,
                $filename,
                [
                    $constancia->folio,
                    $constancia->nombre_solicitante,
                    optional($constancia->fecha_activacion)->timezone('America/Mexico_City')->format('d/m/Y'),
                    optional($constancia->fecha_expiracion)->timezone('America/Mexico_City')->format('d/m/Y'),
                ],
                $language
            );

            if (!($response['ok'] ?? false)) {
                Log::warning('Meta no acepto el envio de constancia activada.', [
                    'constancia_id' => $constancia->id,
                    'status' => $response['status'] ?? null,
                ]);

                return $this->resultado(false, 'error_meta');
            }

            return $this->resultado(true, 'enviado', substr($telefono, -4));
        } catch (\Throwable $e) {
            report($e);

            return $this->resultado(false, 'error_envio');
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function imagenDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = in_array($extension, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    private function resultado(bool $sent, string $status, ?string $terminacion = null): array
    {
        return array_filter([
            'sent' => $sent,
            'status' => $status,
            'telefono_terminacion' => $terminacion,
        ], static fn ($value) => $value !== null);
    }
}
