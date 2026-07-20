<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasSiniestrosSettingsController;
use App\Models\Personal;
use App\Models\Role;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

class EstadisticasSiniestrosSectorizacionEstadoFuerzaTest extends TestCase
{
    public function test_modulo_usa_el_estado_calculado_desde_personal_y_no_el_turno_del_usuario(): void
    {
        $evaluadores = collect([
            $this->evaluador(101, 'Activo'),
            $this->evaluador(102, 'ACTIVO'),
        ]);
        $estados = collect([
            101 => 'EN_SERVICIO',
            102 => 'EN_SERVICIO',
        ]);

        $reflection = new ReflectionClass(EstadisticasSiniestrosSettingsController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $filtrar = $reflection->getMethod('filtrarPersonalLaborando');
        $tieneRol = $reflection->getMethod('usuarioTieneRol');
        $filtrar->setAccessible(true);
        $tieneRol->setAccessible(true);

        /** @var Collection $laborando */
        $laborando = $filtrar->invoke($controller, $evaluadores, $estados);
        $totalModulo = $laborando
            ->filter(fn (Personal $personal) => $tieneRol->invoke($controller, $personal, 'Evaluador Teórico'))
            ->count();

        $this->assertSame(2, $totalModulo);
    }

    private function evaluador(int $personalId, string $estadoUsuario): Personal
    {
        $rol = new Role([
            'name' => 'Evaluador Teórico',
            'guard_name' => 'web',
        ]);
        $usuario = new User([
            'name' => 'Evaluador ' . $personalId,
            'estado' => $estadoUsuario,
        ]);
        $usuario->setRelation('roles', collect([$rol]));

        // Este turno deliberadamente no coincide con la jornada de Personal.
        // El resumen no debe volver a calcular el estado desde aquí.
        $usuario->setRelation('turno', new Turno([
            'nombre' => 'Fin de semana',
            'tipo_rol' => 'SAB_DOM',
        ]));

        $personal = new Personal(['estatus' => 'ACTIVO']);
        $personal->setAttribute('id', $personalId);
        $personal->setRelation('user', $usuario);
        $personal->setRelation('turno', new Turno([
            'nombre' => 'LICENCIAS L-V',
            'tipo_rol' => 'LUN_VIE',
        ]));

        return $personal;
    }
}
