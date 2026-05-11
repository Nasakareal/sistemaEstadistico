<?php

namespace Tests\Unit;

use App\Services\ResumenTodasUnidadesWhatsAppService;
use PHPUnit\Framework\TestCase;

class ResumenTodasUnidadesWhatsAppServiceTest extends TestCase
{
    public function test_template_chunks_are_numbered_and_respect_limit(): void
    {
        $service = new ResumenTodasUnidadesWhatsAppService();
        $mensaje = "ENCABEZADO\n\n"
            . str_repeat('OPERATIVO LARGO CON DATOS DE GUARDIAS CIVILES Y CRP. ', 20)
            . "\n\nCIERRE";

        $chunks = $service->whatsAppTemplateChunks($mensaje, 500);

        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $index => $chunk) {
            $this->assertSame($index + 1, $chunk['part']);
            $this->assertSame(count($chunks), $chunk['total']);
            $this->assertLessThanOrEqual(500, mb_strlen($chunk['body'], 'UTF-8'));
            $this->assertSame([
                (string) ($index + 1),
                (string) count($chunks),
                $chunk['body'],
            ], $chunk['parameters']);
        }
    }

    public function test_text_chunks_add_part_header_only_when_needed(): void
    {
        $service = new ResumenTodasUnidadesWhatsAppService();

        $single = $service->whatsAppTextChunks('MENSAJE CORTO', 500);
        $multi = $service->whatsAppTextChunks(str_repeat('TEXTO LARGO ', 100), 500);

        $this->assertSame(['MENSAJE CORTO'], $single);
        $this->assertGreaterThan(1, count($multi));
        $this->assertStringStartsWith('Parte 1 de ' . count($multi), $multi[0]);
    }
}
