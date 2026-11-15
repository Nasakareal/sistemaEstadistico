<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SierseIntegrationController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Vehiculo;
use ReflectionMethod;
use Tests\TestCase;

class SierseVehicleSearchTest extends TestCase
{
    public function test_identifier_normalization_ignores_common_separators(): void
    {
        $method = new ReflectionMethod(SierseIntegrationController::class, 'normalizeIdentifier');
        $method->setAccessible(true);

        $this->assertSame(
            'PFH123A',
            $method->invoke(new SierseIntegrationController(), 'pfh-123 a.')
        );
    }

    public function test_regular_vehicle_payload_has_no_driver_personal_data(): void
    {
        $vehicle = new Vehiculo([
            'client_uuid' => 'vehicle-test-uuid',
            'marca' => 'Nissan',
            'placas' => 'PFH-123-A',
            'serie' => '3N1TEST',
            'numero_inventario_grua' => '00086',
        ]);
        $vehicle->id = 10;
        $vehicle->setRelation('servicio', null);
        $vehicle->setRelation('liberacionCorralon', null);

        $method = new ReflectionMethod(SierseIntegrationController::class, 'regularVehiclePayload');
        $method->setAccessible(true);
        $payload = $method->invoke(new SierseIntegrationController(), $vehicle);
        $serialized = json_encode($payload);

        $this->assertSame('PFH-123-A', $payload['plate']);
        $this->assertStringNotContainsString('conductor', $serialized);
        $this->assertStringNotContainsString('telefono', $serialized);
        $this->assertStringNotContainsString('domicilio', $serialized);
    }

    public function test_conduce_payload_uses_reserved_folio_number(): void
    {
        $operational = new ConduceLegalidadOperativo();
        $operational->id = 86;
        $capture = new ConduceLegalidadCaptura(['folio_numero' => 213]);
        $capture->id = 999;
        $capture->setRelation('operativo', $operational);
        $vehicle = new ConduceLegalidadVehiculo(['placas' => 'PFH-123-A']);
        $vehicle->id = 20;
        $vehicle->setRelation('captura', $capture);

        $method = new ReflectionMethod(SierseIntegrationController::class, 'conduceVehiclePayload');
        $method->setAccessible(true);
        $payload = $method->invoke(new SierseIntegrationController(), $vehicle);

        $this->assertSame('CL-86-213', $payload['conduce_folio']);
    }
}
