<?php

namespace Tests\Unit;

use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use App\Services\LocationTrackingEligibilityService;
use App\Services\TurnoService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocationTrackingEligibilityServiceTest extends TestCase
{
    public function test_agente_vial_turno_b_trabaja_el_4_de_junio_y_a_descansa(): void
    {
        $service = new LocationTrackingEligibilityService(new TurnoService());
        $momento = Carbon::parse('2026-06-04 09:00:00', 'America/Mexico_City');

        $turnoA = $this->turno('A', 'a', '2026-02-23 07:00:00');
        $turnoB = $this->turno('B', 'b', '2026-02-24 07:00:00');

        $this->assertFalse($service->statusForUser($this->agenteVial($turnoA), $momento)['allowed']);
        $this->assertTrue($service->statusForUser($this->agenteVial($turnoB), $momento)['allowed']);
    }

    public function test_agente_vial_turno_a_trabaja_el_5_de_junio_y_b_descansa(): void
    {
        $service = new LocationTrackingEligibilityService(new TurnoService());
        $momento = Carbon::parse('2026-06-05 09:00:00', 'America/Mexico_City');

        $turnoA = $this->turno('A', 'a', '2026-02-23 07:00:00');
        $turnoB = $this->turno('B', 'b', '2026-02-24 07:00:00');

        $this->assertTrue($service->statusForUser($this->agenteVial($turnoA), $momento)['allowed']);
        $this->assertFalse($service->statusForUser($this->agenteVial($turnoB), $momento)['allowed']);
    }

    public function test_vialidades_usuario_sin_rol_agente_vial_no_reporta_ubicacion(): void
    {
        $service = new LocationTrackingEligibilityService(new TurnoService());
        $momento = Carbon::parse('2026-06-04 09:00:00', 'America/Mexico_City');
        $user = $this->vialidadesUserConRol('Responsable de Turno', $this->turno('B', 'b', '2026-02-24 07:00:00'));

        $status = $service->statusForUser($user, $momento);

        $this->assertFalse($status['allowed']);
        $this->assertSame('rol_no_autorizado_vialidades', $status['reason']);
    }

    private function agenteVial(Turno $turno): User
    {
        return $this->vialidadesUserConRol('Agente Vial', $turno);
    }

    private function vialidadesUserConRol(string $rol, Turno $turno): User
    {
        $user = new User([
            'unidad_id' => 5,
            'turno_id' => $turno->id,
            'compartir_ubicacion' => 1,
        ]);
        $user->setRelation('unidad', new Unidad([
            'id' => 5,
            'nombre' => 'PROTECCION EN VIALIDADES URBANAS',
            'slug' => 'vialidades-urbanas',
        ]));
        $user->setRelation('turno', $turno);
        $user->setRelation('roles', collect([
            new Role(['name' => $rol]),
        ]));

        return $user;
    }

    private function turno(string $nombre, string $slug, string $inicio): Turno
    {
        return new Turno([
            'id' => $slug === 'a' ? 1 : 2,
            'nombre' => $nombre,
            'slug' => $slug,
            'tipo_rol' => '24X24',
            'ciclo_inicio' => $inicio,
            'trabajo_horas' => 24,
            'descanso_horas' => 24,
            'activo' => true,
        ]);
    }
}
