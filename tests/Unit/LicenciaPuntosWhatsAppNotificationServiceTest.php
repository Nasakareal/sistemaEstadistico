<?php

namespace Tests\Unit;

use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\LicenciaPuntoMovimiento;
use App\Services\LicenciaPuntosWhatsAppNotificationService;
use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Tests\TestCase;

class LicenciaPuntosWhatsAppNotificationServiceTest extends TestCase
{
    public function test_descuento_envia_fundamento_legal_como_cuarta_variable(): void
    {
        config([
            'services.whatsapp.licencias_puntos.enabled' => true,
            'services.whatsapp.licencias_puntos.notify_deduccion' => true,
            'services.whatsapp.licencias_puntos.deduccion_template' => 'licencia_puntos_descuento',
            'services.whatsapp.licencias_puntos.template_language' => 'es_MX',
        ]);

        $whatsApp = new class extends WhatsAppCloudService {
            public array $calls = [];

            public function sendTemplate(string $to, string $templateName, array $bodyParameters = [], string $language = 'es_MX'): array
            {
                $this->calls[] = [
                    'to' => $to,
                    'template' => $templateName,
                    'params' => $bodyParameters,
                    'language' => $language,
                ];

                return ['ok' => true, 'status' => 200, 'body' => []];
            }
        };

        $service = new LicenciaPuntosWhatsAppNotificationService($whatsApp);
        $fundamento = 'Fundamentado en el Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.';

        $response = $service->notificarDescuento(
            new LicenciaPuntoCuenta([
                'titular_nombre' => 'JUAN PEREZ LOPEZ',
                'numero_licencia' => 'MICHOACAN12345',
                'telefono' => '4431234567',
            ]),
            new LicenciaPuntoMovimiento([
                'puntos' => -1,
                'saldo_nuevo' => 7,
            ]),
            new LicenciaPuntoInfraccion([
                'nombre' => 'Celular al conducir',
                'fundamento_legal' => $fundamento,
            ]),
            Carbon::parse('2026-06-17 11:30:00', 'America/Mexico_City')
        );

        $this->assertTrue($response['ok']);
        $this->assertCount(1, $whatsApp->calls);

        $call = $whatsApp->calls[0];

        $this->assertSame('5214431234567', $call['to']);
        $this->assertSame('licencia_puntos_descuento', $call['template']);
        $this->assertSame('es_MX', $call['language']);
        $this->assertCount(8, $call['params']);
        $this->assertSame('JUAN PEREZ LOPEZ', $call['params'][0]);
        $this->assertSame('MICHOACAN12345', $call['params'][1]);
        $this->assertSame('Celular al conducir', $call['params'][2]);
        $this->assertSame($fundamento, $call['params'][3]);
        $this->assertSame(1, $call['params'][4]);
        $this->assertSame(7, $call['params'][5]);
        $this->assertSame('17/06/2026 11:30', $call['params'][6]);
        $this->assertStringContainsString('consulta-puntos-licencia', $call['params'][7]);
    }
}
