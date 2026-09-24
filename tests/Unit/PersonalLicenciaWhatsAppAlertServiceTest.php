<?php

namespace Tests\Unit;

use App\Services\PersonalLicenciaWhatsAppAlertService;
use App\Services\WhatsAppCloudService;
use Tests\TestCase;

class PersonalLicenciaWhatsAppAlertServiceTest extends TestCase
{
    public function test_usa_los_destinatarios_de_carreteras(): void
    {
        config([
            'services.whatsapp.carreteras_guardianes.to' => '5214434765057,5214433281661',
            'services.whatsapp.oficios.terminos_to' => '5214434101796,5214432219726',
        ]);

        $service = new PersonalLicenciaWhatsAppAlertService(new WhatsAppCloudService());

        $this->assertSame(
            ['5214434765057', '5214433281661'],
            $service->destinatarios()
        );
    }

    public function test_el_override_del_comando_sigue_teniendo_prioridad(): void
    {
        config([
            'services.whatsapp.carreteras_guardianes.to' => '5214434765057,5214433281661',
        ]);

        $service = new PersonalLicenciaWhatsAppAlertService(new WhatsAppCloudService());

        $this->assertSame(
            ['5214430000000', '5214430000001'],
            $service->destinatarios('5214430000000; 5214430000001')
        );
    }
}
