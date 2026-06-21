<?php

namespace Tests\Unit;

use App\Console\Commands\EnviarCarreterasGuardianesWhatsApp;
use App\Services\CarreterasGuardianesDiarioWhatsAppService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class CarreterasGuardianesDiarioWhatsAppServiceTest extends TestCase
{
    public function test_usa_el_corte_de_17_a_17_horas(): void
    {
        config([
            'app.schedule_timezone' => 'Etc/GMT+6',
            'cortes.hora_corte_carreteras' => '17:00:00',
        ]);

        $service = new CarreterasGuardianesDiarioWhatsAppService();
        [$inicio, $fin] = $service->rango(Carbon::parse('2026-03-05 17:20:00', 'Etc/GMT+6'));

        $this->assertSame('2026-03-04 17:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-05 17:00:00', $fin->format('Y-m-d H:i:s'));
    }

    public function test_el_demo_respeta_fecha_y_hora_de_emision(): void
    {
        config([
            'app.schedule_timezone' => 'Etc/GMT+6',
            'cortes.hora_corte_carreteras' => '17:00:00',
        ]);

        $service = new CarreterasGuardianesDiarioWhatsAppService();
        $resumen = $service->generarDemo(Carbon::parse('2026-03-05 17:20:00', 'Etc/GMT+6'));

        $this->assertStringContainsString('05/03/2026         17:20 hs.', $resumen['mensaje']);
        $this->assertStringContainsString('DESTACAMENTO MORELIA', $resumen['mensaje']);
    }

    public function test_las_plantillas_estaticas_salen_en_tres_partes(): void
    {
        $service = new CarreterasGuardianesDiarioWhatsAppService();
        $resumen = $service->generarDemo(Carbon::parse('2026-03-05 17:20:00', 'Etc/GMT+6'));

        $this->assertCount(3, $resumen['template_parts']);
        $this->assertCount(14, $resumen['template_parts'][0]['parameters']);
        $this->assertCount(29, $resumen['template_parts'][1]['parameters']);
        $this->assertCount(24, $resumen['template_parts'][2]['parameters']);
        $this->assertStringContainsString('PSV (PUESTO DE SEGURIDAD Y VIGILANCIA): 00', $resumen['template_parts'][0]['body']);
        $this->assertStringContainsString('DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES: 00', $resumen['template_parts'][1]['body']);
        $this->assertStringContainsString('TOTALES:', $resumen['template_parts'][2]['body']);
    }

    public function test_ignora_fragmentos_cortos_en_destinatarios(): void
    {
        $command = new EnviarCarreterasGuardianesWhatsApp();
        $method = new ReflectionMethod($command, 'recipients');
        $method->setAccessible(true);

        $this->assertSame(['5214434765057'], $method->invoke($command, '5214434765057,52'));
    }
}
