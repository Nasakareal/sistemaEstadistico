<?php

namespace Tests\Unit;

use App\Http\Controllers\BoquillaDotacionController;
use App\Models\BoquillaDotacion;
use App\Models\BoquillaPerdida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BoquillaDotacionTest extends TestCase
{
    public function test_usa_la_tabla_en_espanol_y_convierte_fecha_y_cantidad(): void
    {
        $dotacion = new BoquillaDotacion([
            'fecha_recepcion' => '2026-07-03',
            'cantidad' => '125',
        ]);

        $this->assertSame('boquilla_dotaciones', $dotacion->getTable());
        $this->assertSame('2026-07-03', $dotacion->fecha_recepcion->toDateString());
        $this->assertSame(125, $dotacion->cantidad);
    }

    public function test_permite_capturar_los_datos_de_cada_entrega(): void
    {
        $dotacion = new BoquillaDotacion([
            'fecha_recepcion' => '2026-07-17',
            'cantidad' => 275,
            'observaciones' => 'Segunda entrega semanal',
        ]);

        $this->assertSame('Segunda entrega semanal', $dotacion->observaciones);
        $this->assertSame(275, $dotacion->cantidad);
    }

    public function test_configuraciones_enlaza_boquillas_sin_depender_de_la_cache_de_rutas(): void
    {
        $this->assertTrue(Route::has('settings.boquillas.index'));

        $vista = file_get_contents(resource_path('views/admin/settings/index.blade.php'));

        $this->assertStringContainsString("url('/admin/settings/boquillas')", $vista);
        $this->assertStringNotContainsString("route('settings.boquillas.index')", $vista);
    }

    public function test_perdidas_usa_la_ruta_estable_de_boquillas_si_la_cache_no_tiene_las_rutas_nuevas(): void
    {
        $vista = file_get_contents(resource_path('views/admin/settings/boquillas/index.blade.php'));

        $this->assertStringNotContainsString("route('settings.boquillas.perdidas.", $vista);
        $this->assertSame(3, substr_count($vista, "action=\"{{ url('/admin/settings/boquillas') }}\""));
        $this->assertStringContainsString('name="operacion_perdida" value="crear"', $vista);
        $this->assertStringContainsString('name="operacion_perdida" value="actualizar"', $vista);
        $this->assertStringContainsString('name="operacion_perdida" value="eliminar"', $vista);
    }

    public function test_la_ruta_estable_despacha_las_tres_operaciones_de_perdidas(): void
    {
        $controller = new class extends BoquillaDotacionController
        {
            public array $acciones = [];

            public function storePerdida(Request $request)
            {
                $this->acciones[] = ['crear', null];

                return $request;
            }

            public function updatePerdida(Request $request, BoquillaPerdida $perdida)
            {
                $this->acciones[] = ['actualizar', $perdida->id];

                return $request;
            }

            public function destroyPerdida(Request $request, BoquillaPerdida $perdida)
            {
                $this->acciones[] = ['eliminar', $perdida->id];

                return $request;
            }

            protected function buscarPerdida($id): BoquillaPerdida
            {
                return (new BoquillaPerdida())->forceFill(['id' => (int) $id]);
            }
        };

        $controller->store(Request::create('/', 'POST', ['operacion_perdida' => 'crear']));
        $controller->store(Request::create('/', 'POST', [
            'operacion_perdida' => 'actualizar',
            'perdida_id' => 17,
        ]));
        $controller->store(Request::create('/', 'POST', [
            'operacion_perdida' => 'eliminar',
            'perdida_id' => 23,
        ]));

        $this->assertSame([
            ['crear', null],
            ['actualizar', 17],
            ['eliminar', 23],
        ], $controller->acciones);
    }
}
