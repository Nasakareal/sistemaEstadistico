<?php

namespace Tests\Unit;

use App\Models\C5iServiceResponse;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\WhatsAppWebGroup;
use App\Models\WhatsAppWebMessage;
use App\Services\C5iResponseTimeService;
use App\Services\C5iSiniestrosRecommendationService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class C5iResponseTimeServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reconoce_arribos_con_y_sin_clave_86(): void
    {
        $service = $this->service();

        $this->assertTrue($service->isArrivalMessage('86, arribo al 40'));
        $this->assertTrue($service->isArrivalMessage('ya en el 40'));
        $this->assertTrue($service->isArrivalMessage('ya en el K6 indicado'));
        $this->assertTrue($service->isArrivalMessage('en el lugar, sin novedad'));
        $this->assertFalse($service->isArrivalMessage('3252 aproxímate al 40'));
        $this->assertFalse($service->isArrivalMessage('3252 K5'));
    }

    public function test_informa_si_el_mensaje_de_arribo_se_envia_despues_del_arribo_gps(): void
    {
        $this->configure(true);
        [$group, $patrulla, $user, $phone] = $this->operationalContext(true);
        $service = $this->service();
        $reportedAt = Carbon::parse('2026-07-17 16:00:00', 'America/Mexico_City');

        $incident = $this->message(
            $group,
            '5214437916890@c.us',
            'FOLIO C5I: TEST-2040 AVENIDA MADERO LOCALIDAD MORELIA '
                . 'LATITUD:19.7000000 LONGITUD:-101.2500000',
            $reportedAt
        );
        $this->assertSame('incident_recorded', $service->processMessage($incident)['status']);

        $assignment = $this->message(
            $group,
            '5214433284672@c.us',
            $patrulla->numero_economico . ' aproxímate al 40',
            $reportedAt->copy()->addMinutes(2),
            $incident->whatsapp_message_id
        );
        $this->assertSame('assigned', $service->processMessage($assignment)['status']);

        $location = UserLocation::query()->create([
            'user_id' => $user->id,
            'lat' => 19.7000000,
            'lng' => -101.2500000,
            'accuracy' => 12,
            'captured_at' => $reportedAt->copy()->addMinutes(10),
        ]);
        $this->assertSame('gps_arrived', $service->processLocation($user, $location)['status']);

        $arrival = $this->message(
            $group,
            $phone . '@c.us',
            'ya en el K6 indicado',
            $reportedAt->copy()->addMinutes(30)
        );
        $this->assertSame('complete', $service->processMessage($arrival)['status']);

        $response = C5iServiceResponse::query()->where('incident_message_id', $incident->id)->firstOrFail();
        $this->assertSame(600, $response->report_to_gps_seconds);
        $this->assertSame(480, $response->assignment_to_gps_seconds);
        $this->assertSame(1200, $response->arrival_message_delay_seconds);
        $this->assertSame('dry_run', $response->notification_status);
        $this->assertStringContainsString(
            '20 minutos después del arribo GPS',
            $response->notification_meta['template_params'][9]
        );
    }

    public function test_envia_plantilla_oficial_al_destinatario_piloto(): void
    {
        $this->configure(false);
        [$group, $patrulla, $user, $phone] = $this->operationalContext();
        $reportedAt = Carbon::parse('2026-07-17 18:00:00', 'America/Mexico_City');

        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->expects($this->once())
            ->method('sendTemplate')
            ->with(
                '5214434765057',
                'alerta_tiempo_reaccion_siniestros_v1',
                $this->callback(function ($params) {
                    return count($params) === 10
                        && strpos($params[9], 'después del arribo GPS') !== false;
                }),
                'es_MX'
            )
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'body' => ['messages' => [['id' => 'wamid.REACCION.TEST']]],
            ]);

        $guard = $this->createMock(WhatsAppSendGuard::class);
        $guard->expects($this->once())->method('reserve')->willReturn(true);
        $guard->expects($this->once())->method('markSent');
        $guard->expects($this->never())->method('release');
        $service = $this->service($cloud, $guard);

        $incident = $this->message(
            $group,
            '5214437916890@c.us',
            'FOLIO: PILOTO-1 CALZADA JUAREZ LATITUD:19.7000000 LONGITUD:-101.2500000',
            $reportedAt
        );
        $service->processMessage($incident);
        $service->processMessage($this->message(
            $group,
            '5214433284672@c.us',
            $patrulla->numero_economico . ' K5',
            $reportedAt->copy()->addMinute(),
            $incident->whatsapp_message_id
        ));

        $location = UserLocation::query()->create([
            'user_id' => $user->id,
            'lat' => 19.7000000,
            'lng' => -101.2500000,
            'accuracy' => 8,
            'captured_at' => $reportedAt->copy()->addMinutes(5),
        ]);
        $service->processLocation($user, $location);
        $service->processMessage($this->message(
            $group,
            $phone . '@c.us',
            'en el 40 indicado',
            $reportedAt->copy()->addMinutes(8)
        ));

        $this->assertSame('sent', C5iServiceResponse::query()->firstOrFail()->notification_status);
    }

    public function test_mensaje_citado_identifica_el_servicio_correcto_si_hay_dos_reportes(): void
    {
        $this->configure(true);
        [$group, $patrulla] = $this->operationalContext();
        $service = $this->service();
        $start = Carbon::parse('2026-07-17 20:00:00', 'America/Mexico_City');
        $first = $this->message(
            $group,
            '5214437916890@c.us',
            'FOLIO: PRIMERO LATITUD:19.7000000 LONGITUD:-101.2500000',
            $start
        );
        $second = $this->message(
            $group,
            '5214437916890@c.us',
            'FOLIO: SEGUNDO LATITUD:19.7100000 LONGITUD:-101.2600000',
            $start->copy()->addMinute()
        );
        $service->processMessage($first);
        $service->processMessage($second);

        $assignment = $this->message(
            $group,
            '5214433284672@c.us',
            $patrulla->numero_economico . ' aproxímate al 40',
            $start->copy()->addMinutes(2),
            $first->whatsapp_message_id
        );
        $service->processMessage($assignment);

        $this->assertSame(
            $assignment->id,
            C5iServiceResponse::query()->where('incident_message_id', $first->id)->value('assignment_message_id')
        );
        $this->assertNull(
            C5iServiceResponse::query()->where('incident_message_id', $second->id)->value('assignment_message_id')
        );
    }

    private function configure(bool $dryRun): void
    {
        config([
            'services.whatsapp.c5i_response_time.enabled' => true,
            'services.whatsapp.c5i_response_time.dry_run' => $dryRun,
            'services.whatsapp.c5i_response_time.to' => '5214434765057',
            'services.whatsapp.c5i_response_time.group_ids' => '120363424100430316@g.us',
            'services.whatsapp.c5i_response_time.source_author_ids' => '5214437916890@c.us',
            'services.whatsapp.c5i_response_time.dispatch_author_ids' => '5214433284672@c.us',
            'services.whatsapp.c5i_response_time.template' => 'alerta_tiempo_reaccion_siniestros_v1',
            'services.whatsapp.c5i_response_time.template_language' => 'es_MX',
            'services.whatsapp.c5i_response_time.unit_slug' => 'siniestros',
            'services.whatsapp.c5i_response_time.arrival_radius_meters' => 200,
            'services.whatsapp.c5i_response_time.max_accuracy_meters' => 100,
            'services.whatsapp.c5i_response_time.open_service_minutes' => 240,
        ]);
    }

    private function operationalContext(bool $useSecondaryPhone = false): array
    {
        $suffix = (string) random_int(100000, 999999);
        $unit = Unidad::query()->firstOrCreate(
            ['slug' => 'siniestros'],
            ['nombre' => 'SINIESTROS', 'activa' => true]
        );
        $patrulla = Patrulla::query()->create([
            'numero_economico' => '9' . $suffix,
            'unidad_id' => $unit->id,
            'activa' => true,
        ]);
        $phone = '521443' . substr($suffix . '0000000', 0, 7);
        $user = User::factory()->create([
            'unidad_id' => $unit->id,
            'patrulla_id' => $patrulla->id,
            'telefono' => null,
            'telefono_whatsapp_operativo' => $useSecondaryPhone ? null : $phone,
            'telefono_whatsapp_operativo_secundario' => $useSecondaryPhone ? $phone : null,
            'compartir_ubicacion' => true,
        ]);
        $group = WhatsAppWebGroup::query()->firstOrCreate(
            ['whatsapp_id' => '120363424100430316@g.us'],
            ['name' => 'SINIESTROS GC', 'participant_count' => 57, 'last_seen_at' => now()]
        );

        return [$group, $patrulla, $user, $phone];
    }

    private function message(
        WhatsAppWebGroup $group,
        string $author,
        string $body,
        Carbon $sentAt,
        ?string $quotedMessageId = null
    ): WhatsAppWebMessage {
        return WhatsAppWebMessage::query()->create([
            'whatsapp_web_group_id' => $group->id,
            'whatsapp_message_id' => 'TEST-' . bin2hex(random_bytes(8)),
            'quoted_whatsapp_message_id' => $quotedMessageId,
            'author_whatsapp_id' => $author,
            'body' => $body,
            'message_type' => 'chat',
            'has_media' => false,
            'sent_at' => $sentAt,
        ]);
    }

    private function service(
        ?WhatsAppCloudService $cloud = null,
        ?WhatsAppSendGuard $guard = null
    ): C5iResponseTimeService {
        $cloud = $cloud ?: $this->createMock(WhatsAppCloudService::class);
        $guard = $guard ?: $this->createMock(WhatsAppSendGuard::class);
        $recommendation = new C5iSiniestrosRecommendationService($cloud, $guard);

        return new C5iResponseTimeService($recommendation, $cloud, $guard);
    }
}
