<?php

namespace Tests\Unit;

use App\Models\Patrulla;
use App\Services\VialidadesUrbanas\Hojas\EstadoVehicularSheetService;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasEstadoVehicularSheetServiceTest extends TestCase
{
    public function test_clasifica_patrullas_de_la_hoja_vehicular(): void
    {
        $this->assertSame('CHARGER', $this->clasificar('DODGE', 'CHARGER'));
        $this->assertSame('FORD 150', $this->clasificar('FORD', 'F-150'));
        $this->assertSame('CAMIONETA F250', $this->clasificar('FORD', 'F250'));
        $this->assertSame('RAM DODGE 1500', $this->clasificar('RAM', '1500'));
        $this->assertSame('JEEP PATRIOT', $this->clasificar('JEEP', 'PATRIOT'));
        $this->assertSame('ECO SPORT', $this->clasificar('FORD', 'ECO SPORT'));
        $this->assertSame('TSURU', $this->clasificar('NISSAN', 'TSURU'));
        $this->assertSame('PLATINA', $this->clasificar('NISSAN', 'PLATINA'));
        $this->assertSame('KAWASAKY KLR650', $this->clasificar('KAWASAKI', 'KLR 650', 'MOTO'));
        $this->assertSame('KAWASAKY ER-6N', $this->clasificar('KAWASAKI', 'ER6N', 'MOTO'));
    }

    public function test_patrullas_fuera_del_catalogo_cuentan_como_charger(): void
    {
        $this->assertSame('CHARGER', $this->clasificar('VOLKSWAGEN', 'JETTA'));
        $this->assertSame('CHARGER', $this->clasificar('CFMOTO', '800 EXPLORER', 'MOTO'));
    }

    private function clasificar(string $marca, string $linea, ?string $tipo = null): string
    {
        $method = new ReflectionMethod(EstadoVehicularSheetService::class, 'vehiculoOficial');
        $method->setAccessible(true);

        return $method->invoke(new EstadoVehicularSheetService(), new Patrulla([
            'tipo' => $tipo,
            'marca' => $marca,
            'linea' => $linea,
            'numero_economico' => 'TEST',
        ]));
    }
}
