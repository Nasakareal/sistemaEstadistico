<?php

namespace Tests\Unit;

use App\Services\ActividadDuplicateGuard;
use Tests\TestCase;

class ActividadDuplicateGuardTest extends TestCase
{
    public function test_submission_fingerprint_ignores_values_that_change_during_a_retry(): void
    {
        $guard = new ActividadDuplicateGuard();
        $payload = [
            'client_uuid' => '11111111-1111-4111-a111-111111111111',
            'actividad_categoria_id' => 8,
            'actividad_subcategoria_id' => 52,
            'fecha' => '2026-09-02',
            'hora' => '10:01',
            'lugar' => ' Avenida Morelos ',
            'municipio' => 'Uruapan',
            'lat' => '19.420001',
            'lng' => '-102.060001',
            'km_recorridos' => '4.50',
        ];
        $retry = array_merge($payload, [
            'client_uuid' => '22222222-2222-4222-a222-222222222222',
            'hora' => '10:09',
            'km_recorridos' => '0.00',
        ]);

        $this->assertSame(
            $guard->submissionFingerprint(15, $payload, ['foto-b', 'foto-a']),
            $guard->submissionFingerprint(15, $retry, ['foto-a', 'foto-b'])
        );
    }

    public function test_submission_fingerprint_distinguishes_genuinely_different_activities(): void
    {
        $guard = new ActividadDuplicateGuard();
        $payload = [
            'actividad_categoria_id' => 8,
            'actividad_subcategoria_id' => 52,
            'fecha' => '2026-09-02',
            'lugar' => 'Avenida Morelos',
            'municipio' => 'Uruapan',
        ];
        $base = $guard->submissionFingerprint(15, $payload, ['foto-a']);

        $this->assertNotSame($base, $guard->submissionFingerprint(
            15,
            array_merge($payload, ['lugar' => 'Otra calle']),
            ['foto-a']
        ));
        $this->assertNotSame($base, $guard->submissionFingerprint(15, $payload, ['foto-distinta']));
        $this->assertNotSame($base, $guard->submissionFingerprint(16, $payload, ['foto-a']));
        $this->assertNotSame($base, $guard->submissionFingerprint(15, array_merge($payload, [
            'vehiculos' => [[
                'marca' => 'NISSAN',
                'placas' => 'ABC123',
            ]],
        ]), ['foto-a']));
    }
}
