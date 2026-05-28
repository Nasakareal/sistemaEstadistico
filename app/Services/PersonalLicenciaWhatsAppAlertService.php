<?php

namespace App\Services;

use App\Models\Personal;
use App\Models\PersonalLicencia;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PersonalLicenciaWhatsAppAlertService
{
    private WhatsAppCloudService $whatsApp;

    public function __construct(WhatsAppCloudService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    public function licenciasVencidasPendientes(?Carbon $fecha = null, bool $force = false): Collection
    {
        $fecha = $fecha ?: Carbon::now('America/Mexico_City');

        return PersonalLicencia::query()
            ->with(['personal.unidad'])
            ->where('activo', true)
            ->where('permanente', false)
            ->whereDate('vigencia', '<', $fecha->toDateString())
            ->when(!$force, function ($query) {
                $query->whereNull('vencimiento_notificado_at');
            })
            ->orderBy('vigencia')
            ->orderBy('tipo')
            ->get();
    }

    public function destinatarios(?string $override = null): array
    {
        $configured = (string) (
            $override
            ?: config('services.whatsapp.oficios.terminos_to', '')
        );
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

    public function notificar(Collection $licencias, array $destinatarios, ?Carbon $fecha = null): array
    {
        $fecha = $fecha ?: Carbon::now('America/Mexico_City');
        $mensaje = $this->mensaje($licencias, $fecha);
        $chunks = $this->chunks($mensaje);
        $enviados = 0;
        $fallidos = 0;
        $destinatariosConEnvio = 0;

        foreach ($destinatarios as $to) {
            $okDestinatario = true;

            foreach ($chunks as $index => $chunk) {
                try {
                    $response = $this->whatsApp->sendText($to, $chunk);
                    $ok = (bool) ($response['ok'] ?? false);

                    Log::info('WhatsApp licencia vencida enviado', [
                        'to' => $to,
                        'parte' => ($index + 1) . '/' . count($chunks),
                        'ok' => $ok,
                        'status' => $response['status'] ?? null,
                    ]);

                    if ($ok) {
                        $enviados++;
                    } else {
                        $fallidos++;
                        $okDestinatario = false;
                    }
                } catch (\Throwable $e) {
                    $fallidos++;
                    $okDestinatario = false;

                    Log::error('Error enviando WhatsApp de licencia vencida', [
                        'to' => $to,
                        'parte' => ($index + 1) . '/' . count($chunks),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($okDestinatario) {
                $destinatariosConEnvio++;
            }
        }

        if ($destinatariosConEnvio > 0) {
            PersonalLicencia::query()
                ->whereIn('id', $licencias->pluck('id')->all())
                ->update(['vencimiento_notificado_at' => now()]);
        }

        return [
            'licencias' => $licencias->count(),
            'destinatarios' => count($destinatarios),
            'destinatarios_con_envio' => $destinatariosConEnvio,
            'mensajes_enviados' => $enviados,
            'mensajes_fallidos' => $fallidos,
            'partes_por_destinatario' => count($chunks),
        ];
    }

    public function mensaje(Collection $licencias, ?Carbon $fecha = null): string
    {
        $fecha = $fecha ?: Carbon::now('America/Mexico_City');
        $lineas = [
            'GUARDIA CIVIL',
            'ALERTA DE LICENCIAS VENCIDAS',
            '',
            'Corte: ' . $fecha->format('d/m/Y H:i'),
            'Total: ' . $licencias->count(),
            '',
        ];

        foreach ($licencias as $licencia) {
            if (!$licencia instanceof PersonalLicencia) {
                continue;
            }

            $personal = $licencia->personal;
            $diasVencida = $licencia->vigencia
                ? (int) $licencia->vigencia->diffInDays($fecha->copy()->startOfDay())
                : 0;

            $lineas[] = '- ' . $this->nombrePersonal($personal)
                . ' | ' . $licencia->tipo_label
                . ' | No. ' . ($licencia->numero ?: 'Sin numero')
                . ' | Vencio: ' . ($licencia->vigencia ? $licencia->vigencia->format('d/m/Y') : 'Sin fecha')
                . ' | Dias vencida: ' . $diasVencida
                . ' | Unidad: ' . (optional($personal ? $personal->unidad : null)->nombre ?: 'Sin unidad');
        }

        return implode("\n", $lineas);
    }

    private function chunks(string $mensaje): array
    {
        $max = 3500;

        if (mb_strlen($mensaje, 'UTF-8') <= $max) {
            return [$mensaje];
        }

        $lineas = explode("\n", $mensaje);
        $chunks = [];
        $current = '';

        foreach ($lineas as $linea) {
            $next = $current === '' ? $linea : $current . "\n" . $linea;

            if (mb_strlen($next, 'UTF-8') > $max && $current !== '') {
                $chunks[] = $current;
                $current = $linea;
            } else {
                $current = $next;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        $total = count($chunks);

        return array_map(function (string $chunk, int $index) use ($total) {
            return $total > 1
                ? 'Parte ' . ($index + 1) . '/' . $total . "\n\n" . $chunk
                : $chunk;
        }, $chunks, array_keys($chunks));
    }

    private function nombrePersonal(?Personal $personal): string
    {
        if (!$personal) {
            return 'Personal no encontrado';
        }

        $nombre = $personal->nombre_completo;

        return $nombre !== '' ? $nombre : ('Personal #' . $personal->id);
    }
}
