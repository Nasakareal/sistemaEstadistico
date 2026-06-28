<?php

namespace Tests\Unit;

use App\Models\LicenciaPuntoInfraccion;
use Tests\TestCase;

class LicenciaPuntoInfraccionTest extends TestCase
{
    public function test_resume_sancion_con_arresto_y_deposito_condicional(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'articulo' => '419',
            'fraccion' => 'II',
            'inciso' => 'b',
            'ambito_vehiculo' => 'motocicleta',
            'puntos' => 3,
            'amonestacion' => true,
            'arresto_persona' => true,
            'deposito_si_sin_persona_habilitada' => true,
            'retencion_vehiculo' => false,
            'nombre' => 'Conducta capturable',
            'codigo' => 'ART419_II_B',
        ]);

        $this->assertSame('Art. 419, fracc. II, inciso b)', $infraccion->referencia_legal_corta);
        $this->assertSame('Motocicleta', $infraccion->ambito_vehiculo_texto);
        $this->assertSame('amonestacion + arresto de persona + -3 puntos + deposito si no hay persona habilitada', $infraccion->resumen_sanciones);
        $this->assertSame('amonestacion + arresto de persona', $infraccion->sancion_persona_texto);
        $this->assertNull($infraccion->multa_uma_texto);
        $this->assertSame(
            'Art. 419, fracc. II, inciso b) - Conducta capturable (amonestacion + arresto de persona + -3 puntos + deposito si no hay persona habilitada)',
            $infraccion->etiqueta_operativa
        );
    }

    public function test_permite_sancion_solo_con_retiro_de_vehiculo(): void
    {
        $infraccion = new LicenciaPuntoInfraccion([
            'puntos' => 0,
            'retencion_vehiculo' => true,
        ]);

        $this->assertSame('deposito de vehiculo', $infraccion->resumen_sanciones);
    }
}
