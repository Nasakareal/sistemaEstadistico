<?php

namespace Tests\Unit;

use App\Models\ConstanciaManejo;
use App\Services\ConstanciaManejoWhatsAppService;
use App\Services\WhatsAppCloudService;
use Tests\TestCase;

class ConstanciaManejoWhatsAppServiceTest extends TestCase
{
    public function test_no_intenta_enviar_si_la_integracion_esta_deshabilitada(): void
    {
        config(['services.whatsapp.constancias_manejo.enabled' => false]);
        $cloud = $this->cloudQueNoDebeInvocarse();
        $service = new ConstanciaManejoWhatsAppService($cloud);

        $resultado = $service->enviarConstanciaActivada(new ConstanciaManejo([
            'telefono' => '4431234567',
        ]));

        $this->assertFalse($resultado['sent']);
        $this->assertSame('deshabilitado', $resultado['status']);
    }

    public function test_omite_envio_cuando_no_hay_telefono(): void
    {
        config(['services.whatsapp.constancias_manejo.enabled' => true]);
        $cloud = $this->cloudQueNoDebeInvocarse();
        $service = new ConstanciaManejoWhatsAppService($cloud);

        $resultado = $service->enviarConstanciaActivada(new ConstanciaManejo([
            'telefono' => null,
        ]));

        $this->assertFalse($resultado['sent']);
        $this->assertSame('sin_telefono', $resultado['status']);
    }

    public function test_omite_envio_cuando_el_telefono_no_tiene_diez_digitos(): void
    {
        config(['services.whatsapp.constancias_manejo.enabled' => true]);
        $cloud = $this->cloudQueNoDebeInvocarse();
        $service = new ConstanciaManejoWhatsAppService($cloud);

        $resultado = $service->enviarConstanciaActivada(new ConstanciaManejo([
            'id' => 77,
            'telefono' => '443-123',
        ]));

        $this->assertFalse($resultado['sent']);
        $this->assertSame('telefono_invalido', $resultado['status']);
    }

    private function cloudQueNoDebeInvocarse(): WhatsAppCloudService
    {
        return new class extends WhatsAppCloudService {
            public function uploadMedia(string $filePath, string $mimeType = 'application/pdf'): array
            {
                throw new \RuntimeException('No se esperaba una llamada a Meta.');
            }
        };
    }
}
