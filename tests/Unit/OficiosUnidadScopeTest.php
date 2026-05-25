<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OficiosUnidadScopeTest extends TestCase
{
    public function test_oficios_quedan_bajo_admin_settings_y_scope_por_unidad(): void
    {
        $routes = $this->source('routes/web.php');
        $controller = $this->source('app/Http/Controllers/OficioController.php');
        $model = $this->source('app/Models/Oficio.php');

        $this->assertStringContainsString("Route::prefix('admin/settings/oficios')", $routes);
        $this->assertStringContainsString('visibleFor($this->actor())', $controller);
        $this->assertStringContainsString('(int) $oficio->unidad_id === $unidadActor', $controller);
        $this->assertStringContainsString('where(\'unidad_id\', $unidadId)', $controller);
        $this->assertStringContainsString('public function scopeVisibleFor', $model);
        $this->assertStringContainsString("hasRole('Superadmin')", $model);
    }

    public function test_oficios_tienen_tipos_sentido_y_relacion_de_contestaciones(): void
    {
        $model = $this->source('app/Models/Oficio.php');
        $show = $this->source('resources/views/admin/settings/oficios/show.blade.php');

        foreach (['amparo', 'memorandum', 'oficio', 'circular', 'administrativo'] as $tipo) {
            $this->assertStringContainsString("'" . $tipo . "'", $model);
        }

        $this->assertStringContainsString("'entrada'", $model);
        $this->assertStringContainsString("'salida'", $model);
        $this->assertStringContainsString('contestaA()', $model);
        $this->assertStringContainsString('contestaciones()', $model);
        $this->assertStringContainsString('Este documento contesta a:', $show);
        $this->assertStringContainsString('id="contestaciones"', $show);
        $this->assertStringContainsString('Aún no hay documentos registrados como contestación.', $show);
    }

    public function test_oficios_tienen_termino_alerta_visual_y_whatsapp(): void
    {
        $model = $this->source('app/Models/Oficio.php');
        $controller = $this->source('app/Http/Controllers/OficioController.php');
        $form = $this->source('resources/views/admin/settings/oficios/_form.blade.php');
        $index = $this->source('resources/views/admin/settings/oficios/index.blade.php');
        $config = $this->source('config/services.php');

        foreach ([12, 24, 48, 72] as $horas) {
            $this->assertStringContainsString($horas . " => '" . $horas . " horas'", $model);
        }

        $this->assertStringContainsString('termino_horas', $controller);
        $this->assertStringContainsString('OficioTerminoWhatsAppService', $controller);
        $this->assertStringContainsString('id="termino_horas"', $form);
        $this->assertStringContainsString("return \$this->sentido === 'entrada'", $model);
        $this->assertStringNotContainsString("(int) (\$this->termino_horas ?? 0) > 0", $model);
        $this->assertStringContainsString('oficio-row--pendiente-contestacion', $index);
        $this->assertStringContainsString('Falta contestar', $index);
        $this->assertStringContainsString('#contestaciones', $index);
        $this->assertStringNotContainsString("route('settings.index')", $index);
        $this->assertStringContainsString('WHATSAPP_OFICIOS_TERMINOS_TO', $config);
    }

    public function test_las_salidas_generan_numero_y_las_entradas_lo_capturan_manual(): void
    {
        $controller = $this->source('app/Http/Controllers/OficioController.php');
        $model = $this->source('app/Models/Oficio.php');
        $form = $this->source('resources/views/admin/settings/oficios/_form.blade.php');

        $this->assertStringContainsString('private function asignarNumeroSalida', $controller);
        $this->assertStringContainsString('private function siguienteNumeroSalida', $controller);
        $this->assertStringContainsString("sprintf('%s/%03d/%d'", $controller);
        $this->assertStringContainsString("if (\$sentido !== 'salida')", $controller);
        $this->assertStringContainsString('public static function prefijoParaUnidad', $model);
        $this->assertStringContainsString("'siniestros' => 'UAS'", $model);
        $this->assertStringContainsString("'delegaciones' => 'UD'", $model);
        $this->assertStringContainsString("'carreteras' => 'UPC'", $model);
        $this->assertStringContainsString("'vialidades-urbanas' => 'UPVU'", $model);
        $this->assertStringContainsString("'fomento-cultura-vial' => 'UFCV'", $model);
        $this->assertStringContainsString('Se asignará automáticamente al guardar', $form);
        $this->assertStringContainsString('Para documentos de entrada, escribe el número', $form);
        $this->assertStringContainsString('El movimiento no puede cambiarse después del registro.', $form);
    }

    public function test_gate_no_convierte_eliminar_oficios_en_permiso_implicitamente_global(): void
    {
        $provider = $this->source('app/Providers/AuthServiceProvider.php');

        $this->assertStringContainsString('isOficiosAbility', $provider);
        $this->assertStringContainsString("'ver oficios'", $provider);
        $this->assertStringContainsString("'crear oficios'", $provider);
        $this->assertStringContainsString("'editar oficios'", $provider);
        $this->assertStringNotContainsString("'eliminar oficios',", $this->oficiosAbilityBlock($provider));
    }

    private function oficiosAbilityBlock(string $source): string
    {
        $start = strpos($source, 'private function isOficiosAbility');

        $this->assertNotFalse($start);

        return substr($source, $start, 400);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
