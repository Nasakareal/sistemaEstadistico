<?php

namespace Tests\Unit;

use App\Services\VialidadesUrbanas\ExcelVialidadesUrbanasGenerator;
use Tests\TestCase;

class ExcelVialidadesUrbanasGeneratorTest extends TestCase
{
    public function test_rango_corte_diario_usa_17_horas_para_vialidades_urbanas(): void
    {
        [$inicio, $fin] = app(ExcelVialidadesUrbanasGenerator::class)->rangoCorte('2026-06-04');

        $this->assertSame('2026-06-03 17:00:00', $inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-04 17:00:00', $fin->format('Y-m-d H:i:s'));
    }
}
