<?php

namespace Tests\Unit;

use App\Models\Personal;
use App\Models\Turno;
use App\Services\VialidadesUrbanas\Hojas\EstadoFuerzaSheetService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasEstadoFuerzaSheetServiceTest extends TestCase
{
    public function test_turnos_a_y_b_rolan_24x24_con_b_como_referencia(): void
    {
        $this->assertSame('EN_SERVICIO', $this->estado('B', '2026-06-04 17:00:00'));
        $this->assertSame('FRANCO', $this->estado('A', '2026-06-04 17:00:00'));
        $this->assertSame('EN_SERVICIO', $this->estado('A', '2026-06-05 17:00:00'));
        $this->assertSame('FRANCO', $this->estado('B', '2026-06-05 17:00:00'));
        $this->assertSame('EN_SERVICIO', $this->estado('B', '2026-06-06 17:00:00'));
    }

    public function test_jornada_acumulada_solo_trabaja_sabado_y_domingo(): void
    {
        $this->assertSame('FRANCO', $this->estado('JORNADA ACUMULADA S-D', '2026-06-05 17:00:00', 'SAB_DOM'));
        $this->assertSame('EN_SERVICIO', $this->estado('JORNADA ACUMULADA S-D', '2026-06-06 17:00:00', 'SAB_DOM'));
        $this->assertSame('EN_SERVICIO', $this->estado('JORNADA ACUMULADA S-D', '2026-06-07 17:00:00', 'SAB_DOM'));
        $this->assertSame('FRANCO', $this->estado('JORNADA ACUMULADA S-D', '2026-06-08 17:00:00', 'SAB_DOM'));
    }

    private function estado(string $turnoNombre, string $fechaHora, ?string $tipoRol = null): string
    {
        $personal = new Personal([
            'estatus' => 'ACTIVO',
        ]);

        $personal->setRelation('turno', new Turno([
            'nombre' => $turnoNombre,
            'slug' => mb_strtolower(str_replace(' ', '-', $turnoNombre), 'UTF-8'),
            'tipo_rol' => $tipoRol ?? ($turnoNombre === 'A' || $turnoNombre === 'B' ? '24X24' : null),
            'activo' => true,
        ]));
        $personal->setRelation('incidencias', collect());

        $method = new ReflectionMethod(EstadoFuerzaSheetService::class, 'estadoVialidadesUrbanas');
        $method->setAccessible(true);

        return $method->invoke(
            new EstadoFuerzaSheetService(),
            $personal,
            Carbon::parse($fechaHora, 'America/Mexico_City')
        );
    }
}
