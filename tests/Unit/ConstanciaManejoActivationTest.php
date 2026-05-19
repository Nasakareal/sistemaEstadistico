<?php

namespace Tests\Unit;

use App\Models\ConstanciaExamen;
use App\Models\ConstanciaManejo;
use PHPUnit\Framework\TestCase;

class ConstanciaManejoActivationTest extends TestCase
{
    public function test_folio_impreso_puede_activarse_sin_examen_asociado(): void
    {
        $constancia = $this->constancia([
            'nombre_solicitante' => 'ANA LOPEZ',
            'sexo' => 'MUJER',
            'tipo_licencia' => 'AUTOMOVILISTA',
        ]);

        $this->assertTrue($constancia->tieneDatosMinimosActivacion());
        $this->assertTrue($constancia->puedeActivarDirectamente());
        $this->assertTrue($constancia->puedeActivar());
    }

    public function test_activacion_directa_requiere_nombre_sexo_y_tipo_de_licencia(): void
    {
        $constancia = $this->constancia([
            'nombre_solicitante' => 'ANA LOPEZ',
            'sexo' => null,
            'tipo_licencia' => 'AUTOMOVILISTA',
        ]);

        $this->assertFalse($constancia->tieneDatosMinimosActivacion());
        $this->assertFalse($constancia->puedeActivar());
    }

    public function test_constancia_con_examen_no_activa_si_el_examen_no_esta_aprobado(): void
    {
        $constancia = $this->constancia(
            [
                'nombre_solicitante' => 'ANA LOPEZ',
                'sexo' => 'MUJER',
                'tipo_licencia' => 'AUTOMOVILISTA',
            ],
            new ConstanciaExamen(['resultado' => 'REPROBADO'])
        );

        $this->assertFalse($constancia->puedeActivarDirectamente());
        $this->assertFalse($constancia->tieneExamenAprobado());
        $this->assertFalse($constancia->puedeActivar());
    }

    private function constancia(array $attributes, ?ConstanciaExamen $examen = null): ConstanciaManejo
    {
        $constancia = new ConstanciaManejo(array_merge([
            'estatus' => 'IMPRESA_INACTIVA',
            'nombre_solicitante' => null,
            'sexo' => null,
            'tipo_licencia' => null,
            'acceso_examen_token' => null,
        ], $attributes));
        $constancia->setRelation('examen', $examen);

        return $constancia;
    }
}
