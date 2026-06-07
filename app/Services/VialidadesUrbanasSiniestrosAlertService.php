<?php

namespace App\Services;

use App\Models\Actividad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VialidadesUrbanasSiniestrosAlertService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;
    private const CONTEXT = 'vialidades-urbanas-siniestros-alerta';

    private WhatsAppCloudService $whatsApp;
    private WhatsAppSendGuard $sendGuard;

    public function __construct(
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard
    ) {
        $this->whatsApp = $whatsApp;
        $this->sendGuard = $sendGuard;
    }

    public function notificarActividad(Actividad $actividad): void
    {
        try {
            $actividad->loadMissing([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
            ]);

            if (!$this->debeNotificarActividad($actividad)) {
                return;
            }

            $recipients = $this->recipients();
            $template = trim((string) config(
                'services.whatsapp.siniestros.vialidades_urbanas_alertas_template',
                'alerta_vialidades_urbanas_siniestros'
            ));
            $language = trim((string) config(
                'services.whatsapp.siniestros.vialidades_urbanas_alertas_template_language',
                'es_MX'
            )) ?: 'es_MX';

            if (empty($recipients)) {
                Log::warning('WhatsApp alerta Vialidades Urbanas/Siniestros sin destinatarios', [
                    'actividad_id' => $actividad->id,
                ]);
                return;
            }

            if ($template === '') {
                Log::warning('WhatsApp alerta Vialidades Urbanas/Siniestros sin plantilla', [
                    'actividad_id' => $actividad->id,
                ]);
                return;
            }

            $params = $this->templateParams($actividad);

            foreach ($recipients as $recipient) {
                $periodKey = 'actividad:' . $actividad->id;

                if (!$this->sendGuard->reserve(self::CONTEXT, $periodKey, $recipient, 30)) {
                    Log::info('WhatsApp alerta Vialidades Urbanas/Siniestros ya reservada', [
                        'actividad_id' => $actividad->id,
                        'to' => $recipient,
                    ]);
                    continue;
                }

                try {
                    $response = $this->whatsApp->sendTemplate(
                        $recipient,
                        $template,
                        $params,
                        $language
                    );

                    if (!($response['ok'] ?? false)) {
                        $this->sendGuard->release(self::CONTEXT, $periodKey, $recipient);
                        Log::error('Meta rechazo WhatsApp alerta Vialidades Urbanas/Siniestros', [
                            'actividad_id' => $actividad->id,
                            'template' => $template,
                            'to' => $recipient,
                            'status' => $response['status'] ?? null,
                            'body' => $response['body'] ?? null,
                        ]);
                        continue;
                    }

                    $messageId = $response['body']['messages'][0]['id'] ?? null;
                    $this->sendGuard->markSent(self::CONTEXT, $periodKey, $recipient, $messageId, 30);

                    Log::info('WhatsApp alerta Vialidades Urbanas/Siniestros enviada', [
                        'actividad_id' => $actividad->id,
                        'template' => $template,
                        'to' => $recipient,
                        'message_id' => $messageId,
                    ]);
                } catch (\Throwable $e) {
                    $this->sendGuard->release(self::CONTEXT, $periodKey, $recipient);
                    Log::error('Error enviando WhatsApp alerta Vialidades Urbanas/Siniestros', [
                        'actividad_id' => $actividad->id,
                        'template' => $template,
                        'to' => $recipient,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error preparando WhatsApp alerta Vialidades Urbanas/Siniestros', [
                'actividad_id' => $actividad->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function debeNotificarActividad(Actividad $actividad): bool
    {
        $actividad->loadMissing(['categoria', 'subcategoria']);

        if ((int) ($actividad->unidad_org_id ?? 0) !== self::UNIDAD_VIALIDADES_URBANAS_ID) {
            return false;
        }

        $categoria = $this->normalize(optional($actividad->categoria)->nombre);
        $subcategoria = $this->normalize(optional($actividad->subcategoria)->nombre);

        $esAbanderamientoAccidente =
            str_contains($categoria, 'ABANDERAMIENTO') &&
            (
                str_contains($subcategoria, 'ACCIDENTE') ||
                str_contains($subcategoria, 'SINIESTRO')
            );

        $esReporteC5iHechoTransito =
            str_contains($categoria, 'REPORTE') &&
            (str_contains($categoria, 'C5I') || str_contains($categoria, 'C5')) &&
            (
                str_contains($subcategoria, 'HECHO DE TRANSITO') ||
                str_contains($subcategoria, 'HECHOS DE TRANSITO') ||
                $subcategoria === 'HECHOS' ||
                str_contains($subcategoria, 'SINIESTRO')
            );

        return $esAbanderamientoAccidente || $esReporteC5iHechoTransito;
    }

    private function templateParams(Actividad $actividad): array
    {
        $categoria = trim((string) optional($actividad->categoria)->nombre);
        $subcategoria = trim((string) optional($actividad->subcategoria)->nombre);
        $tipo = trim($categoria . ' / ' . $subcategoria, ' /');

        return [
            '#' . $actividad->id,
            $this->valorTemplate($this->fechaHora($actividad->fecha ?? null, $actividad->hora ?? null)),
            $this->valorTemplate($tipo),
            $this->valorTemplate($this->ubicacionActividad($actividad)),
            $this->valorTemplate($this->mapsLink($actividad->lat ?? null, $actividad->lng ?? null)),
            $this->valorTemplate(optional($actividad->creador)->name),
            $this->valorTemplate($actividad->patrullas_participantes_texto ?? $actividad->elementos_participantes_texto ?? null),
            $this->valorTemplate($this->preview(
                $actividad->observaciones
                ?? $actividad->motivo
                ?? $actividad->narrativa
                ?? null,
                280
            )),
        ];
    }

    private function recipients(): array
    {
        $configured = (string) config('services.whatsapp.siniestros.vialidades_urbanas_alertas_to', '');
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

    private function ubicacionActividad(Actividad $actividad): ?string
    {
        $parts = array_filter([
            $actividad->lugar ?? null,
            $actividad->municipio ?? null,
            $actividad->carretera ?? null,
            $actividad->tramo ?? null,
            $actividad->kilometro ?? null,
        ]);

        $ubicacion = trim(implode(', ', $parts));

        if ($ubicacion === '') {
            $ubicacion = trim((string) ($actividad->coordenadas_texto ?? ''));
        }

        return $ubicacion !== '' ? $ubicacion : null;
    }

    private function mapsLink($lat, $lng): ?string
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        return 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    private function fechaHora($fecha, $hora): ?string
    {
        $fechaTexto = '';

        if (!empty($fecha)) {
            try {
                $fechaTexto = Carbon::parse($fecha)->format('d/m/Y');
            } catch (\Throwable $e) {
                $fechaTexto = substr((string) $fecha, 0, 10);
            }
        }

        $horaTexto = '';

        if (!empty($hora)) {
            if ($hora instanceof \DateTimeInterface) {
                $horaTexto = $hora->format('H:i');
            } elseif (preg_match('/\b(\d{2}:\d{2})/', (string) $hora, $match)) {
                $horaTexto = $match[1];
            }
        }

        $result = trim($fechaTexto . ' ' . $horaTexto);

        return $result !== '' ? $result : null;
    }

    private function preview($value, int $limit = 350): ?string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $value));

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit - 3, 'UTF-8') . '...';
    }

    private function valorTemplate($value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'No especificado';
    }

    private function normalize($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $map = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
        ];

        return mb_strtoupper(strtr($value, $map), 'UTF-8');
    }
}
