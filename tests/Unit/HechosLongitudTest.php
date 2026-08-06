<?php

namespace Tests\Unit;

use App\Models\Hechos;
use PHPUnit\Framework\TestCase;

class HechosLongitudTest extends TestCase
{
    public function test_convierte_una_longitud_positiva_a_negativa(): void
    {
        $hecho = new Hechos();
        $hecho->lng = '101.1539351';

        $this->assertSame(-101.1539351, $hecho->getAttributes()['lng']);
    }

    public function test_conserva_una_longitud_que_ya_es_negativa(): void
    {
        $hecho = new Hechos();
        $hecho->lng = '-101.1539351';

        $this->assertSame(-101.1539351, $hecho->getAttributes()['lng']);
    }

    public function test_conserva_longitud_vacia_como_null(): void
    {
        $hecho = new Hechos();
        $hecho->lng = null;

        $this->assertNull($hecho->getAttributes()['lng']);
    }
}
