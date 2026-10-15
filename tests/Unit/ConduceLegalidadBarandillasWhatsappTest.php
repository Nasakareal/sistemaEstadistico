<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Services\IphPuestaDisposicionDocxService;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadBarandillasWhatsappTest extends TestCase
{
    public function test_endpoint_rnd_no_contacta_a_justicia_civica(): void
    {
        $whatsApp = Mockery::mock(WhatsAppCloudService::class);
        $whatsApp->shouldNotReceive('sendTemplate');

        $response = (new ConduceLegalidadController())->sendRndChatbot(
            Request::create('/api/conduce-legalidad/operativos/10/rnd-chatbot', 'POST'),
            new ConduceLegalidadOperativo(),
            $whatsApp
        );

        $this->assertSame(410, $response->status());
        $this->assertFalse($response->getData(true)['ok']);
    }

    public function test_no_envia_documentos_a_justicia_civica_aunque_la_configuracion_anterior_siga_activa(): void
    {
        config()->set('services.whatsapp.conduce_legalidad.barandillas_enabled', true);
        config()->set('services.whatsapp.conduce_legalidad.barandillas_to', '5214433163728');
        config()->set('services.whatsapp.conduce_legalidad.barandillas_boleta_template', 'aviso_barandillas_conduce_v1');
        config()->set('services.whatsapp.conduce_legalidad.barandillas_iph_template', 'iph_barandillas_conduce_v1');

        [$operativo, $captura] = $this->capturaConVehiculo(true);
        $whatsApp = Mockery::mock(WhatsAppCloudService::class);
        $whatsApp->shouldNotReceive('uploadMedia');
        $whatsApp->shouldNotReceive('sendDocumentTemplate');

        $docx = Mockery::mock(IphPuestaDisposicionDocxService::class);
        $docx->shouldNotReceive('generarConduceLegalidadBarandillasSoloVehiculo');

        $resultado = $this->invocarNotificacion($operativo, $captura, $whatsApp, $docx);

        $this->assertTrue($resultado['ok']);
        $this->assertFalse($resultado['enviado']);
        $this->assertStringContainsString('desactivado', $resultado['message']);
    }

    public function test_no_avisa_a_barandillas_si_no_hay_vehiculo_remitido(): void
    {
        config()->set('services.whatsapp.conduce_legalidad.barandillas_enabled', true);

        [$operativo, $captura] = $this->capturaConVehiculo(false);
        $whatsApp = Mockery::mock(WhatsAppCloudService::class);
        $whatsApp->shouldNotReceive('uploadMedia');
        $docx = Mockery::mock(IphPuestaDisposicionDocxService::class);
        $docx->shouldNotReceive('generarConduceLegalidadBarandillasSoloVehiculo');

        $resultado = $this->invocarNotificacion($operativo, $captura, $whatsApp, $docx);

        $this->assertTrue($resultado['ok']);
        $this->assertFalse($resultado['enviado']);
    }

    private function capturaConVehiculo(bool $remitido): array
    {
        $operativo = new ConduceLegalidadOperativo([
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Madero',
            'tipo_operativo' => 'conduce_legalidad',
            'unidad_id' => 3,
        ]);
        $operativo->id = 10;
        $operativo->setRelation('creador', null);

        $captura = new ConduceLegalidadCaptura([
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Madero',
            'fecha' => '2026-09-29',
            'hora' => '18:30',
            'fundamento_legal' => 'Artículo 402.',
            'agente_nombre' => 'Elemento de prueba',
        ]);
        $captura->id = 20;

        $vehiculo = new ConduceLegalidadVehiculo([
            'marca' => 'Honda',
            'linea' => 'Cargo',
            'tipo' => 'Motocicleta',
            'placas' => 'ABC123',
            'retencion_vehiculo' => $remitido,
            'corralon' => $remitido ? 'Barandillas' : null,
        ]);
        $vehiculo->setRelation('infraccion', null);
        $vehiculo->setRelation('gruaRelacion', null);
        $vehiculo->setRelation('corralonRelacion', null);

        $persona = new ConduceLegalidadPersona(['nombre' => 'Persona de prueba']);
        $persona->setRelation('infraccion', null);

        $captura->setRelation('creador', null);
        $captura->setRelation('unidad', null);
        $captura->setRelation('delegacion', null);
        $captura->setRelation('infraccion', null);
        $captura->setRelation('fundamentos', new Collection());
        $captura->setRelation('vehiculos', new Collection([$vehiculo]));
        $captura->setRelation('personas', new Collection([$persona]));
        $captura->setRelation('fotos', new Collection());

        return [$operativo, $captura];
    }

    private function invocarNotificacion(
        ConduceLegalidadOperativo $operativo,
        ConduceLegalidadCaptura $captura,
        WhatsAppCloudService $whatsApp,
        IphPuestaDisposicionDocxService $docx
    ): array {
        $method = new ReflectionMethod(ConduceLegalidadController::class, 'notificarBarandillasWhatsApp');
        $method->setAccessible(true);

        return $method->invoke(
            new ConduceLegalidadController(),
            $operativo,
            $captura,
            null,
            $whatsApp,
            $docx
        );
    }
}
