<?php

namespace Tests\Unit;

use App\Models\IncidenciaTipo;
use App\Models\Personal;
use App\Models\PersonalIncidencia;
use App\Models\Turno;
use App\Services\EstadoFuerzaService;
use Carbon\Carbon;
use Tests\TestCase;

class EstadoFuerzaServiceTest extends TestCase
{
    public function test_instructor_turno_descansa_en_fin_de_semana_sin_incidencia_servicio(): void
    {
        $personal = $this->personalInstructor(collect());

        $estado = (new EstadoFuerzaService())->estado(
            $personal,
            Carbon::parse('2026-05-30 09:00:00', 'America/Mexico_City')
        );

        $this->assertSame('FRANCO', $estado);
    }

    public function test_incidencia_servicio_activa_al_instructor_en_fin_de_semana(): void
    {
        $tipoServicio = new IncidenciaTipo([
            'clave' => 'SERVICIO',
            'nombre' => 'SERVICIO',
            'activo' => true,
        ]);

        $incidencia = new PersonalIncidencia([
            'fecha_inicio' => '2026-05-30',
            'fecha_fin' => null,
            'activo' => true,
        ]);
        $incidencia->setRelation('tipo', $tipoServicio);

        $personal = $this->personalInstructor(collect([$incidencia]));

        $estado = (new EstadoFuerzaService())->estado(
            $personal,
            Carbon::parse('2026-05-30 09:00:00', 'America/Mexico_City')
        );

        $this->assertSame('EN_SERVICIO', $estado);
    }

    private function personalInstructor($incidencias): Personal
    {
        $personal = new Personal([
            'estatus' => 'ACTIVO',
        ]);

        $personal->setRelation('turno', new Turno([
            'nombre' => 'Instructor',
            'slug' => 'instructor',
            'tipo_rol' => 'LUN_VIE',
            'activo' => true,
        ]));

        $personal->setRelation('incidencias', $incidencias);

        return $personal;
    }
}
