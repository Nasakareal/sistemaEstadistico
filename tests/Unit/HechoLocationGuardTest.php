<?php

namespace Tests\Unit;

use App\Support\HechoLocationGuard;
use PHPUnit\Framework\TestCase;

class HechoLocationGuardTest extends TestCase
{
    public function test_bloquea_la_captura_en_la_oficina(): void
    {
        $this->assertTrue(
            HechoLocationGuard::isBlockedOfficeLocation(19.6808588, -101.2339535)
        );
    }

    public function test_permite_capturas_fuera_del_radio_de_oficina(): void
    {
        $this->assertFalse(
            HechoLocationGuard::isBlockedOfficeLocation(19.6815, -101.2339535)
        );
    }
}
