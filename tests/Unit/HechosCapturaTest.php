<?php

namespace Tests\Unit;

use App\Models\Hechos;
use PHPUnit\Framework\TestCase;

class HechosCapturaTest extends TestCase
{
    public function test_captura_es_completa_si_lo_capturado_cubre_o_supera_lo_esperado()
    {
        $hecho = new Hechos([
            'vehiculos_esperados' => 1,
            'conductores_esperados' => 1,
            'lesionados_esperados' => 0,
            'vehiculos_capturados' => 1,
            'conductores_capturados' => 1,
            'lesionados_capturados' => 1,
        ]);

        $this->assertTrue($hecho->capturaCompletaCalculada());
        $this->assertSame([], $hecho->faltantesCapturaTexto());
    }

    public function test_faltantes_de_captura_describen_lo_pendiente()
    {
        $hecho = new Hechos([
            'vehiculos_esperados' => 2,
            'conductores_esperados' => 2,
            'lesionados_esperados' => 1,
            'vehiculos_capturados' => 1,
            'conductores_capturados' => 0,
            'lesionados_capturados' => 1,
        ]);

        $this->assertFalse($hecho->capturaCompletaCalculada());
        $this->assertSame([
            '1 vehículo (1/2)',
            '2 conductores (0/2)',
        ], array_values($hecho->faltantesCapturaTexto()));
    }
}
