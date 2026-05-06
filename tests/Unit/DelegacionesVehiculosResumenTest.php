<?php

namespace Tests\Unit;

use App\Services\Delegaciones\Hojas\RegionalSheetService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DelegacionesVehiculosResumenTest extends TestCase
{
    public function test_resumen_de_vehiculos_no_duplica_motocicletas_como_particulares(): void
    {
        $resumen = [
            'particulares' => 0,
            'publicos' => 0,
            'motos' => 0,
            'oficiales' => 0,
        ];

        $service = new RegionalSheetService();
        $method = (new ReflectionClass($service))->getMethod('sumarResumenVehiculo');
        $method->setAccessible(true);

        $method->invokeArgs($service, [&$resumen, 'MOTOCICLETA', 'PARTICULAR']);
        $method->invokeArgs($service, [&$resumen, 'SEDAN', 'PARTICULAR']);
        $method->invokeArgs($service, [&$resumen, 'CAMIONETA', 'SERVICIO PUBLICO']);
        $method->invokeArgs($service, [&$resumen, 'PICK UP', 'OFICIAL']);

        $this->assertSame([
            'particulares' => 1,
            'publicos' => 1,
            'motos' => 1,
            'oficiales' => 1,
        ], $resumen);
    }
}
