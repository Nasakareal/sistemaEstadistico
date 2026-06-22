<?php

namespace Tests\Unit;

use App\Models\LicenciaPuntoCurso;
use App\Models\LicenciaPuntoCursoParticipante;
use App\Models\User;
use App\Services\BigBlueButtonService;
use Tests\TestCase;

class LicenciaPuntoCursoTest extends TestCase
{
    public function test_curso_de_recuperacion_requiere_15_horas(): void
    {
        $curso = new LicenciaPuntoCurso([
            'horas_totales' => LicenciaPuntoCurso::HORAS_REQUERIDAS,
            'estado' => LicenciaPuntoCurso::ESTADO_PROGRAMADO,
        ]);

        $this->assertSame(15, $curso->horas_totales);
        $this->assertSame('Programado', $curso->estado_label);
        $this->assertTrue($curso->puede_modificarse);
    }

    public function test_participante_cumple_horas_al_llegar_a_15(): void
    {
        $curso = new LicenciaPuntoCurso([
            'horas_totales' => LicenciaPuntoCurso::HORAS_REQUERIDAS,
        ]);

        $participante = new LicenciaPuntoCursoParticipante([
            'asistencia_horas' => 14.5,
            'estado' => LicenciaPuntoCursoParticipante::ESTADO_INSCRITO,
        ]);
        $participante->setRelation('curso', $curso);

        $this->assertFalse($participante->cumple_horas);

        $participante->asistencia_horas = 15;

        $this->assertTrue($participante->cumple_horas);
        $this->assertSame('Inscrito', $participante->estado_label);
    }

    public function test_participante_debe_aprobar_si_el_curso_exige_examen(): void
    {
        $curso = new LicenciaPuntoCurso([
            'horas_totales' => LicenciaPuntoCurso::HORAS_REQUERIDAS,
            'examen_habilitado' => true,
            'calificacion_por_instructor' => true,
            'calificacion_minima' => 80,
        ]);

        $participante = new LicenciaPuntoCursoParticipante([
            'asistencia_horas' => 15,
            'calificacion' => 79,
        ]);
        $participante->setRelation('curso', $curso);

        $this->assertTrue($participante->cumple_horas);
        $this->assertFalse($participante->cumple_calificacion);
        $this->assertFalse($participante->puede_acreditarse);

        $participante->calificacion = 80;

        $this->assertTrue($participante->cumple_calificacion);
        $this->assertTrue($participante->puede_acreditarse);
    }

    public function test_big_blue_button_join_url_usa_password_de_moderador(): void
    {
        config([
            'services.bigbluebutton.enabled' => true,
            'services.bigbluebutton.url' => 'https://bbb.example.test/bigbluebutton',
            'services.bigbluebutton.secret' => 'secret-test',
        ]);

        $curso = new LicenciaPuntoCurso([
            'bbb_meeting_id' => 'curso-123',
            'bbb_moderator_password' => 'mod-pass',
            'bbb_attendee_password' => 'att-pass',
            'bbb_create_time' => '123456',
        ]);
        $curso->id = 123;

        $user = new User([
            'name' => 'Instructor Uno',
        ]);
        $user->id = 77;

        $url = app(BigBlueButtonService::class)->moderatorJoinUrl($curso, $user);

        $this->assertStringContainsString('/api/join?', $url);
        $this->assertStringContainsString('password=mod-pass', $url);
        $this->assertStringNotContainsString('role=MODERATOR', $url);
        $this->assertStringContainsString('checksum=', $url);
    }
}
