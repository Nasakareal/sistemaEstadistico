<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadVehiculo;
use Illuminate\Http\Request;
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

        $rules = $rulesMethod->invoke(
            new ConduceLegalidadController(),
            new ConduceLegalidadOperativo(['tipo_operativo' => 'conduce_legalidad'])
        );
        $vehiculo = new ConduceLegalidadVehiculo([
            'numero_inventario' => '00042',
        ]);

        $this->assertContains('required', $rules['vehiculos.*.numero_inventario']);
        $this->assertContains('regex:/^\d{5,}$/', $rules['vehiculos.*.numero_inventario']);
        $this->assertSame(['required', 'array', 'size:1'], $rules['vehiculos']);
        $this->assertSame(
            ['required', 'integer', 'exists:gruas,id'],
            $rules['vehiculos.*.corralon_id']
        );
        $this->assertSame('00042', $vehiculo->numero_inventario);
    }

    public function test_alcoholimetria_keeps_vehicle_optional(): void
    {
        $rulesMethod = new ReflectionMethod(
            ConduceLegalidadController::class,
            'capturaRules'
        );
        $rulesMethod->setAccessible(true);

        $rules = $rulesMethod->invoke(
            new ConduceLegalidadController(),
            new ConduceLegalidadOperativo(['tipo_operativo' => 'alcoholimetria'])
        );

        $this->assertSame(['nullable', 'array', 'max:1'], $rules['vehiculos']);
        $this->assertContains('nullable', $rules['vehiculos.*.numero_inventario']);
        $this->assertContains('regex:/^\d{5,}$/', $rules['vehiculos.*.numero_inventario']);
        $this->assertSame(
            ['nullable', 'integer', 'exists:gruas,id'],
            $rules['vehiculos.*.corralon_id']
        );
    }

    public function test_vehicle_payload_returns_inventory_number(): void
    {
        $vehiculo = new ConduceLegalidadVehiculo();
        $vehiculo->forceFill([
            'id' => 15,
            'numero_inventario' => '00042',
            'corralon_id' => 8,
            'corralon' => 'CORRALÓN MORELIA',
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

        $this->assertSame('00042', $payload['numero_inventario']);
        $this->assertSame(8, $payload['corralon_id']);
        $this->assertSame('CORRALÓN MORELIA', $payload['corralon']);
    }

}
