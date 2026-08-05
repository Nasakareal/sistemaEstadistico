<?php

namespace Tests\Unit;

use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\WhatsAppWebGroup;
use App\Models\WhatsAppWebMessage;
use App\Services\C5iSiniestrosRecommendationService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class C5iSiniestrosRecommendationServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_parsea_el_formato_c5i_con_coordenadas(): void
    {
        $service = $this->service();
        $incident = $service->parseIncident(
            'AVENIDA FRANCISCO I. MADERO P #S/N LOCALIDAD: MORELIA '
            . 'LATITUD:19.696922181965796 LONGITUD:-101.25839301230336'
        );

        $this->assertNotNull($incident);
        $this->assertEqualsWithDelta(19.696922181965796, $incident['lat'], 0.0000001);
        $this->assertEqualsWithDelta(-101.25839301230336, $incident['lng'], 0.0000001);
        $this->assertStringContainsString('AVENIDA FRANCISCO', $incident['location']);
    }

    public function test_solo_recomienda_patrullas_activas_de_siniestros(): void
    {
        config([
            'services.whatsapp.c5i_recommendation.enabled' => true,
            'services.whatsapp.c5i_recommendation.dry_run' => true,
            'services.whatsapp.c5i_recommendation.group_ids' => '120363424100430316@g.us',
            'services.whatsapp.c5i_recommendation.source_author_ids' => '5214437916890@c.us',
            'services.whatsapp.c5i_recommendation.to' => '5214438000001,5214438000002',
            'services.whatsapp.c5i_recommendation.location_max_age_minutes' => 10,
            'services.whatsapp.c5i_recommendation.max_accuracy_meters' => 200,
        ]);

        [$siniestrosPatrulla, $otherPatrulla] = $this->patrullasConUbicacion();
        $message = $this->message();

        $result = $this->service()->process($message);

        $this->assertSame('dry_run', $result['status']);
        $this->assertSame($siniestrosPatrulla->id, $result['candidate']['patrulla_id']);
        $this->assertNotSame($otherPatrulla->id, $result['candidate']['patrulla_id']);

        $message->refresh();
        $this->assertSame('dry_run', $message->recommendation_status);
        $this->assertSame($siniestrosPatrulla->id, $message->recommended_patrulla_id);
    }

    public function test_solicita_dos_plantillas_oficiales_solo_fuera_de_simulacion(): void
    {
        config([
            'services.whatsapp.c5i_recommendation.enabled' => true,
            'services.whatsapp.c5i_recommendation.dry_run' => false,
            'services.whatsapp.c5i_recommendation.group_ids' => '120363424100430316@g.us',
            'services.whatsapp.c5i_recommendation.source_author_ids' => '5214437916890@c.us',
            'services.whatsapp.c5i_recommendation.to' => '5214438000001,5214438000002',
            'services.whatsapp.c5i_recommendation.template' => 'recomendacion_unidad_siniestros_c5i_v1',
            'services.whatsapp.c5i_recommendation.template_language' => 'es_MX',
            'services.whatsapp.c5i_recommendation.location_max_age_minutes' => 10,
            'services.whatsapp.c5i_recommendation.max_accuracy_meters' => 200,
        ]);

        [$siniestrosPatrulla] = $this->patrullasConUbicacion();
        $message = $this->message();

        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->expects($this->exactly(2))
            ->method('sendTemplate')
            ->with(
                $this->callback(fn ($to) => in_array($to, ['5214438000001', '5214438000002'], true)),
                'recomendacion_unidad_siniestros_c5i_v1',
                $this->callback(fn ($params) => count($params) === 7 && $params[2] === $siniestrosPatrulla->numero_economico),
                'es_MX'
            )
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'body' => ['messages' => [['id' => 'wamid.TEST']]],
            ]);

        $guard = $this->createMock(WhatsAppSendGuard::class);
        $guard->expects($this->exactly(2))->method('reserve')->willReturn(true);
        $guard->expects($this->exactly(2))->method('markSent');
        $guard->expects($this->never())->method('release');

        $service = new C5iSiniestrosRecommendationService($cloud, $guard);
        $result = $service->process($message);

        $this->assertSame('sent', $result['status']);
        $this->assertSame('sent', $message->fresh()->recommendation_status);
    }

    public function test_ignora_solo_reportes_llega_con_clave_l4(): void
    {
        config([
            'services.whatsapp.c5i_recommendation.enabled' => true,
            'services.whatsapp.c5i_recommendation.dry_run' => false,
            'services.whatsapp.c5i_recommendation.group_ids' => '120363424100430316@g.us',
            'services.whatsapp.c5i_recommendation.source_author_ids' => '5214437916890@c.us',
            'services.whatsapp.c5i_recommendation.to' => '5214438000001',
            'services.whatsapp.c5i_recommendation.template' => 'recomendacion_unidad_siniestros_c5i_v1',
        ]);

        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->expects($this->never())->method('sendTemplate');
        $guard = $this->createMock(WhatsAppSendGuard::class);
        $guard->expects($this->never())->method('reserve');
        $service = new C5iSiniestrosRecommendationService($cloud, $guard);

        foreach (['L4', 'L4 L4'] as $code) {
            $message = $this->message(
                "📍Ubicación: {$code} LLEGA 13 DE PORTACION DE ARMAS "
                . 'LATITUD:19.696922181965796 LONGITUD:-101.25839301230336'
            );

            $result = $service->process($message);

            $this->assertSame('ignored', $result['status']);
            $this->assertSame('arrival_code_not_relevant', $result['reason']);
            $this->assertSame('ignored', $message->fresh()->recommendation_status);
            $this->assertSame(
                'arrival_code_not_relevant',
                $message->fresh()->recommendation_meta['reason']
            );
        }

        $this->assertFalse($service->isExcludedIncident(
            '📍Ubicación: R4 R4 LLEGA 13 DE PORTACION DE ARMAS '
            . 'LATITUD:19.696922181965796 LONGITUD:-101.25839301230336'
        ));
    }

    private function patrullasConUbicacion(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $siniestros = Unidad::query()->firstOrCreate(
            ['slug' => 'siniestros'],
            ['nombre' => 'SINIESTROS', 'activa' => true]
        );
        $other = Unidad::query()->create([
            'nombre' => 'OTRA UNIDAD ' . $suffix,
            'slug' => 'otra-unidad-' . $suffix,
            'activa' => true,
        ]);

        $siniestrosPatrulla = Patrulla::query()->create([
            'numero_economico' => 'SIN-' . $suffix,
            'unidad_id' => $siniestros->id,
            'activa' => true,
        ]);
        $otherPatrulla = Patrulla::query()->create([
            'numero_economico' => 'OTR-' . $suffix,
            'unidad_id' => $other->id,
            'activa' => true,
        ]);

        $siniestrosUser = User::factory()->create([
            'unidad_id' => $siniestros->id,
            'patrulla_id' => $siniestrosPatrulla->id,
            'compartir_ubicacion' => true,
        ]);
        $otherUser = User::factory()->create([
            'unidad_id' => $other->id,
            'patrulla_id' => $otherPatrulla->id,
            'compartir_ubicacion' => true,
        ]);

        UserLocation::query()->create([
            'user_id' => $siniestrosUser->id,
            'lat' => 19.7000000,
            'lng' => -101.2500000,
            'accuracy' => 15,
            'captured_at' => now(),
        ]);
        UserLocation::query()->create([
            'user_id' => $otherUser->id,
            'lat' => 19.6969222,
            'lng' => -101.2583930,
            'accuracy' => 5,
            'captured_at' => now(),
        ]);

        return [$siniestrosPatrulla, $otherPatrulla];
    }

    private function message(?string $body = null): WhatsAppWebMessage
    {
        $group = WhatsAppWebGroup::query()->firstOrCreate(
            ['whatsapp_id' => '120363424100430316@g.us'],
            [
                'name' => 'SINIESTROS GC',
                'participant_count' => 57,
                'last_seen_at' => now(),
            ]
        );

        return WhatsAppWebMessage::query()->create([
            'whatsapp_web_group_id' => $group->id,
            'whatsapp_message_id' => 'C5I-' . bin2hex(random_bytes(8)),
            'author_whatsapp_id' => '5214437916890@c.us',
            'body' => $body ?: 'AVENIDA FRANCISCO I. MADERO P #S/N POR CALLE PUERTO COATZACOALCOS '
                . 'LOCALIDAD: MORELIA COL.TINÍJARO MUNICIPIO:MORELIA KILOMETRO: SIN INFORMACIÓN '
                . 'LATITUD:19.696922181965796 LONGITUD:-101.25839301230336',
            'message_type' => 'chat',
            'has_media' => false,
            'sent_at' => now(),
        ]);
    }

    private function service(): C5iSiniestrosRecommendationService
    {
        return new C5iSiniestrosRecommendationService(
            $this->createMock(WhatsAppCloudService::class),
            $this->createMock(WhatsAppSendGuard::class)
        );
    }
}
