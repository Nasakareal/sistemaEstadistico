<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HechoEliminadoArchiveTest extends TestCase
{
    public function test_migracion_crea_archivo_de_hechos_y_vehiculos(): void
    {
        $migration = $this->source('database/migrations/2026_11_23_120000_create_hechos_eliminados_tables.php');

        $this->assertStringContainsString("Schema::create('hechos_eliminados'", $migration);
        $this->assertStringContainsString("Schema::create('hechos_eliminados_vehiculos'", $migration);
        $this->assertStringContainsString("\$table->unsignedBigInteger('unidad_org_id')", $migration);
        $this->assertStringContainsString("\$table->json('datos_hecho')", $migration);
        $this->assertStringContainsString("\$table->json('datos_vehiculo')", $migration);
        $this->assertStringContainsString("\$table->json('conductores')", $migration);
        $this->assertStringContainsString("\$table->json('servicios')", $migration);
    }

    public function test_archivo_ocurre_antes_del_borrado_y_no_elimina_archivos_fisicos(): void
    {
        $service = $this->source('app/Services/HechoEliminadoService.php');

        $archivePosition = strpos($service, 'HechoEliminado::create');
        $deletePosition = strpos($service, '$hecho->delete()');

        $this->assertNotFalse($archivePosition);
        $this->assertNotFalse($deletePosition);
        $this->assertLessThan($deletePosition, $archivePosition);
        $this->assertStringContainsString('DB::transaction', $service);
        $this->assertStringNotContainsString('Storage::delete', $service);
        $this->assertStringNotContainsString('HechoFotoStorage', $service);
        $this->assertStringNotContainsString('CroquisArchivoStorage', $service);
    }

    public function test_web_y_api_comparten_el_mismo_servicio_de_archivo(): void
    {
        foreach ([
            'app/Http/Controllers/HechosController.php',
            'app/Http/Controllers/Api/HechoController.php',
        ] as $controllerPath) {
            $controller = $this->source($controllerPath);

            $this->assertStringContainsString('HechoEliminadoService $service', $controller, $controllerPath);
            $this->assertStringContainsString('$service->archivar(', $controller, $controllerPath);
        }
    }

    public function test_existe_pantalla_y_accion_para_restaurar(): void
    {
        $routes = $this->source('routes/web.php');
        $view = $this->source('resources/views/hechos/eliminados.blade.php');

        $this->assertStringContainsString("name('hechos.eliminados.index')", $routes);
        $this->assertStringContainsString("name('hechos.eliminados.restore')", $routes);
        $this->assertStringContainsString("route('hechos.eliminados.restore'", $view);
        $this->assertStringContainsString('Restaurar', $view);

        $controller = $this->source('app/Http/Controllers/HechosEliminadosController.php');
        $this->assertStringContainsString("hasRole('Superadmin')", $controller);
        $this->assertStringNotContainsString("hasRole('Administrador')", $controller);

        $index = $this->source('resources/views/hechos/index.blade.php');
        $this->assertStringContainsString("\$puedeVerEliminados = \$usuario->hasRole('Superadmin')", $index);
    }

    public function test_api_conserva_el_contrato_que_consume_flutter(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/HechoController.php');

        $this->assertStringContainsString("'message' => 'Hecho eliminado exitosamente'", $controller);
        $this->assertStringContainsString('], 200);', $controller);
    }

    public function test_administrador_recibe_el_flujo_visual_normal_de_eliminacion(): void
    {
        $controller = $this->source('app/Http/Controllers/HechosController.php');
        $index = $this->source('resources/views/hechos/index.blade.php');
        $show = $this->source('resources/views/hechos/show.blade.php');
        $seguimiento = $this->source('resources/views/hechos/seguimiento.blade.php');

        $this->assertStringContainsString('Hecho eliminado exitosamente.', $controller);

        foreach ([$index, $show, $seguimiento] as $view) {
            $this->assertStringNotContainsString('podrá restaurarse', $view);
            $this->assertStringNotContainsString('Mover a hechos eliminados', $view);
        }
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
