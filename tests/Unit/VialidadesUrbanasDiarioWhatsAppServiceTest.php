<?php

namespace Tests\Unit;

use App\Services\VialidadesUrbanasDiarioWhatsAppService;
use App\Console\Commands\EnviarVialidadesUrbanasDiarioWhatsApp;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasDiarioWhatsAppServiceTest extends TestCase
{
    public function test_usa_el_corte_configurado_de_vialidades_urbanas(): void
    {
        config([
            'app.schedule_timezone' => 'Etc/GMT+6',
            'cortes.hora_corte_vialidades_urbanas' => '17:00:00',
        ]);

        $service = new VialidadesUrbanasDiarioWhatsAppService();
        [$inicio, $fin] = $service->rango(Carbon::parse('2026-06-05 17:00:00', 'Etc/GMT+6'));

        $this->assertSame('2026-06-04 17:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-05 17:00:00', $fin->format('Y-m-d H:i:s'));
    }

    public function test_el_mensaje_demo_muestra_el_corte_configurado(): void
    {
        config([
            'app.schedule_timezone' => 'Etc/GMT+6',
            'cortes.hora_corte_vialidades_urbanas' => '17:00:00',
        ]);

        $service = new VialidadesUrbanasDiarioWhatsAppService();
        $resumen = $service->generarDemo(Carbon::parse('2026-06-05 17:00:00', 'Etc/GMT+6'));

        $this->assertStringContainsString(
            'ACTIVIDADES RELEVANTES DE LAS 17:00 HORAS DEL 04/06/2026 A LAS 17:00 HORAS DEL 05/06/2026',
            $resumen['mensaje']
        );
    }

    public function test_ignora_fragmentos_cortos_en_destinatarios(): void
    {
        $command = new EnviarVialidadesUrbanasDiarioWhatsApp();
        $method = new ReflectionMethod($command, 'recipients');
        $method->setAccessible(true);

        $this->assertSame(['5214434765057'], $method->invoke($command, '5214434765057,52'));
    }
}
