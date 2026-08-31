<?php

namespace Tests\Unit;

use App\AdminLte\Filters\UnidadMenuFilter;
use PHPUnit\Framework\TestCase;

class UnidadMenuFilterTest extends TestCase
{
    public function test_oculta_un_elemento_marcado_para_unidad_cinco(): void
    {
        $item = ['text' => 'Grúas', 'hide_for_units' => [5]];

        $this->assertTrue(UnidadMenuFilter::debeOcultarse($item, (object) ['unidad_id' => 5]));
        $this->assertFalse(UnidadMenuFilter::debeOcultarse($item, (object) ['unidad_id' => 3]));
    }

    public function test_no_afecta_elementos_sin_restriccion_de_menu(): void
    {
        $this->assertFalse(UnidadMenuFilter::debeOcultarse(
            ['text' => 'Actividades'],
            (object) ['unidad_id' => 5]
        ));
    }
}
