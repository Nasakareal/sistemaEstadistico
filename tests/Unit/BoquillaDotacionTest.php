<?php

namespace Tests\Unit;

use App\Models\BoquillaDotacion;
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
}
