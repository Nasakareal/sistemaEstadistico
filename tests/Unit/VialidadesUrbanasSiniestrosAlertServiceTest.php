<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Services\VialidadesUrbanasSiniestrosAlertService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class VialidadesUrbanasSiniestrosAlertServiceTest extends TestCase
{
    public function test_detecta_abanderamiento_accidente_de_vialidades_urbanas(): void
    {
        $service = $this->service();
        $actividad = $this->actividad('Abanderamientos', 'Accidentes');

        $this->assertTrue($service->debeNotificarActividad($actividad));
    }

    public function test_detecta_reporte_c5i_hecho_de_transito_de_vialidades_urbanas(): void
    {
        $service = $this->service();
        $actividad = $this->actividad('Reportes de C5i', 'Hechos de tránsito');

        $this->assertTrue($service->debeNotificarActividad($actividad));
    }

    public function test_no_notifica_si_no_es_vialidades_urbanas(): void
    {
        $service = $this->service();
        $actividad = $this->actividad('Reportes de C5i', 'Hechos de tránsito', 4);

        $this->assertFalse($service->debeNotificarActividad($actividad));
    }

    public function test_no_notifica_otros_reportes_c5i(): void
    {
        $service = $this->service();
        $actividad = $this->actividad('Reportes de C5i', 'Apoyo ciudadano');

        $this->assertFalse($service->debeNotificarActividad($actividad));
    }

    private function actividad(string $categoria, string $subcategoria, int $unidadId = 5): Actividad
    {
        $actividad = new Actividad([
            'unidad_org_id' => $unidadId,
        ]);

        $actividad->setRelation('categoria', new ActividadCategoria([
            'nombre' => $categoria,
        ]));
        $actividad->setRelation('subcategoria', new ActividadSubcategoria([
            'nombre' => $subcategoria,
        ]));

        return $actividad;
    }

    private function service(): VialidadesUrbanasSiniestrosAlertService
    {
        /** @var WhatsAppCloudService&MockObject $whatsApp */
        $whatsApp = $this->createMock(WhatsAppCloudService::class);
        /** @var WhatsAppSendGuard&MockObject $sendGuard */
        $sendGuard = $this->createMock(WhatsAppSendGuard::class);

        return new VialidadesUrbanasSiniestrosAlertService($whatsApp, $sendGuard);
    }
}
