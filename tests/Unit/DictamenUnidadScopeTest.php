<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DictamenUnidadScopeTest extends TestCase
{
    public function test_dictamenes_en_hechos_quedan_limitados_a_unidad_uno(): void
    {
        foreach ([
            'app/Http/Controllers/HechosController.php',
            'app/Http/Controllers/Api/HechoController.php',
        ] as $path) {
            $source = $this->source($path);

            $this->assertStringContainsString('private function userCanUseDictamenes', $source, $path);
            $this->assertStringContainsString('return $unidadId === 1;', $source, $path);
        }
    }

    public function test_delegaciones_no_dispara_selector_de_dictamen_y_conserva_guardia_de_mp(): void
    {
        $createView = $this->source('resources/views/hechos/create.blade.php');
        $editView = $this->source('resources/views/hechos/edit.blade.php');
        $mpScript = $this->source('resources/views/hechos/partials/turnado_mp_scripts.blade.php');

        $this->assertStringContainsString('@if($puedeUsarDictamenes)', $createView);
        $this->assertStringContainsString('@if($puedeUsarDictamenes)', $editView);
        $this->assertStringContainsString('return situacionSelect && situacionSelect.value === \'TURNADO\';', $mpScript);
    }

    private function source(string $path): string
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
