<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadMotorcycleScopeTest extends TestCase
{
    /**
     * @dataProvider vehicleScopeProvider
     */
    public function test_conduce_legalidad_accepts_only_general_or_motorcycle_scope(
        ?string $scope,
        bool $expected
    ): void {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'ambitoFundamentoAplicaConduceLegalidad'
        );
        $method->setAccessible(true);

        $actual = $method->invoke(new ConduceLegalidadController(), $scope);

        $this->assertSame($expected, $actual);
    }

    public function vehicleScopeProvider(): array
    {
        return [
            'missing is treated as general' => [null, true],
            'general' => ['general', true],
            'motorcycle' => ['motocicleta', true],
            'car' => ['automovil', false],
            'truck' => ['carga', false],
            'public transport' => ['transporte_publico', false],
        ];
    }

    public function test_backend_rejects_truck_in_motorcycle_operation(): void
    {
        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'assertVehiculosCorrespondenOperativo'
        );
        $method->setAccessible(true);

        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill(['tipo_operativo' => 'conduce_legalidad']);

        $this->expectException(ValidationException::class);
        $method->invoke(new ConduceLegalidadController(), [
            'vehiculos' => [['tipo_general' => 'carga']],
        ], $operativo);
    }
}
