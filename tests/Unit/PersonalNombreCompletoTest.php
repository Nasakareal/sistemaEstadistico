<?php

namespace Tests\Unit;

use App\Models\Personal;
use PHPUnit\Framework\TestCase;

class PersonalNombreCompletoTest extends TestCase
{
    public function test_nombre_completo_usa_apellidos_antes_de_nombres(): void
    {
        $personal = new Personal([
            'nombre' => 'JUAN CARLOS',
            'ap_paterno' => 'PEREZ',
            'ap_materno' => 'LOPEZ',
            'grado' => 'OFICIAL',
        ]);

        $this->assertSame('PEREZ LOPEZ JUAN CARLOS', $personal->nombre_completo);
        $this->assertSame('OFICIAL PEREZ LOPEZ JUAN CARLOS', $personal->nombreCompletoConGrado());
    }

    public function test_nombre_completo_omite_partes_vacias(): void
    {
        $this->assertSame(
            'LOPEZ MARIA',
            Personal::formarNombreCompleto('MARIA', null, 'LOPEZ')
        );
    }
}
