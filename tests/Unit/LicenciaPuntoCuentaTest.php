<?php

namespace Tests\Unit;

use App\Models\LicenciaPuntoCuenta;
use Carbon\Carbon;
use Tests\TestCase;

class LicenciaPuntoCuentaTest extends TestCase
{
    public function test_fecha_recuperacion_se_calcula_a_18_meses_de_la_ultima_infraccion(): void
    {
        $cuenta = new LicenciaPuntoCuenta([
            'saldo_actual' => 5,
            'fecha_ultima_infraccion' => Carbon::parse('2026-01-15 10:30:00'),
        ]);

        $this->assertSame('2027-07-15', $cuenta->fecha_recuperacion->toDateString());
    }

    public function test_saldo_completo_no_tiene_fecha_de_recuperacion(): void
    {
        $cuenta = new LicenciaPuntoCuenta([
            'saldo_actual' => LicenciaPuntoCuenta::SALDO_MAXIMO,
            'fecha_ultima_infraccion' => Carbon::parse('2026-01-15 10:30:00'),
        ]);

        $this->assertNull($cuenta->fecha_recuperacion);
    }
}
