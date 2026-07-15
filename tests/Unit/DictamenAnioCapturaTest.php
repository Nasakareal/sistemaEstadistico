<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DictamenAnioCapturaTest extends TestCase
{
    public function test_formulario_permite_capturar_numero_y_anio_desde_2017(): void
    {
        $view = $this->source('resources/views/dictamenes/create.blade.php');

        $this->assertStringContainsString('name="numero_dictamen"', $view);
        $this->assertStringContainsString('name="anio"', $view);
        $this->assertStringContainsString('min="{{ $anioMinimo }}"', $view);
        $this->assertStringNotContainsString('value="{{ now()->year }}" readonly', $view);
    }

    public function test_controladores_guardan_el_anio_y_numero_solicitados(): void
    {
        $web = $this->source('app/Http/Controllers/DictamenController.php');
        $api = $this->source('app/Http/Controllers/Api/DictamenController.php');

        foreach ([$web, $api] as $source) {
            $this->assertStringContainsString('private const ANIO_MINIMO = 2017;', $source);
            $this->assertStringContainsString("'numero_dictamen'", $source);
            $this->assertStringContainsString("'anio'", $source);
            $this->assertStringContainsString("Rule::unique('dictamens', 'numero_dictamen')", $source);
            $this->assertStringContainsString("->where('anio'", $source);
            $this->assertStringContainsString("->where('area'", $source);
        }
    }

    public function test_edicion_de_hecho_filtra_y_valida_dictamenes_del_mismo_anio(): void
    {
        $web = $this->source('app/Http/Controllers/HechosController.php');
        $api = $this->source('app/Http/Controllers/Api/HechoController.php');
        $view = $this->source('resources/views/hechos/edit.blade.php');

        $this->assertStringContainsString("->where('anio', \$anioHecho)", $web);
        $this->assertStringContainsString("Rule::exists('dictamens', 'id')", $web);
        $this->assertStringContainsString("Rule::exists('dictamens', 'id')", $api);
        $this->assertStringContainsString('Solo aparecen dictámenes {{ $anioHecho }}', $view);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
