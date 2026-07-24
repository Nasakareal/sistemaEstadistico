<?php

namespace Tests\Unit;

use App\Services\Alcoholimetria\AlcoholimetriaMensualDocxGenerator;
use App\Services\Alcoholimetria\AlcoholimetriaMensualService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use ZipArchive;

class AlcoholimetriaMensualDocxGeneratorTest extends TestCase
{
    public function test_reemplaza_variables_fragmentadas_y_preserva_las_imagenes(): void
    {
        $service = new AlcoholimetriaMensualService();
        $resumen = $service->construirResumen(
            Carbon::parse('2026-07-01'),
            new Collection(),
            new Collection(),
            500,
            100,
            'Morelia'
        );
        $destino = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'alcoholimetria_mensual_' . uniqid('', true) . '.docx';

        try {
            $generator = new AlcoholimetriaMensualDocxGenerator($service);
            $reporte = $generator->generarConResumen($resumen, $destino);

            $this->assertFileExists($reporte['path']);
            $this->assertSame(
                $this->hashParte(
                    public_path('templates/alcohol_bernardo.docx'),
                    'word/media/image1.png'
                ),
                $this->hashParte($reporte['path'], 'word/media/image1.png')
            );

            $documentXml = $this->leerParte($reporte['path'], 'word/document.xml');

            $this->assertStringContainsString('MORELIA', $documentXml);
            $this->assertStringContainsString('JULIO', $documentXml);
            $this->assertStringContainsString('2026', $documentXml);
            $this->assertStringNotContainsString('$ts2', $documentXml);
            $this->assertDoesNotMatchRegularExpression(
                '/\$[A-Za-z][A-Za-z0-9_]*/',
                $documentXml
            );
        } finally {
            if (is_file($destino)) {
                unlink($destino);
            }
        }
    }

    private function leerParte(string $archivo, string $parte): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archivo) === true);

        try {
            $contenido = $zip->getFromName($parte);
            $this->assertNotFalse($contenido);

            return $contenido;
        } finally {
            $zip->close();
        }
    }

    private function hashParte(string $archivo, string $parte): string
    {
        return hash('sha256', $this->leerParte($archivo, $parte));
    }
}
