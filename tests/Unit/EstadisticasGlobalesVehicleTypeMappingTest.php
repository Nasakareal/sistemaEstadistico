<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasGlobalesController;
use ReflectionMethod;
use Tests\TestCase;

class EstadisticasGlobalesVehicleTypeMappingTest extends TestCase
{
    public function test_clasifica_tipos_legacy_de_peritos_en_categorias_generales(): void
    {
        $cases = [
            'AUTOMOVIL' => 'automovil',
            'CAMIONETA CARGA' => 'camioneta',
            'CAMIONETA DE PASAJEROS' => 'camioneta',
            'MOTOCICLETA' => 'motocicleta',
            'sin datos' => 'NO ESPECIFICADO',
            'CAMION DE CARGA' => 'camion',
            'CAMION URBANO PASAJEROS' => 'camion',
            'TRACTOR' => 'maquinaria',
            'MICROBÚS' => 'camion',
            'BICICLETA' => 'bicicleta',
            'OMNIBUS' => 'camion',
            'FERROCARRIL' => 'tren',
            'SEMOVIENTE' => 'semoviente',
            'OTRO' => 'OTRO',
        ];

        foreach ($cases as $tipo => $expected) {
            $this->assertSame($expected, $this->invokePrivate('tipoGeneralFromTipo', $tipo), $tipo);
        }
    }

    public function test_filtros_de_tipo_general_incluyen_aliases_legacy(): void
    {
        $this->assertContains('AUTOMOVIL', $this->invokePrivate('carroceriasFromTipoGeneral', 'automovil'));
        $this->assertContains('CAMIONETA CARGA', $this->invokePrivate('carroceriasFromTipoGeneral', 'camioneta'));
        $this->assertContains('CAMION DE CARGA', $this->invokePrivate('carroceriasFromTipoGeneral', 'camion'));
        $this->assertContains('MOTOCICLETA', $this->invokePrivate('carroceriasFromTipoGeneral', 'motocicleta'));
        $this->assertContains('sin datos', $this->invokePrivate('carroceriasFromTipoGeneral', 'NO ESPECIFICADO'));
    }

    private function invokePrivate(string $methodName, string $value)
    {
        $method = new ReflectionMethod(EstadisticasGlobalesController::class, $methodName);
        $method->setAccessible(true);

        return $method->invoke(new EstadisticasGlobalesController(), $value);
    }
}
