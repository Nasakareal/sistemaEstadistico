<?php

namespace Tests\Unit;

use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use App\Services\LicenciaPuntosTurnoAccessService;
use App\Services\TurnoService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LicenciaPuntosTurnoAccessServiceTest extends TestCase
{
    public function test_siniestros_turno_b_activo_permite_b_y_bloquea_a(): void
    {
        $momento = Carbon::parse('2026-06-04 09:00:00', 'America/Mexico_City');
        $turnoA = $this->turno('A', 'a', '2026-02-23 07:00:00');
        $turnoB = $this->turno('B', 'b', '2026-02-24 07:00:00');
        $service = new LicenciaPuntosTurnoAccessService($this->turnoServiceConActivo($turnoB));

        $statusA = $service->statusForUser($this->siniestrosUser($turnoA), $momento);
        $statusB = $service->statusForUser($this->siniestrosUser($turnoB), $momento);

        $this->assertFalse($statusA['allowed']);
        $this->assertSame('turno_descanso', $statusA['reason']);
        $this->assertSame('B', $statusA['turno_en_servicio']['nombre']);

        $this->assertTrue($statusB['allowed']);
        $this->assertSame('allowed', $statusB['reason']);
    }

    public function test_siniestros_sin_turno_bloquea_por_defecto(): void
    {
        $service = new LicenciaPuntosTurnoAccessService($this->turnoServiceConActivo(null));

        $status = $service->statusForUser($this->siniestrosUser(null));

        $this->assertFalse($status['allowed']);
        $this->assertSame('sin_turno', $status['reason']);
    }

    public function test_fuera_de_siniestros_no_aplica_el_candado(): void
    {
        $service = new LicenciaPuntosTurnoAccessService($this->turnoServiceConActivo(null));

        $status = $service->statusForUser($this->userUnidad(5, 'vialidades-urbanas', null));

        $this->assertTrue($status['allowed']);
        $this->assertFalse($status['applies']);
        $this->assertSame('not_siniestros', $status['reason']);
    }

    private function siniestrosUser(?Turno $turno): User
    {
        return $this->userUnidad(1, 'siniestros', $turno);
    }

    private function userUnidad(int $unidadId, string $unidadSlug, ?Turno $turno): User
    {
        $user = new User([
            'unidad_id' => $unidadId,
            'turno_id' => $turno ? $turno->id : null,
        ]);
        $user->setRelation('unidad', new Unidad([
            'id' => $unidadId,
            'nombre' => strtoupper($unidadSlug),
            'slug' => $unidadSlug,
        ]));
        $user->setRelation('turno', $turno);
        $user->setRelation('roles', collect([
            new Role(['name' => 'Perito']),
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

    private function turnoServiceConActivo(?Turno $turnoActivo): TurnoService
    {
        return new class($turnoActivo) extends TurnoService {
            private ?Turno $turnoActivo;

            public function __construct(?Turno $turnoActivo)
            {
                $this->turnoActivo = $turnoActivo;
            }

            public function turnoActivoEn(Carbon $fechaHora)
            {
                return $this->turnoActivo;
            }
        };
    }
}
