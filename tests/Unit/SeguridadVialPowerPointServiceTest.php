<?php

namespace Tests\Unit;

use App\Services\SeguridadVialPowerPointService;
use DOMDocument;
use Tests\TestCase;
use ZipArchive;

class SeguridadVialPowerPointServiceTest extends TestCase
{
    public function test_genera_pptx_con_partes_y_xml_validos(): void
    {
        $path = app(SeguridadVialPowerPointService::class)->generar($this->reporteMuestra());

        try {
            $zip = new ZipArchive();

            $this->assertSame(true, $zip->open($path));
            $this->assertNotFalse($zip->locateName('ppt/presProps.xml'));
            $this->assertNotFalse($zip->locateName('ppt/viewProps.xml'));
            $this->assertNotFalse($zip->locateName('ppt/tableStyles.xml'));
            $this->assertNotFalse($zip->locateName('ppt/media/image1.png'));
            $this->assertNotFalse($zip->locateName('ppt/media/image2.jpg'));

            if (extension_loaded('gd') && is_file(public_path('geo/michoacan.json'))) {
                $this->assertNotFalse($zip->locateName('ppt/media/image3.png'));

                $mapRels = $zip->getFromName('ppt/slides/_rels/slide3.xml.rels');
                $this->assertIsString($mapRels);
                $this->assertStringContainsString('../media/image3.png', $mapRels);
            }

            $slideRels = $zip->getFromName('ppt/slides/_rels/slide1.xml.rels');
            $this->assertIsString($slideRels);
            $this->assertStringContainsString('relationships/image', $slideRels);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                if (!preg_match('/\.(xml|rels)$/', $name)) {
                    continue;
                }

                $xml = $zip->getFromIndex($i);

                $this->assertIsString($xml, $name);
                $this->assertStringNotContainsString('algn="c"', $xml, $name);
                $this->assertStringNotContainsString('anchor="mid"', $xml, $name);
                $this->assertStringNotContainsString('<a:spAutoFit/>', $xml, $name);
                $this->assertXmlIsWellFormed($xml, $name);
            }

            $zip->close();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function assertXmlIsWellFormed(string $xml, string $name): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument();
        $loaded = $document->loadXML($xml);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $message = $name;

        if ($errors) {
            $message .= ': ' . trim($errors[0]->message);
        }

        $this->assertTrue($loaded, $message);
    }

    private function reporteMuestra(): array
    {
        $horas = array_map(fn ($hora) => sprintf('%02d:00', $hora), range(0, 23));

        return [
            'periodo' => ['texto' => 'ENERO - ABRIL 2026'],
            'kpis' => [
                'total_hechos' => 278,
                'total_lesionados' => 91,
                'total_fallecidos' => 12,
                'total_vehiculos' => 302,
                'municipios_con_hechos' => 18,
                'promedio_diario' => 2.3,
                'municipio_principal' => 'MORELIA',
                'municipio_principal_total' => 56,
                'hora_pico' => '18:00',
                'hora_pico_total' => 19,
                'dia_pico' => 'VIERNES',
                'dia_pico_total' => 44,
                'tipo_principal' => 'COLISION POR ALCANCE',
                'tipo_principal_total' => 68,
            ],
            'ranking_municipios' => collect([
                ['municipio' => 'MORELIA', 'hechos' => 56, 'participacion' => 20.1],
                ['municipio' => 'URUAPAN', 'hechos' => 32, 'participacion' => 11.5],
                ['municipio' => 'ZAMORA', 'hechos' => 28, 'participacion' => 10.1],
            ]),
            'mapa_morelia' => [
                'totales' => [
                    'hechos' => 4,
                    'puntos' => 3,
                    'fallecidos' => 1,
                    'lesionados' => 2,
                    'choques' => 1,
                ],
                'puntos' => collect([
                    ['lat' => 19.7008, 'lng' => -101.1844, 'total' => 2, 'fallecidos' => 1, 'lesionados' => 0, 'choques' => 1, 'categoria' => 'fallecidos'],
                    ['lat' => 19.7132, 'lng' => -101.2051, 'total' => 1, 'fallecidos' => 0, 'lesionados' => 1, 'choques' => 0, 'categoria' => 'lesionados'],
                    ['lat' => 19.6844, 'lng' => -101.1688, 'total' => 1, 'fallecidos' => 0, 'lesionados' => 0, 'choques' => 1, 'categoria' => 'choques'],
                ]),
            ],
            'graficas' => [
                'por_dia' => [
                    'labels' => ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'],
                    'series' => [21, 32, 29, 35, 44, 41, 28],
                ],
                'por_hora' => [
                    'labels' => $horas,
                    'series' => array_fill(0, 24, 3),
                ],
                'por_tipo' => [
                    'labels' => ['COLISION POR ALCANCE', 'VOLCADURA', 'SALIDA DE CAMINO'],
                    'series' => [68, 24, 18],
                ],
                'por_situacion' => [
                    'labels' => ['RESUELTO', 'TURNADO', 'PENDIENTE'],
                    'series' => [180, 71, 27],
                ],
            ],
        ];
    }
}
