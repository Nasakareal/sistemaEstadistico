<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\EstadisticasGlobalesController;
use ReflectionMethod;
use Tests\TestCase;

class ApiEstadisticasGlobalesVehicleTypeMappingTest extends TestCase
{
    public function test_prioriza_tipo_general_y_clasifica_carrocerias_legacy(): void
    {
        $this->assertSame('motocicleta', $this->tipoGeneral('motocicleta', 'Trabajo'));
        $this->assertSame('motocicleta', $this->tipoGeneral('', 'Scooter'));
        $this->assertSame('motocicleta', $this->tipoGeneral('', 'MOTONETA'));
        $this->assertSame('camion', $this->tipoGeneral('', 'Camión de carga'));
        $this->assertSame('maquinaria', $this->tipoGeneral('', 'Tractor John Deere'));
        $this->assertSame('no especificado', $this->tipoGeneral('', ''));
    }

    public function test_taxonomia_de_motocicleta_incluye_todas_sus_variantes(): void
    {
        $types = $this->invokePrivate('carroceriasTipoGeneral', ['motocicleta']);

        $this->assertContains('Trabajo', $types);
        $this->assertContains('Scooter', $types);
        $this->assertContains('Pista', $types);
        $this->assertContains('Naked', $types);
        $this->assertContains('Motoneta', $types);
    }

    private function tipoGeneral(string $stored, string $type): string
    {
        return $this->invokePrivate('tipoGeneralVehiculo', [$stored, $type]);
    }

    private function invokePrivate(string $methodName, array $arguments)
    {
        $method = new ReflectionMethod(EstadisticasGlobalesController::class, $methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(new EstadisticasGlobalesController(), $arguments);
    }
}
