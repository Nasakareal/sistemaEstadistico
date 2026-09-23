<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadVehiculo;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadNumeroInventarioTest extends TestCase
{
    public function test_inventory_number_is_validated_and_mass_assignable(): void
    {
        $rulesMethod = new ReflectionMethod(
            ConduceLegalidadController::class,
            'capturaRules'
        );
        $rulesMethod->setAccessible(true);

        $rules = $rulesMethod->invoke(new ConduceLegalidadController());
        $vehiculo = new ConduceLegalidadVehiculo([
            'numero_inventario' => 'INV-2026-0042',
        ]);

        $this->assertSame(
            ['nullable', 'string', 'max:100'],
            $rules['vehiculos.*.numero_inventario']
        );
        $this->assertSame('INV-2026-0042', $vehiculo->numero_inventario);
    }

    public function test_vehicle_payload_returns_inventory_number(): void
    {
        $vehiculo = new ConduceLegalidadVehiculo();
        $vehiculo->forceFill([
            'id' => 15,
            'numero_inventario' => 'INV-2026-0042',
        ]);
        $vehiculo->setRelation('infraccion', null);

        $payloadMethod = new ReflectionMethod(
            ConduceLegalidadController::class,
            'vehiculoPayload'
        );
        $payloadMethod->setAccessible(true);
        $payload = $payloadMethod->invoke(
            new ConduceLegalidadController(),
            $vehiculo
        );

        $this->assertSame('INV-2026-0042', $payload['numero_inventario']);
    }
}
