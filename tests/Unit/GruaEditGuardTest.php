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

    public function test_permite_asignar_grua_si_el_vehiculo_aun_no_tiene_grua(): void
    {
        $vehiculo = new Vehiculo([
            'grua' => 'N/A',
            'corralon' => 'SIN CORRALON',
        ]);

        $this->assertFalse(GruaEditGuard::vehicleHasGrua($vehiculo));
        $this->assertTrue(GruaEditGuard::requestedGruaMatchesCurrent($vehiculo, 99));
    }

    public function test_detecta_cambio_de_grua_cuando_ya_existe_grua(): void
    {
        $vehiculo = new Vehiculo([
            'grua_id' => 10,
            'grua' => 'N/A',
        ]);

        $this->assertTrue(GruaEditGuard::vehicleHasGrua($vehiculo));
        $this->assertTrue(GruaEditGuard::requestedGruaMatchesCurrent($vehiculo, 10));
        $this->assertFalse(GruaEditGuard::requestedGruaMatchesCurrent($vehiculo, 11));
    }

    public function test_catalogo_completo_para_administrador_y_subdirector_de_delegaciones(): void
    {
        $this->assertTrue(GruaEditGuard::canViewFullGruaCatalog($this->usuarioConRoles(['Administrador'], 2)));
        $this->assertTrue(GruaEditGuard::canViewFullGruaCatalog($this->usuarioConRoles(['Subdirector'], 2)));
    }

    public function test_catalogo_completo_no_se_abre_a_otros_roles_de_delegaciones(): void
    {
        $this->assertFalse(GruaEditGuard::canViewFullGruaCatalog($this->usuarioConRoles(['Delegado'], 2)));
        $this->assertFalse(GruaEditGuard::canViewFullGruaCatalog($this->usuarioConRoles(['Administrador'], 1)));
    }

    public function test_vialidades_urbanas_usa_el_catalogo_de_gruas_de_siniestros(): void
    {
        $this->assertTrue(GruaEditGuard::usesSiniestrosGruaCatalog($this->usuarioConRoles([], 1)));
        $this->assertTrue(GruaEditGuard::usesSiniestrosGruaCatalog($this->usuarioConRoles([], 5)));
        $this->assertFalse(GruaEditGuard::usesSiniestrosGruaCatalog($this->usuarioConRoles([], 2)));
    }

    private function usuarioConRoles(array $roles, ?int $unidadId = null)
    {
        return new class($roles, $unidadId) {
            private array $roles;
            public ?int $unidad_id;

            public function __construct(array $roles, ?int $unidadId)
            {
                $this->roles = $roles;
                $this->unidad_id = $unidadId;
            }

            public function hasRole(string $role): bool
            {
                return in_array($role, $this->roles, true);
            }
        };
    }
}
