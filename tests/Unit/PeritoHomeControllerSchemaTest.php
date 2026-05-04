<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PeritoHomeControllerSchemaTest extends TestCase
{
    public function test_detalle_de_hecho_no_pide_columnas_inexistentes_para_vehiculos_y_lesionados(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'app/Http/Controllers/Api/PeritoHomeController.php'
        );

        $this->assertStringNotContainsString("'v.tipo_vehiculo'", $source);
        $this->assertStringNotContainsString("'estado_salud'", $source);
        $this->assertStringContainsString("DB::raw('v.tipo as tipo_vehiculo')", $source);
        $this->assertStringContainsString("DB::raw('tipo_lesion as estado_salud')", $source);
    }
}
