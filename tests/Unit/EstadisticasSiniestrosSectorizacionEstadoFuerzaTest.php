<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasSiniestrosSettingsController;
use App\Models\Personal;
use App\Models\Role;
use App\Models\Turno;
use App\Models\User;
use ReflectionClass;
use Tests\TestCase;

class EstadisticasSiniestrosSectorizacionEstadoFuerzaTest extends TestCase
{
    public function test_modulo_cuenta_personal_de_licencias_l_v_aunque_no_tenga_usuario(): void
    {
        $administrativoLunesViernes = $this->personal(70, null, 'ELEMENTO ADMINISTRATIVO');
        $administrativoLunesViernes->setRelation('turno', new Turno([
            'nombre' => 'ADMINISTRATIVO L-V',
            'slug' => 'administrativo-l-v',
            'tipo_rol' => 'LUN_VIE',
        ]));
        $plantilla = collect([
            $this->personalModulo(2, 'BERTHA MIJAYLI', 'ALCANTAR', 'ALMONTE'),
            $this->personalModulo(13, 'ALMA ROSA', 'CHAVEZ', 'SIERRA'),
            $this->personalModulo(19, 'SANDRA', 'GARCIA', 'VALDES'),
            $this->personalModulo(35, 'OMAR IVAN', 'MIJANGOS', 'CASTILLO'),
            $this->personalModulo(62, 'ANDREA YESSICA', 'LOPEZ', 'MURILLO'),
            $this->personalModulo(57, 'JOSE BENIGNO', 'VILLA', 'VILLA'),
            $administrativoLunesViernes,
        ]);
        $estados = collect([
            2 => 'EN_SERVICIO',
            13 => 'EN_SERVICIO',
            19 => 'EN_SERVICIO',
            35 => 'EN_SERVICIO',
            62 => 'EN_SERVICIO',
            57 => 'VACACIONES',
            70 => 'EN_SERVICIO',
        ]);

        $total = $this->contarModulo($plantilla, $estados);

        $this->assertSame(5, $total);
    }

    public function test_modulo_respeta_el_estado_calculado_desde_personal(): void
    {
        $personal = $this->personalModulo(62, 'ANDREA YESSICA', 'LOPEZ', 'MURILLO');

        $this->assertSame(1, $this->contarModulo(
            collect([$personal]),
            collect([62 => 'EN_SERVICIO'])
        ));
        $this->assertSame(0, $this->contarModulo(
            collect([$personal]),
            collect([62 => 'FRANCO'])
        ));
    }

    public function test_comandante_cuenta_solo_al_que_esta_en_servicio_y_nunca_mas_de_uno(): void
    {
        $usuarios = collect([
            $this->usuario(9, 'Comandante Turno B', 'Jefe de Grupo'),
            $this->usuario(33, 'Comandante Turno A', 'Jefe de Grupo'),
        ]);
        $plantilla = collect([
            $this->personal(36, 9, 'COMANDANTE TURNO B'),
            $this->personal(50, 33, 'COMANDANTE TURNO A'),
        ]);

        $this->assertSame(1, $this->contar(
            $usuarios,
            $plantilla,
            collect([36 => 'EN_SERVICIO', 50 => 'FRANCO']),
            'Jefe de Grupo',
            1
        ));
        $this->assertSame(1, $this->contar(
            $usuarios,
            $plantilla,
            collect([36 => 'EN_SERVICIO', 50 => 'EN_SERVICIO']),
            'Jefe de Grupo',
            1
        ));
    }

    private function contar($usuarios, $plantilla, $estados, string $rol, ?int $maximo = null): int
    {
        $reflection = new ReflectionClass(EstadisticasSiniestrosSettingsController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $metodo = $reflection->getMethod('contarUsuariosRolEnServicio');
        $metodo->setAccessible(true);

        return $metodo->invoke($controller, $usuarios, $plantilla, $estados, $rol, $maximo);
    }

    private function contarModulo($plantilla, $estados): int
    {
        $reflection = new ReflectionClass(EstadisticasSiniestrosSettingsController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $metodo = $reflection->getMethod('contarPersonalModuloEnServicio');
        $metodo->setAccessible(true);

        return $metodo->invoke($controller, $plantilla, $estados);
    }

    private function usuario(int $id, string $nombre, string $rolNombre): User
    {
        $rol = new Role([
            'name' => $rolNombre,
            'guard_name' => 'web',
        ]);
        $usuario = new User([
            'name' => $nombre,
            'estado' => 'Activo',
        ]);
        $usuario->setAttribute('id', $id);
        $usuario->setRelation('roles', collect([$rol]));

        return $usuario;
    }

    private function personal(
        int $id,
        ?int $userId,
        string $nombre,
        ?string $apellidoPaterno = null,
        ?string $apellidoMaterno = null
    ): Personal {
        $personal = new Personal([
            'user_id' => $userId,
            'nombre' => $nombre,
            'ap_paterno' => $apellidoPaterno,
            'ap_materno' => $apellidoMaterno,
            'estatus' => 'ACTIVO',
        ]);
        $personal->setAttribute('id', $id);

        return $personal;
    }

    private function personalModulo(
        int $id,
        string $nombre,
        ?string $apellidoPaterno = null,
        ?string $apellidoMaterno = null
    ): Personal {
        $personal = $this->personal(
            $id,
            null,
            $nombre,
            $apellidoPaterno,
            $apellidoMaterno
        );
        $personal->setRelation('turno', new Turno([
            'nombre' => 'LICENCIAS L-V',
            'slug' => 'licencias-l-v',
            'tipo_rol' => 'LUN_VIE',
        ]));

        return $personal;
    }
}
