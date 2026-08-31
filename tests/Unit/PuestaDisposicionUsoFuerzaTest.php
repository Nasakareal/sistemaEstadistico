<?php

namespace Tests\Unit;

use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use Tests\TestCase;

class PuestaDisposicionUsoFuerzaTest extends TestCase
{
    public function test_modelo_admite_el_pdf_de_uso_de_fuerza(): void
    {
        $puesta = new PuestaDisposicion([
            'archivo_uso_fuerza' => 'puestas_disposicion/uso_fuerza/general.pdf',
        ]);
        $persona = new PuestaDisposicionPersona([
            'archivo_uso_fuerza' => 'puestas_disposicion/uso_fuerza/personas/archivo.pdf',
        ]);

        $this->assertTrue($puesta->isFillable('archivo_uso_fuerza'));
        $this->assertSame('puestas_disposicion/uso_fuerza/general.pdf', $puesta->archivo_uso_fuerza);
        $this->assertTrue($persona->isFillable('archivo_uso_fuerza'));
        $this->assertSame(
            'puestas_disposicion/uso_fuerza/personas/archivo.pdf',
            $persona->archivo_uso_fuerza
        );
    }

    public function test_api_y_backend_web_validan_guardan_y_limpian_el_archivo(): void
    {
        $api = file_get_contents(app_path('Http/Controllers/Api/PuestaDisposicionController.php'));
        $web = file_get_contents(app_path('Http/Controllers/PuestaDisposicionController.php'));

        foreach ([$api, $web] as $source) {
            $this->assertStringContainsString('personas.*.archivo_uso_fuerza', $source);
            $this->assertStringContainsString('puestas_disposicion/uso_fuerza', $source);
            $this->assertStringContainsString("'archivo_uso_fuerza'", $source);
            $this->assertStringContainsString('documentos()->delete', $source);
        }
    }

    public function test_formularios_web_solicitan_y_permiten_reemplazar_el_pdf(): void
    {
        $create = file_get_contents(resource_path('views/puestas_disposicion/create.blade.php'));
        $edit = file_get_contents(resource_path('views/puestas_disposicion/edit.blade.php'));
        $show = file_get_contents(resource_path('views/puestas_disposicion/show.blade.php'));

        $this->assertStringContainsString('name="archivo_puesta"', $create);
        $this->assertStringContainsString('name="archivo_uso_fuerza"', $create);
        $this->assertStringContainsString('PDF de puesta a disposición', $create);
        $this->assertStringContainsString('PDF de uso de la fuerza', $create);
        $this->assertStringContainsString('Reemplazar PDF general de uso de la fuerza', $edit);
        $this->assertStringContainsString('El PDF actual se conservará', $edit);
        $this->assertStringContainsString('Ver PDF de uso de fuerza', $show);
    }

    public function test_rutas_protegidas_exponen_el_archivo_en_web_y_api(): void
    {
        $apiRoutes = file_get_contents(base_path('routes/api.php'));
        $webRoutes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString('api.puestas_disposicion.personas.uso_fuerza', $apiRoutes);
        $this->assertStringContainsString('api.puestas_disposicion.uso_fuerza', $apiRoutes);
        $this->assertStringContainsString('puestas_disposicion.personas.uso_fuerza', $webRoutes);
        $this->assertStringContainsString('puestas_disposicion.uso_fuerza', $webRoutes);
    }
}
