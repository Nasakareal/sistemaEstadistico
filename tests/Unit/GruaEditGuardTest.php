<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Support\GruaEditGuard;
use PHPUnit\Framework\TestCase;

class GruaEditGuardTest extends TestCase
{
    public function test_bloquea_hechos_de_delegaciones_para_usuario_normal(): void
    {
        $hecho = new Hechos(['unidad_org_id' => 2]);

        $this->assertTrue(GruaEditGuard::locksHecho($this->usuarioConRoles([]), $hecho));
    }

    public function test_permite_modificar_a_administrador_y_superadmin(): void
    {
        $hecho = new Hechos(['unidad_org_id' => 2]);

        $this->assertFalse(GruaEditGuard::locksHecho($this->usuarioConRoles(['Administrador']), $hecho));
        $this->assertFalse(GruaEditGuard::locksHecho($this->usuarioConRoles(['Superadmin']), $hecho));
    }

    public function test_no_bloquea_unidades_que_no_son_delegaciones(): void
    {
        $hecho = new Hechos(['unidad_org_id' => 1]);
        $actividad = new Actividad(['unidad_org_id' => 1]);

        $this->assertFalse(GruaEditGuard::locksHecho($this->usuarioConRoles([]), $hecho));
        $this->assertFalse(GruaEditGuard::locksActividad($this->usuarioConRoles([]), $actividad));
    }

    public function test_detecta_si_el_vehiculo_tiene_datos_de_grua_o_corralon(): void
    {
        $sinGrua = new Vehiculo([
            'grua' => 'N/A',
            'corralon' => 'SIN CORRALON',
        ]);

        $conGrua = new Vehiculo([
            'grua_id' => 10,
            'grua' => 'N/A',
            'corralon' => 'N/A',
        ]);

        $conCorralon = new Vehiculo([
            'grua' => 'N/A',
            'corralon' => 'ESTRELLA 1',
        ]);

        $this->assertFalse(GruaEditGuard::vehicleHasGruaData($sinGrua));
        $this->assertTrue(GruaEditGuard::vehicleHasGruaData($conGrua));
        $this->assertTrue(GruaEditGuard::vehicleHasGruaData($conCorralon));
    }

    private function usuarioConRoles(array $roles)
    {
        return new class($roles) {
            private array $roles;

            public function __construct(array $roles)
            {
                $this->roles = $roles;
            }

            public function hasRole(string $role): bool
            {
                return in_array($role, $this->roles, true);
            }
        };
    }
}
