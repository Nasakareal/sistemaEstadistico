<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasFomentoSettingsController;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class EstadisticasFomentoPersonalTest extends TestCase
{
    public function test_rutas_de_estadisticas_de_personal_estan_registradas(): void
    {
        $this->assertSame(
            'admin/settings/estadisticas-fomento/personal',
            app('router')->getRoutes()->getByName('settings.estadisticas_fomento.personal')->uri()
        );
        $this->assertNotNull(app('router')->getRoutes()->getByName('settings.estadisticas_fomento.personal.rendimiento'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('settings.estadisticas_fomento.personal.incapacidades'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('settings.estadisticas_fomento.personal.disciplina'));
    }

    public function test_calculo_de_dias_de_servicio_respeta_periodo_y_fin_de_semana(): void
    {
        $controller = new EstadisticasFomentoSettingsController();
        $method = new ReflectionMethod($controller, 'diasIncidenciaServicio');
        $method->setAccessible(true);

        [$dias, $finSemana] = $method->invoke(
            $controller,
            (object) ['fecha_inicio' => '2026-11-20', 'fecha_fin' => '2026-11-22'],
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        $this->assertSame(3, $dias);
        $this->assertSame(2, $finSemana);
    }

    public function test_normaliza_tipos_disciplinarios_con_acentos(): void
    {
        $method = new ReflectionMethod(EstadisticasFomentoSettingsController::class, 'normalizarTexto');
        $method->setAccessible(true);

        $this->assertSame(
            'AMONESTACION',
            $method->invoke(new EstadisticasFomentoSettingsController(), 'Amonestación')
        );
    }

    public function test_vistas_incluyen_estado_vacio_y_detalle_de_servicios(): void
    {
        $personal = file_get_contents(resource_path('views/admin/settings/estadisticas_fomento/personal/index.blade.php'));
        $servicios = file_get_contents(resource_path('views/admin/settings/estadisticas_fomento/servicios_personal/index.blade.php'));

        $this->assertStringContainsString('No hay personal adscrito a la Unidad de Fomento', $personal);
        $this->assertStringContainsString('Equidad en servicios', $personal);
        $this->assertStringContainsString('Listado de servicios registrados', $servicios);
        $this->assertStringContainsString('fomentoServiciosDetalle', $servicios);
    }
}
