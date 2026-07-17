<?php

namespace Tests\Unit;

use App\Models\Vehiculo;
use Tests\TestCase;

class VehiculoReporteRoboTest extends TestCase
{
    public function test_reporte_de_robo_pertenece_a_cada_vehiculo(): void
    {
        $vehiculo = new Vehiculo(['reporte_robo' => '1']);

        $this->assertTrue($vehiculo->isFillable('reporte_robo'));
        $this->assertTrue($vehiculo->reporte_robo);
        $this->assertSame('boolean', $vehiculo->getCasts()['reporte_robo']);
    }

    public function test_api_y_backend_lo_exigen_para_usuarios_de_delegaciones(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/VehiculosController.php'));
        $api = file_get_contents(app_path('Http/Controllers/Api/VehiculoController.php'));

        foreach ([$web, $api] as $source) {
            $this->assertStringContainsString("'reporte_robo'", $source);
            $this->assertStringContainsString("'required|boolean'", $source);
            $this->assertStringContainsString("boolean('reporte_robo')", $source);
        }
    }

    public function test_show_calcula_si_algun_vehiculo_tiene_reporte(): void
    {
        $show = file_get_contents(resource_path('views/hechos/show.blade.php'));

        $this->assertStringContainsString('hayVehiculoConReporteRobo', $show);
        $this->assertStringContainsString('Vehículo con reporte de robo:', $show);
        $this->assertStringContainsString('REPORTE DE ROBO', $show);
    }

    public function test_formulario_inicia_sin_respuesta_preseleccionada(): void
    {
        $create = file_get_contents(resource_path('views/vehiculos/create.blade.php'));

        $this->assertStringContainsString('Seleccione una opción', $create);
        $this->assertStringContainsString('name="reporte_robo"', $create);
        $this->assertStringContainsString('value=""', $create);
    }
}
