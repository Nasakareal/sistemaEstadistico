<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HechosWebCatalogSyncTest extends TestCase
{
    public function test_catalogos_web_contienen_las_opciones_vigentes_de_flutter(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/hechos.php';
        $catalogos = $config['catalogos'];

        $this->assertArrayHasKey('INCIDENTE DE TRANSITO', $catalogos['tipos_hecho']);
        $this->assertSame(
            ['ASFALTO', 'CONCRETO', 'ADOQUIN', 'TERRACERIA', 'EMPEDRADO', 'GRAVA'],
            array_keys($catalogos['superficies_via'])
        );
        $this->assertSame(7, count($catalogos['controles_transito']));
        $this->assertSame(14, count($catalogos['causas']));
        $this->assertSame(6, count($catalogos['responsables']));
        $this->assertSame(5, count($catalogos['colisiones_camino']));
    }

    public function test_alta_y_edicion_usan_selects_de_catalogo_en_lugar_de_texto_libre(): void
    {
        foreach ([
            'resources/views/hechos/create.blade.php',
            'resources/views/hechos/edit.blade.php',
        ] as $path) {
            $view = $this->source($path);

            foreach (['tipo_hecho', 'superficie_via', 'control_transito', 'causas', 'responsable', 'colision_camino'] as $field) {
                $this->assertStringContainsString("'nombre' => '{$field}'", $view, $path);
                $this->assertStringNotContainsString('type="text" name="' . $field . '"', $view, $path);
            }

            $this->assertStringContainsString('@unless($usaReglasFlexibles ?? false)', $view, $path);
        }
    }

    public function test_controlador_envia_las_banderas_de_unidad_a_ambos_formularios(): void
    {
        $controller = $this->source('app/Http/Controllers/HechosController.php');

        $this->assertSame(2, substr_count($controller, "'usaReglasFlexibles'"));
        $this->assertSame(2, substr_count($controller, "'ocultarCamposAdministrativosDelegaciones'"));
        $this->assertStringContainsString('private function hideDelegacionesHechoAdminFields', $controller);
        $this->assertStringContainsString('$unidadId === 2', $controller);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
