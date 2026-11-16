<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SierseIntegrationController;
use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadFoto;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Hechos;
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
        $vehicle->setRelation('hechos', collect());
        $vehicle->setRelation('actividades', collect());

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
        $capture->setRelation('fotos', collect());
        $capture->setRelation('actividad', null);
        $vehicle = new ConduceLegalidadVehiculo(['placas' => 'PFH-123-A']);
        $vehicle->id = 20;
        $vehicle->setRelation('captura', $capture);

        $method = new ReflectionMethod(SierseIntegrationController::class, 'conduceVehiclePayload');
        $method->setAccessible(true);
        $payload = $method->invoke(new SierseIntegrationController(), $vehicle);

        $this->assertSame('CL-86-213', $payload['conduce_folio']);
    }

    public function test_gallery_lists_vehicle_hecho_activity_and_conduce_photos_without_storage_paths(): void
    {
        $vehicle = new Vehiculo(['fotos' => 'private/vehicle.jpg']);
        $vehicle->id = 10;
        $vehicle->syncOriginalAttribute('fotos');
        $vehicle->setRelation('servicio', null);

        $hecho = new Hechos(['foto_lugar' => 'private/hecho.jpg']);
        $hecho->id = 30;
        $vehicle->setRelation('hechos', collect([$hecho]));

        $activityPhoto = new ActividadFoto(['foto_path' => 'private/activity.jpg']);
        $activityPhoto->id = 41;
        $activity = new Actividad(['foto_path' => 'private/activity-main.jpg']);
        $activity->id = 40;
        $activity->setRelation('fotos', collect([$activityPhoto]));

        $conducePhoto = new ConduceLegalidadFoto(['foto_path' => 'private/conduce.jpg']);
        $conducePhoto->id = 51;
        $capture = new ConduceLegalidadCaptura();
        $capture->id = 50;
        $capture->setRelation('fotos', collect([$conducePhoto]));
        $activity->setRelation('conduceLegalidadCaptura', $capture);
        $vehicle->setRelation('actividades', collect([$activity]));

        $method = new ReflectionMethod(SierseIntegrationController::class, 'photoPayloads');
        $method->setAccessible(true);
        $photos = $method->invoke(new SierseIntegrationController(), 'vehicle_registry', $vehicle);
        $serialized = json_encode($photos->all(), JSON_UNESCAPED_SLASHES);

        $this->assertSame(
            ['vehicle', 'hecho', 'activity', 'conduce_legalidad'],
            $photos->pluck('type')->unique()->values()->all()
        );
        $this->assertStringNotContainsString('private/', $serialized);
        $this->assertStringContainsString('/fotos/hecho_lugar_30', $serialized);
        $this->assertStringContainsString('/fotos/actividad_foto_41', $serialized);
        $this->assertStringContainsString('/fotos/conduce_foto_51', $serialized);
    }
}
