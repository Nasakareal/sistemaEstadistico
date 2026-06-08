<?php

namespace Tests\Unit;

use App\Support\PuestaDisposicionRules;
use PHPUnit\Framework\TestCase;

class PuestaDisposicionRulesTest extends TestCase
{
    public function test_detecta_hecho_de_transito_con_o_sin_acento(): void
    {
        $this->assertTrue(PuestaDisposicionRules::motivoEsHechoTransito('HECHO DE TRANSITO TURNADO'));
        $this->assertTrue(PuestaDisposicionRules::motivoEsHechoTransito('hecho de tránsito terrestre'));
        $this->assertFalse(PuestaDisposicionRules::motivoEsHechoTransito('DAÑOS'));
    }

    public function test_requiere_hecho_vinculado_solo_para_delegaciones_sin_vinculo(): void
    {
        $this->assertTrue(
            PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
                PuestaDisposicionRules::UNIDAD_DELEGACIONES_ID,
                'HECHO DE TRANSITO',
                false
            )
        );

        $this->assertFalse(
            PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
                PuestaDisposicionRules::UNIDAD_DELEGACIONES_ID,
                'HECHO DE TRANSITO',
                true
            )
        );

        $this->assertFalse(
            PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
                1,
                'HECHO DE TRANSITO',
                false
            )
        );
    }
}
