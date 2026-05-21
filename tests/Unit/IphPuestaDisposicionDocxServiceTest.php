<?php

namespace Tests\Unit;

use App\Models\Hechos;
use App\Services\IphPuestaDisposicionDocxService;
use DOMDocument;
use Tests\TestCase;
use ZipArchive;

class IphPuestaDisposicionDocxServiceTest extends TestCase
{
    public function test_descripcion_de_vehiculos_incluye_tarjeta_conductor_y_licencia_en_docx(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive is required to inspect generated DOCX files.');
        }

        $hecho = new Hechos([
            'id' => 777,
            'folio_c5i' => 'TEST-IPH',
        ]);
        $service = app(IphPuestaDisposicionDocxService::class);

        [$path] = $service->generar($hecho, [
            'hecho' => [
                'folio_c5i' => 'TEST-IPH',
                'fecha' => '2026-03-11',
                'hora' => '12:30',
                'tipo_hecho' => 'CHOQUE',
                'causas' => 'FALTA DE PRECAUCION Y CUIDADO',
                'lesionados_count' => 0,
                'fallecidos_count' => 0,
                'unidad_org_id' => 2,
                'unidad_org_nombre' => 'Unidad de Delegaciones',
                'creador_nombre' => 'AGENTE TEST',
                'ubicacion' => [
                    'calle' => 'AVENIDA TEST',
                    'colonia' => 'CENTRO',
                    'municipio' => 'MORELIA',
                ],
            ],
            'puesta_disposicion' => [
                'fecha_puesta' => '2026-03-11',
                'hora_puesta' => '13:05',
                'nombre_policia' => 'AGENTE TEST',
                'autoridad_receptora' => 'MINISTERIO PUBLICO',
            ],
            'vehiculos_hecho' => [
                [
                    'tipo' => 'Sedán',
                    'marca' => 'Chevrolet',
                    'linea' => 'Aveo',
                    'modelo' => '2011',
                    'color' => 'Rojo',
                    'capacidad_personas' => '5',
                    'placas' => 'PKP853B',
                    'estado_placas' => 'esta entidad federativa',
                    'serie' => '3G1TC5CF5BL147401',
                    'tipo_servicio' => 'PARTICULAR',
                    'tarjeta_circulacion_nombre' => 'BRYAN BULMARO SOLORIO PAQUE',
                    'partes_danadas' => 'ÁNGULO FRONTAL DERECHO',
                    'monto_danos' => 3000,
                    'grua_nombre' => 'Serví-Grúas Profesionales',
                    'grua_direccion' => 'Carretera Morelia - Salamanca Km. 7.5, colonia Erandeni',
                    'conductores' => [
                        [
                            'nombre' => 'NOE PEREZ CRUZ',
                            'edad' => '55',
                            'sexo' => 'M',
                            'domicilio' => 'Lomas de las Villas # 187-B',
                            'estado_licencia' => 'MICHOACAN',
                            'tipo_licencia' => null,
                            'numero_licencia' => null,
                        ],
                    ],
                ],
                [
                    'tipo' => 'Hatchback',
                    'marca' => 'Nissan',
                    'linea' => 'March',
                    'modelo' => '2019',
                    'color' => 'Rojo',
                    'capacidad_personas' => '5',
                    'placas' => 'PMB497C',
                    'estado_placas' => 'esta entidad federativa',
                    'serie' => '3N1CK3CD0KL212607',
                    'tipo_servicio' => 'PARTICULAR',
                    'tarjeta_circulacion_nombre' => 'MARIA DEL ROCIO TAPIA ZENTENO',
                    'partes_danadas' => 'COSTADO POSTERIOR IZQUIERDO',
                    'monto_danos' => 8000,
                    'grua_nombre' => 'Serví-Grúas Profesionales',
                    'grua_direccion' => 'Carretera Morelia - Salamanca Km. 7.5, colonia Erandeni',
                    'conductores' => [],
                ],
            ],
            'lesionados_hecho' => [],
            'objetos' => [],
            'anexos' => [],
        ]);

        try {
            $texto = $this->textoDocx($path);

            $this->assertStringContainsString('Capacidad para 5 Personas', $texto);
            $this->assertStringContainsString('Placas para circular PKP853B del servicio particular de esta entidad federativa', $texto);
            $this->assertStringContainsString('Serie 3G1TC5CF5BL147401', $texto);
            $this->assertStringContainsString('tarjeta de circulación a nombre de BRYAN BULMARO SOLORIO PAQUE', $texto);
            $this->assertStringContainsString('el C. NOE PEREZ CRUZ de 55 años de edad', $texto);
            $this->assertStringContainsString('con domicilio en Lomas de las Villas # 187-B, en esta ciudad', $texto);
            $this->assertStringContainsString('me manifestó ir a bordo del vehículo, presentó licencia', $texto);
            $this->assertStringContainsString('De este hecho de tránsito no se manifestaron ante el suscrito.', $texto);
            $this->assertStringContainsString('VEHÍCULO (A).- Presenta daños en su Ángulo Frontal Derecho, se estiman en la cantidad aproximada para su reparación de $ 3,000.00 (TRES MIL PESOS 00/100 M.N.).', $texto);
            $this->assertStringContainsString('VEHÍCULO (B).- Presenta daños en su Costado Posterior Izquierdo, se estiman en la cantidad aproximada para su reparación de $ 8,000.00 (OCHO MIL PESOS 00/100 M.N.).', $texto);
            $this->assertStringContainsString('Estos daños fueron estimados y calculados a simple vista', $texto);
            $this->assertStringContainsString('Ambos vehículos fueron resguardados por su propia tracción en las instalaciones de Serví-Grúas Profesionales', $texto);
            $this->assertStringContainsString('ÚNICA.- La causa que da origen al hecho de tránsito que nos ocupa se refiere a falta de precaucion y cuidado por parte del conductor del vehículo (A), en consecuencia ocasionar daños materiales', $texto);
            $this->assertStringContainsString('Con base en lo dispuesto en el artículo 59 de la Ley de Tránsito y Vialidad vigente en el Estado, Pongo a su disposición ambos vehículos', $texto);
            $this->assertStringContainsString('ATENTAMENTE.PERITO DE TRÁNSITO.AGENTE TEST', $texto);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function textoDocx(string $path): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertIsString($xml);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $texto = '';

        foreach ($dom->getElementsByTagName('t') as $node) {
            $texto .= $node->textContent;
        }

        return $texto;
    }
}
