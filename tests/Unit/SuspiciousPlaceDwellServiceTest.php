<?php

namespace Tests\Unit;

use App\Models\Patrulla;
use App\Models\SuspiciousPlaceVisit;
use App\Models\Unidad;
use App\Models\User;
use App\Models\UserLocation;
use App\Services\SuspiciousPlaceDwellService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuspiciousPlaceDwellServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_alerts_after_five_minutes_and_when_unit_leaves(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        $service = $this->service();
        $start = Carbon::parse('2026-07-18 12:00:00', 'America/Mexico_City');

        Carbon::setTestNow($start);
        $this->assertSame('monitoring', $service->processLocation(
            $user,
            $this->location($user, $start, 19.6603522, -101.2373983)
        )['status']);

        Carbon::setTestNow($start->copy()->addMinutes(2));
        $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
        );

        Carbon::setTestNow($start->copy()->addMinutes(5));
        $entry = $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
        );
        $this->assertSame('dwell_alerted', $entry['status']);
        $this->assertSame('dry_run', $entry['notification_status']);

        for ($minute = 7; $minute <= 34; $minute += 2) {
            Carbon::setTestNow($start->copy()->addMinutes($minute));
            $service->processLocation(
                $user,
                $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
            );
        }

        Carbon::setTestNow($start->copy()->addMinutes(35));
        $exit = $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        );

        $this->assertSame('exit_alerted', $exit['status']);
        $this->assertSame(35, $exit['duration_minutes']);

        $visit = SuspiciousPlaceVisit::query()->where('patrulla_id', $patrulla->id)->firstOrFail();
        $this->assertSame('completed', $visit->status);
        $this->assertSame(2100, $visit->duration_seconds);
        $this->assertNotNull($visit->dwell_alerted_at);
        $this->assertNotNull($visit->exit_alerted_at);
        $this->assertSame(
            [$patrulla->numero_economico . ' - ' . $user->nombre_completo, '5', 'Grúas Muñoz'],
            $visit->notification_meta['entry']['parameters']
        );
        $this->assertSame(
            [$patrulla->numero_economico . ' - ' . $user->nombre_completo, '35', 'Grúas Muñoz'],
            $visit->notification_meta['exit']['parameters']
        );
    }

    public function test_pass_through_does_not_send_alerts(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        $service = $this->service();
        $start = Carbon::parse('2026-07-18 13:00:00', 'America/Mexico_City');

        Carbon::setTestNow($start);
        $service->processLocation(
            $user,
            $this->location($user, $start, 19.6603522, -101.2373983)
        );

        Carbon::setTestNow($start->copy()->addMinutes(2));
        $result = $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        );

        $this->assertSame('passed_without_dwell', $result['status']);
        $visit = SuspiciousPlaceVisit::query()->where('patrulla_id', $patrulla->id)->firstOrFail();
        $this->assertSame('discarded', $visit->status);
        $this->assertNull($visit->dwell_alerted_at);
        $this->assertNull($visit->exit_alerted_at);
    }

    public function test_uses_the_approved_entry_and_exit_templates(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        config([
            'services.whatsapp.suspicious_place.dry_run' => false,
            'services.whatsapp.suspicious_place.to' => '5214434765057',
        ]);

        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->expects($this->exactly(2))
            ->method('sendTemplate')
            ->withConsecutive(
                [
                    '5214434765057',
                    'alerta_permanencia_siniestros_v1',
                    [$patrulla->numero_economico . ' - ' . $user->nombre_completo, '5', 'Grúas Muñoz'],
                    'es_MX',
                ],
                [
                    '5214434765057',
                    'alerta_salida_permanencia_siniestros_v1',
                    [$patrulla->numero_economico . ' - ' . $user->nombre_completo, '7', 'Grúas Muñoz'],
                    'es_MX',
                ]
            )
            ->willReturnOnConsecutiveCalls(
                ['ok' => true, 'body' => ['messages' => [['id' => 'wamid.ENTRY']]]],
                ['ok' => true, 'body' => ['messages' => [['id' => 'wamid.EXIT']]]]
            );

        $guard = $this->createMock(WhatsAppSendGuard::class);
        $guard->expects($this->exactly(2))->method('reserve')->willReturn(true);
        $guard->expects($this->exactly(2))->method('markSent');
        $guard->expects($this->never())->method('release');
        $service = new SuspiciousPlaceDwellService($cloud, $guard);
        $start = Carbon::parse('2026-07-18 15:00:00', 'America/Mexico_City');

        Carbon::setTestNow($start);
        $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
        );
        Carbon::setTestNow($start->copy()->addMinutes(2));
        $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
        );
        Carbon::setTestNow($start->copy()->addMinutes(5));
        $this->assertSame('dwell_alerted', $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
        )['status']);

        Carbon::setTestNow($start->copy()->addMinutes(7));
        $this->assertSame('exit_alerted', $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        )['status']);
    }

    public function test_tracking_gap_does_not_invent_an_exit_time(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        $service = $this->service();
        $start = Carbon::parse('2026-07-18 16:00:00', 'America/Mexico_City');

        foreach ([0, 2, 5] as $minute) {
            Carbon::setTestNow($start->copy()->addMinutes($minute));
            $service->processLocation(
                $user,
                $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
            );
        }

        Carbon::setTestNow($start->copy()->addMinutes(10));
        $firstOutside = $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        );
        $this->assertSame('tracking_gap', $firstOutside['status']);

        Carbon::setTestNow($start->copy()->addMinutes(12));
        $secondOutside = $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        );
        $this->assertSame('outside', $secondOutside['status']);

        $visit = SuspiciousPlaceVisit::query()->where('patrulla_id', $patrulla->id)->firstOrFail();
        $this->assertSame('tracking_lost', $visit->status);
        $this->assertNull($visit->exit_alerted_at);
    }

    public function test_processes_queued_client_events_after_coverage_returns(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        $service = $this->service();
        $start = Carbon::parse('2026-07-18 17:00:00', 'America/Mexico_City');
        $visitId = '11111111-1111-4111-a111-111111111111';

        Carbon::setTestNow($start->copy()->addMinutes(35));
        $dwell = $service->processClientEvent($user, $this->clientEvent(
            $visitId,
            'dwell',
            $start,
            $start->copy()->addMinutes(5),
            300,
            19.6603522,
            -101.2373983
        ));
        $this->assertSame('dwell_alerted', $dwell['status']);

        $exit = $service->processClientEvent($user, $this->clientEvent(
            $visitId,
            'exit',
            $start,
            $start->copy()->addMinutes(35),
            2100,
            19.6633522,
            -101.2373983
        ));
        $this->assertSame('exit_alerted', $exit['status']);
        $this->assertSame(35, $exit['duration_minutes']);

        $visit = SuspiciousPlaceVisit::query()->where('client_visit_id', $visitId)->firstOrFail();
        $this->assertSame($patrulla->id, $visit->patrulla_id);
        $this->assertSame('completed', $visit->status);
        $this->assertSame(2100, $visit->duration_seconds);
        $this->assertNotNull($visit->client_entry_received_at);
        $this->assertNotNull($visit->client_exit_received_at);
    }

    public function test_client_events_do_not_duplicate_server_detection(): void
    {
        [$user, $patrulla] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id);
        $service = $this->service();
        $start = Carbon::parse('2026-07-18 18:00:00', 'America/Mexico_City');
        $visitId = '22222222-2222-4222-a222-222222222222';

        foreach ([0, 2, 5] as $minute) {
            Carbon::setTestNow($start->copy()->addMinutes($minute));
            $service->processLocation(
                $user,
                $this->location($user, Carbon::now(), 19.6603522, -101.2373983)
            );
        }
        Carbon::setTestNow($start->copy()->addMinutes(7));
        $service->processLocation(
            $user,
            $this->location($user, Carbon::now(), 19.6633522, -101.2373983)
        );

        $dwell = $service->processClientEvent($user, $this->clientEvent(
            $visitId,
            'dwell',
            $start,
            $start->copy()->addMinutes(5),
            300,
            19.6603522,
            -101.2373983
        ));
        $exit = $service->processClientEvent($user, $this->clientEvent(
            $visitId,
            'exit',
            $start,
            $start->copy()->addMinutes(7),
            420,
            19.6633522,
            -101.2373983
        ));

        $this->assertSame('duplicate', $dwell['status']);
        $this->assertSame('duplicate', $exit['status']);
        $this->assertSame(1, SuspiciousPlaceVisit::query()
            ->where('patrulla_id', $patrulla->id)
            ->count());
    }

    public function test_ignores_users_outside_siniestros(): void
    {
        [$user] = $this->siniestrosContext();
        $this->configure((int) $user->unidad_id + 1000);
        $now = Carbon::parse('2026-07-18 14:00:00', 'America/Mexico_City');
        Carbon::setTestNow($now);

        $result = $this->service()->processLocation(
            $user,
            $this->location($user, $now, 19.6603522, -101.2373983)
        );

        $this->assertSame('ignored', $result['status']);
        $this->assertSame('user_not_siniestros', $result['reason']);
        $this->assertSame(0, SuspiciousPlaceVisit::query()->count());
    }

    private function configure(int $unitId): void
    {
        config([
            'services.whatsapp.suspicious_place.enabled' => true,
            'services.whatsapp.suspicious_place.dry_run' => true,
            'services.whatsapp.suspicious_place.to' => '5214434101796,5214434765057',
            'services.whatsapp.suspicious_place.unit_id' => $unitId,
            'services.whatsapp.suspicious_place.place_key' => 'gruas-munoz',
            'services.whatsapp.suspicious_place.place_name' => 'Grúas Muñoz',
            'services.whatsapp.suspicious_place.latitude' => 19.6603522,
            'services.whatsapp.suspicious_place.longitude' => -101.2373983,
            'services.whatsapp.suspicious_place.entry_radius_meters' => 120,
            'services.whatsapp.suspicious_place.exit_radius_meters' => 180,
            'services.whatsapp.suspicious_place.dwell_minutes' => 5,
            'services.whatsapp.suspicious_place.max_accuracy_meters' => 100,
            'services.whatsapp.suspicious_place.location_max_age_minutes' => 3,
            'services.whatsapp.suspicious_place.max_sample_gap_minutes' => 3,
            'services.whatsapp.suspicious_place.entry_template' => 'alerta_permanencia_siniestros_v1',
            'services.whatsapp.suspicious_place.exit_template' => 'alerta_salida_permanencia_siniestros_v1',
            'services.whatsapp.suspicious_place.template_language' => 'es_MX',
        ]);
    }

    private function siniestrosContext(): array
    {
        $suffix = (string) random_int(100000, 999999);
        $unit = Unidad::query()->firstOrCreate(
            ['slug' => 'siniestros'],
            ['nombre' => 'SINIESTROS', 'activa' => true]
        );
        $patrulla = Patrulla::query()->create([
            'numero_economico' => '3' . $suffix,
            'unidad_id' => $unit->id,
            'activa' => true,
        ]);
        $user = User::factory()->create([
            'unidad_id' => $unit->id,
            'patrulla_id' => $patrulla->id,
            'compartir_ubicacion' => true,
        ]);

        return [$user, $patrulla];
    }

    private function location(
        User $user,
        Carbon $capturedAt,
        float $lat,
        float $lng
    ): UserLocation {
        return new UserLocation([
            'user_id' => $user->id,
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => 10,
            'speed' => 0,
            'captured_at' => $capturedAt,
        ]);
    }

    private function clientEvent(
        string $visitId,
        string $eventType,
        Carbon $enteredAt,
        Carbon $occurredAt,
        int $durationSeconds,
        float $lat,
        float $lng
    ): array {
        return [
            'visit_id' => $visitId,
            'event_type' => $eventType,
            'place_key' => 'gruas-munoz',
            'entered_at' => $enteredAt->toIso8601String(),
            'occurred_at' => $occurredAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => 10,
        ];
    }

    private function service(): SuspiciousPlaceDwellService
    {
        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->expects($this->never())->method('sendTemplate');
        $guard = $this->createMock(WhatsAppSendGuard::class);
        $guard->expects($this->never())->method('reserve');

        return new SuspiciousPlaceDwellService($cloud, $guard);
    }
}
