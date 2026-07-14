<?php

namespace Tests\Unit;

use App\Models\Hechos;
use Tests\TestCase;

class HechoFotoLugar2BackendTest extends TestCase
{
    public function test_modelo_admite_la_segunda_foto_del_hecho(): void
    {
        $hecho = new Hechos(['foto_lugar_2' => 'hechos/2026/hecho_1/lugar_2.jpg']);

        $this->assertTrue($hecho->isFillable('foto_lugar_2'));
        $this->assertSame('hechos/2026/hecho_1/lugar_2.jpg', $hecho->foto_lugar_2);
    }

    public function test_controlador_web_gestiona_alta_reemplazo_eliminacion_y_limpieza(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/HechosController.php'));

        $this->assertStringContainsString("'foto_lugar_2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'", $source);
        $this->assertStringContainsString("hasFile('foto_lugar_2')", $source);
        $this->assertStringContainsString("putUploadedFile(\$request->file('foto_lugar_2'), \$hecho, 'lugar_2')", $source);
        $this->assertStringContainsString("input('quitar_foto_lugar_2', '0')", $source);
        $this->assertStringContainsString("delete(\$hecho->foto_lugar_2)", $source);
    }

    public function test_formularios_y_detalle_muestran_ambas_fotos_del_hecho(): void
    {
        $create = file_get_contents(resource_path('views/hechos/create.blade.php'));
        $edit = file_get_contents(resource_path('views/hechos/edit.blade.php'));
        $show = file_get_contents(resource_path('views/hechos/show.blade.php'));

        $this->assertStringContainsString('name="foto_lugar_2"', $create);
        $this->assertStringContainsString('name="foto_lugar_2"', $edit);
        $this->assertStringContainsString('name="quitar_foto_lugar_2"', $edit);
        $this->assertStringContainsString('$hecho->foto_lugar_2', $show);
        $this->assertStringContainsString('Foto del hecho 2', $show);
    }

    public function test_salidas_secundarias_tambien_contemplan_la_segunda_foto(): void
    {
        foreach ([
            app_path('Services/WhatsApp/WhatsAppRenderService.php'),
            app_path('Services/IphPuestaDisposicionService.php'),
            app_path('Services/IphPuestaDisposicionDocxService.php'),
            app_path('Console/Commands/MigrarFotosHechosBlob.php'),
            app_path('Console/Commands/LimpiarFotosHechosLocales.php'),
        ] as $path) {
            $this->assertStringContainsString('foto_lugar_2', file_get_contents($path), $path);
        }
    }
}
