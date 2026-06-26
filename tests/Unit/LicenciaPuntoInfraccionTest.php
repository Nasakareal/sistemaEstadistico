<?php

namespace Tests\Unit;

use App\Models\LicenciaPuntoInfraccion;
use Tests\TestCase;

class LicenciaPuntoInfraccionTest extends TestCase
{
    public function test_resume_sancion_con_puntos_y_retiro_de_vehiculo(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'articulo' => '419',
            'fraccion' => 'II',
            'inciso' => 'b',
            'puntos' => 3,
            'retencion_vehiculo' => true,
            'multa_uma_min' => 30,
            'multa_uma_max' => 40,
            'nombre' => 'Conducta capturable',
            'codigo' => 'ART419_II_B',
        ]);

        $this->assertSame('Art. 419, fracc. II, inciso b)', $infraccion->referencia_legal_corta);
        $this->assertSame('-3 puntos + retiro de vehiculo', $infraccion->resumen_sanciones);
        $this->assertSame('30 a 40 UMAS', $infraccion->multa_uma_texto);
        $this->assertSame(
            'Art. 419, fracc. II, inciso b) - Conducta capturable (-3 puntos + retiro de vehiculo)',
            $infraccion->etiqueta_operativa
        );
    }

    public function test_permite_sancion_solo_con_retiro_de_vehiculo(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'puntos' => 0,
            'retencion_vehiculo' => true,
        ]);

        $this->assertSame('retiro de vehiculo', $infraccion->resumen_sanciones);
    }
}
