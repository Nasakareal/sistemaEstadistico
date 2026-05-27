<?php

namespace App\Services;

use App\Models\Oficio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OficioTerminoWhatsAppService
{
    private WhatsAppCloudService $whatsApp;

    public function __construct(WhatsAppCloudService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    public function notificar(Oficio $oficio): bool
    {
        if (!$this->debeNotificar($oficio)) {
            return false;
        }

        $destinatarios = $this->destinatarios();

        if (empty($destinatarios)) {
            Log::warning('Oficio con termino sin destinatarios WhatsApp', [
                'oficio_id' => $oficio->id,
            ]);

            return false;
        }

        $oficio->loadMissing(['unidad', 'creador']);
        $mensaje = $this->mensaje($oficio);
        $template = trim((string) config('services.whatsapp.oficios.terminos_template', ''));
        $templateLanguage = (string) config('services.whatsapp.oficios.terminos_template_language', 'es_MX');
        $enviado = false;

        foreach ($destinatarios as $to) {
            try {
                $response = $template !== ''
                    ? $this->whatsApp->sendTemplate($to, $template, $this->templateParams($oficio), $templateLanguage)
                    : $this->whatsApp->sendText($to, $mensaje);

                $ok = (bool) ($response['ok'] ?? false);
                $enviado = $enviado || $ok;

                Log::info('WhatsApp oficio con termino enviado', [
                    'oficio_id' => $oficio->id,
                    'to' => $to,
                    'ok' => $ok,
                    'status' => $response['status'] ?? null,
                    'template' => $template !== '' ? $template : null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error enviando WhatsApp de oficio con termino', [
                    'oficio_id' => $oficio->id,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($enviado) {
            $oficio->forceFill(['termino_notificado_at' => now()])->save();
        }

        return $enviado;
    }

    private function debeNotificar(Oficio $oficio): bool
    {
        return (int) ($oficio->termino_horas ?? 0) > 0
            && empty($oficio->termino_notificado_at);
    }

    private function destinatarios(): array
    {
        $configured = (string) config('services.whatsapp.oficios.terminos_to', '');
        $parts = preg_split('/[\s,;|]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
        $numbers = [];

        foreach ($parts ?: [] as $part) {
            $number = preg_replace('/\D+/', '', (string) $part);

            if ($number !== '') {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function mensaje(Oficio $oficio): string
    {
        return implode("\n", array_values(array_filter([
            'GUARDIA CIVIL',
            'ALERTA DE OFICIO CON TERMINO',
            '',
            'Oficio ID: ' . $oficio->id,
            'Numero: ' . ($oficio->numero_oficio ?: 'Sin numero'),
            'Tipo: ' . $oficio->tipo_label,
            'Movimiento: ' . $oficio->sentido_label,
            'Termino: ' . ($oficio->termino_label ?: ((int) $oficio->termino_horas . ' horas')),
            'Fecha del documento: ' . $this->fecha($oficio->fecha_documento ?? null),
            'Unidad: ' . (optional($oficio->unidad)->nombre ?: 'Sin unidad'),
            'Asunto: ' . ($oficio->asunto ?: 'Sin asunto'),
            'Remitente: ' . ($oficio->remitente ?: 'Sin remitente'),
            'Destinatario: ' . ($oficio->destinatario ?: 'Sin destinatario'),
            'Capturo: ' . (optional($oficio->creador)->name ?: 'Sin dato'),
        ], fn ($line) => $line !== null)));
    }

    private function templateParams(Oficio $oficio): array
    {
        return [
            $oficio->tipo_label . ' - ' . $oficio->sentido_label,
            $oficio->termino_label ?: ((int) $oficio->termino_horas . ' horas'),
            $oficio->numero_oficio ?: 'Sin numero',
            $this->fecha($oficio->fecha_documento ?? null),
            optional($oficio->unidad)->nombre ?: 'Sin unidad',
            $oficio->asunto ?: 'Sin asunto',
            $oficio->remitente ?: 'Sin remitente',
            $oficio->destinatario ?: 'Sin destinatario',
            optional($oficio->creador)->name ?: 'Sin dato',
        ];
    }

    private function fecha($fecha): string
    {
        if (empty($fecha)) {
            return 'Sin fecha';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable $e) {
            return substr((string) $fecha, 0, 10);
        }
    }
}
