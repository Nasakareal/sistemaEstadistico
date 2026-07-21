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
    public function test_modulo_incluye_usuario_con_personal_coincidente_aunque_falte_el_vinculo(): void
    {
        $usuarios = collect([
            $this->usuario(7, 'Bertha Mijayli Alcantar Almonte', 'Evaluador Teórico'),
            $this->usuario(75, 'Andrea Yessica López Murillo', 'Evaluador Teórico'),
            $this->usuario(48, 'Prueba', 'Evaluador Teórico'),
        ]);
        $plantilla = collect([
            $this->personal(2, 7, 'BERTHA MIJAYLI', 'ALCANTAR', 'ALMONTE'),
            $this->personal(62, null, 'ANDREA YESSICA', 'LOPEZ', 'MURILLO'),
        ]);
        $estados = collect([
            2 => 'EN_SERVICIO',
            62 => 'EN_SERVICIO',
        ]);

        $total = $this->contar($usuarios, $plantilla, $estados, 'Evaluador Teórico');

        $this->assertSame(2, $total);
    }

    public function test_modulo_respeta_el_estado_calculado_desde_personal(): void
    {
        $usuario = $this->usuario(75, 'Andrea Yessica López Murillo', 'Evaluador Teórico');
        $usuario->setRelation('turno', new Turno([
            'nombre' => 'Fin de semana',
            'tipo_rol' => 'SAB_DOM',
        ]));
        $personal = $this->personal(62, 75, 'ANDREA YESSICA', 'LOPEZ', 'MURILLO');

        $this->assertSame(1, $this->contar(
            collect([$usuario]),
            collect([$personal]),
            collect([62 => 'EN_SERVICIO']),
            'Evaluador Teórico'
        ));
        $this->assertSame(0, $this->contar(
            collect([$usuario]),
            collect([$personal]),
            collect([62 => 'FRANCO']),
            'Evaluador Teórico'
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
}
