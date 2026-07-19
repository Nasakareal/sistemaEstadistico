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

    public function test_relevos_radio_12x36_cubren_manana_y_noche(): void
    {
        $service = new EstadoFuerzaService();
        $radioManana = $this->personalRadio('Radio-B Mañana', 'radio-b-manana', '2026-02-24 07:00:00');
        $radioNoche = $this->personalRadio('Radio-B Noche', 'radio-b-noche', '2026-02-24 19:00:00');

        $this->assertSame(
            'EN_SERVICIO',
            $service->estado($radioManana, Carbon::parse('2026-04-03 12:00:00', 'America/Mexico_City'))
        );
        $this->assertSame(
            'FRANCO',
            $service->estado($radioManana, Carbon::parse('2026-04-03 20:00:00', 'America/Mexico_City'))
        );
        $this->assertSame(
            'FRANCO',
            $service->estado($radioNoche, Carbon::parse('2026-04-03 12:00:00', 'America/Mexico_City'))
        );
        $this->assertSame(
            'EN_SERVICIO',
            $service->estado($radioNoche, Carbon::parse('2026-04-03 20:00:00', 'America/Mexico_City'))
        );
    }

    private function personalRadio(string $nombre, string $slug, string $inicio): Personal
    {
        $personal = new Personal(['estatus' => 'ACTIVO']);
        $personal->setRelation('turno', new Turno([
            'nombre' => $nombre,
            'slug' => $slug,
            'tipo_rol' => 'RADIO_12X36',
            'ciclo_inicio' => $inicio,
            'trabajo_horas' => 12,
            'descanso_horas' => 36,
            'activo' => true,
        ]));
        $personal->setRelation('incidencias', collect());

        return $personal;
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
