<?php

namespace App\Services;

use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\LicenciaPuntoMovimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LicenciaPuntosWhatsAppNotificationService
{
    /** @var WhatsAppCloudService */
    private $whatsApp;

    public function __construct(WhatsAppCloudService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    public function notificarDescuento(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        LicenciaPuntoInfraccion $infraccion,
        Carbon $fecha
    ): array {
        if (!$this->enabled() || !$this->deduccionEnabled()) {
            return $this->skipped('notificaciones_deduccion_desactivadas');
        }

        $template = (string) config('services.whatsapp.licencias_puntos.deduccion_template', '');

        return $this->enviarTemplate(
            'descuento_puntos',
            $cuenta,
            $movimiento,
            $template,
            [
                $cuenta->titular_nombre,
                $cuenta->numero_licencia,
                $infraccion->nombre,
                abs((int) $movimiento->puntos),
                (int) $movimiento->saldo_nuevo,
                $fecha->format('d/m/Y H:i'),
                $this->consultaUrl($cuenta),
            ],
            'Notificacion WhatsApp por descuento de puntos'
        );
    }

    public function notificarAgotamiento(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        LicenciaPuntoInfraccion $infraccion,
        Carbon $fecha
    ): array {
        if (!$this->enabled() || !$this->agotamientoEnabled()) {
            return $this->skipped('notificaciones_agotamiento_desactivadas');
        }

        $template = (string) config('services.whatsapp.licencias_puntos.agotamiento_template', '');

        $response = $this->enviarTemplate(
            'agotamiento_puntos',
            $cuenta,
            $movimiento,
            $template,
            [
                $cuenta->titular_nombre,
                $cuenta->numero_licencia,
                $infraccion->nombre,
                $cuenta->expediente_folio ?: 'SIN EXPEDIENTE',
                $cuenta->oficio_folio ?: 'SIN OFICIO',
                $fecha->format('d/m/Y H:i'),
                $this->consultaUrl($cuenta),
            ],
            'Notificacion WhatsApp por licencia en cero'
        );

        if (($response['ok'] ?? false) && !$cuenta->titular_notificado_at) {
            $cuenta->forceFill(['titular_notificado_at' => Carbon::now('America/Mexico_City')])->save();
        }

        return $response;
    }

    private function enviarTemplate(
        string $tipo,
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        string $template,
        array $params,
        string $descripcion
    ): array {
        $telefono = $this->telefono($cuenta);
        $language = (string) config('services.whatsapp.licencias_puntos.template_language', 'es_MX');

        if ($telefono === '') {
            return $this->registrarResultado($cuenta, $movimiento, $tipo, [
                'ok' => false,
                'skipped' => true,
                'reason' => 'telefono_no_registrado',
                'template' => $template,
                'template_language' => $language,
            ], $descripcion);
        }

        if ($template === '') {
            return $this->registrarResultado($cuenta, $movimiento, $tipo, [
                'ok' => false,
                'skipped' => true,
                'reason' => 'template_no_configurada',
                'template' => $template,
                'template_language' => $language,
            ], $descripcion);
        }

        try {
            $response = $this->whatsApp->sendTemplate($telefono, $template, $params, $language);
        } catch (\Throwable $e) {
            report($e);

            $response = [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => $e->getMessage()],
            ];
        }

        return $this->registrarResultado($cuenta, $movimiento, $tipo, array_merge($response, [
            'template' => $template,
            'template_language' => $language,
            'telefono_destino' => $telefono,
        ]), $descripcion);
    }

    private function registrarResultado(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        string $tipo,
        array $response,
        string $descripcion
    ): array {
        $ok = (bool) ($response['ok'] ?? false);
        $skipped = (bool) ($response['skipped'] ?? false);
        $referencia = 'whatsapp_' . $tipo;

        try {
            $cuenta->movimientos()->create([
                'user_id' => null,
                'tipo' => 'notificacion_whatsapp',
                'puntos' => 0,
                'saldo_anterior' => (int) $movimiento->saldo_nuevo,
                'saldo_nuevo' => (int) $movimiento->saldo_nuevo,
                'fecha_movimiento' => Carbon::now('America/Mexico_City'),
                'referencia' => $referencia,
                'descripcion' => $descripcion . ($ok ? ' enviada.' : ($skipped ? ' omitida.' : ' fallida.')),
                'metadata' => [
                    'tipo_notificacion' => $tipo,
                    'movimiento_origen_id' => $movimiento->id,
                    'ok' => $ok,
                    'skipped' => $skipped,
                    'reason' => $response['reason'] ?? null,
                    'status' => $response['status'] ?? null,
                    'template' => $response['template'] ?? null,
                    'template_language' => $response['template_language'] ?? null,
                    'telefono_destino' => $response['telefono_destino'] ?? null,
                    'body' => $response['body'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::warning('No se pudo registrar notificacion WhatsApp de puntos', [
                'cuenta_id' => $cuenta->id,
                'movimiento_id' => $movimiento->id,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function telefono(LicenciaPuntoCuenta $cuenta): string
    {
        return preg_replace('/\D+/', '', (string) $cuenta->telefono) ?: '';
    }

    private function consultaUrl(LicenciaPuntoCuenta $cuenta): string
    {
        return route('licencias_puntos.consulta', ['numero_licencia' => $cuenta->numero_licencia]);
    }

    private function enabled(): bool
    {
        return (bool) config('services.whatsapp.licencias_puntos.enabled', false);
    }

    private function deduccionEnabled(): bool
    {
        return (bool) config('services.whatsapp.licencias_puntos.notify_deduccion', true);
    }

    private function agotamientoEnabled(): bool
    {
        return (bool) config('services.whatsapp.licencias_puntos.notify_agotamiento', true);
    }

    private function skipped(string $reason): array
    {
        return [
            'ok' => false,
            'skipped' => true,
            'reason' => $reason,
        ];
    }
}
