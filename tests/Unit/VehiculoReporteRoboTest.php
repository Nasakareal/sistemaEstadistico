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

    public function test_web_lo_exige_y_api_movil_conserva_compatibilidad(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/VehiculosController.php'));
        $api = file_get_contents(app_path('Http/Controllers/Api/VehiculoController.php'));

        $this->assertStringContainsString("'reporte_robo'", $web);
        $this->assertStringContainsString("'required|boolean'", $web);
        $this->assertStringContainsString("boolean('reporte_robo')", $web);

        $this->assertStringContainsString("'reporte_robo' => 'sometimes|boolean'", $api);
        $this->assertStringContainsString("boolean('reporte_robo')", $api);
        $this->assertStringContainsString("\$request->exists('reporte_robo')", $api);
    }

    public function test_api_no_convierte_errores_de_validacion_en_error_500(): void
    {
        $api = file_get_contents(app_path('Http/Controllers/Api/VehiculoController.php'));

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($api, 'catch (ValidationException $e)'),
        );
        $this->assertStringContainsString(
            'return $e->response ?? $this->validationFailed($e->errors());',
            $api,
        );
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
