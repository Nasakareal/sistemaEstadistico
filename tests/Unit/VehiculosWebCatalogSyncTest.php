<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VehiculosWebCatalogSyncTest extends TestCase
{
    public function test_catalogos_web_coinciden_con_los_catalogos_vigentes_de_flutter(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/vehiculos.php';
        $catalogos = $config['catalogos'];

        $this->assertSame(9, count($catalogos['tipos_generales']));
        $this->assertContains('Autobús', $catalogos['carrocerias']['camion']);
        $this->assertGreaterThan(250, count($catalogos['marcas']));
        $this->assertSame(23, count($catalogos['colores']));
        $this->assertSame(23, count($catalogos['aseguradoras']));
        $this->assertSame([
            'PARTICULAR',
            'SERVICIO PÚBLICO ESTATAL',
            'SERVICIO PÚBLICO FEDERAL',
            'OFICIAL',
        ], array_keys($catalogos['tipos_servicio']));
        $this->assertSame(36, count($catalogos['estados_placas']));
    }

    public function test_alta_y_edicion_usan_selects_y_campos_condicionales_de_flutter(): void
    {
        foreach ([
            'resources/views/vehiculos/create.blade.php',
            'resources/views/vehiculos/edit.blade.php',
        ] as $path) {
            $view = $this->source($path);

            foreach (['marca', 'color', 'aseguradora', 'estado_placas', 'tipo_servicio'] as $field) {
                $this->assertStringContainsString('<select name="' . $field . '"', $view, $path);
                $this->assertStringNotContainsString('type="text" name="' . $field . '"', $view, $path);
            }

            $this->assertStringContainsString('name="permiso_circular"', $view, $path);
            $this->assertStringContainsString('SERVICIO PÚBLICO FEDERAL', $view, $path);
            $this->assertStringContainsString('sincronizarCamposPlacas', $view, $path);
        }
    }

    public function test_controlador_permite_vehiculos_sin_placas_y_persiste_permiso_circular(): void
    {
        $controller = $this->source('app/Http/Controllers/VehiculosController.php');

        $this->assertSame(2, substr_count($controller, "'placas'                     => ['nullable'"));
        $this->assertSame(2, substr_count($controller, "'permiso_circular'           => 'nullable|string|max:60'"));
        $this->assertSame(2, substr_count($controller, "'permiso_circular'           => \$validated['permiso_circular']"));
        $this->assertStringContainsString("\$validated['placas'] !== '' && \$hecho->vehiculos()", $controller);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
