<?php

namespace Tests\Unit;

use App\Services\WhatsAppCloudService;
use Tests\TestCase;

class WhatsAppCloudServiceTest extends TestCase
{
    public function test_send_template_sanitiza_parametros_de_texto_para_meta(): void
    {
        $service = new class extends WhatsAppCloudService {
            protected function request(array $payload): array
            {
                return [
                    'ok' => true,
                    'status' => 200,
                    'payload' => $payload,
                ];
            }
        };

        $response = $service->sendTemplate('5214431234567', 'template_prueba', [
            "Linea uno\n\tLinea dos     final",
        ]);

        $parameter = $response['payload']['template']['components'][0]['parameters'][0]['text'];

        $this->assertSame('Linea uno Linea dos final', $parameter);
        $this->assertDoesNotMatchRegularExpression('/[\r\n\t]/', $parameter);
        $this->assertDoesNotMatchRegularExpression('/ {5,}/', $parameter);
    }

    public function test_send_document_template_sanitiza_parametros_de_texto_para_meta(): void
    {
        $service = new class extends WhatsAppCloudService {
            protected function request(array $payload): array
            {
                return [
                    'ok' => true,
                    'status' => 200,
                    'payload' => $payload,
                ];
            }
        };

        $response = $service->sendDocumentTemplate(
            '5214431234567',
            'template_documento',
            'media-id',
            'archivo.pdf',
            ["A\tB\nC      D"]
        );

        $parameter = $response['payload']['template']['components'][1]['parameters'][0]['text'];

        $this->assertSame('A B C D', $parameter);
        $this->assertDoesNotMatchRegularExpression('/[\r\n\t]/', $parameter);
        $this->assertDoesNotMatchRegularExpression('/ {5,}/', $parameter);
    }
}
