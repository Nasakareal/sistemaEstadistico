<?php

namespace Tests\Feature;

use App\Services\C5iAudioTranscriptionService;
use App\Services\C5iResponseTimeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class WhatsAppWebReaderControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // APP_URL contiene la subcarpeta usada por Wamp; las pruebas HTTP
        // necesitan una raíz virtual para que Laravel resuelva /api/*.
        config(['app.url' => 'http://localhost']);
    }

    public function test_rechaza_solicitudes_sin_secreto(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000000@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214430000000@c.us',
        ]);

        $this->postJson('http://localhost/api/whatsapp-web-reader/groups', [
            'groups' => [],
        ])->assertStatus(403);
    }

    public function test_sincroniza_grupos_y_guarda_mensajes_sin_enviar_respuestas(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000000@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214430000000@c.us',
        ]);

        $headers = ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'];

        $this->postJson('http://localhost/api/whatsapp-web-reader/groups', [
            'groups' => [
                [
                    'id' => '120363000000000000@g.us',
                    'name' => 'Grupo de prueba',
                    'participant_count' => 12,
                ],
            ],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('stored', 1);

        $this->postJson('http://localhost/api/whatsapp-web-reader/messages', [
            'group' => [
                'id' => '120363000000000000@g.us',
                'name' => 'Grupo de prueba',
            ],
            'message' => [
                'id' => 'false_120363000000000000@g.us_TEST',
                'author_id' => '5214430000000@c.us',
                'body' => 'Mensaje de prueba',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178000,
            ],
        ], $headers)->assertOk();

        $this->assertDatabaseHas('whatsapp_web_groups', [
            'whatsapp_id' => '120363000000000000@g.us',
            'name' => 'Grupo de prueba',
        ]);

        $this->assertDatabaseHas('whatsapp_web_messages', [
            'whatsapp_message_id' => 'false_120363000000000000@g.us_TEST',
            'body' => 'Mensaje de prueba',
            'has_media' => false,
        ]);
        $this->assertSame(
            1784178000,
            \App\Models\WhatsAppWebMessage::query()
                ->where('whatsapp_message_id', 'false_120363000000000000@g.us_TEST')
                ->firstOrFail()
                ->sent_at
                ->timestamp
        );
    }

    public function test_no_procesa_recomendaciones_aunque_la_configuracion_anterior_siga_activa(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000004@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214437938996@c.us',
            'services.whatsapp.c5i_recommendation.enabled' => true,
            'services.whatsapp.c5i_recommendation.group_ids' => '120363000000000004@g.us',
            'services.whatsapp.c5i_recommendation.source_author_ids' => '5214437938996@c.us',
        ]);

        $this->postJson('http://localhost/api/whatsapp-web-reader/messages', [
            'group' => [
                'id' => '120363000000000004@g.us',
                'name' => 'Grupo con diagnóstico',
            ],
            'message' => [
                'id' => 'MENSAJE_SIN_COORDENADAS',
                'author_id' => '5214437938996@c.us',
                'body' => 'R10 COMANDO',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178004,
            ],
        ], ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'])
            ->assertOk()
            ->assertJsonPath('recommendation_status', null)
            ->assertJsonPath('recommendation_reason', null);

        $this->assertDatabaseHas('whatsapp_web_messages', [
            'whatsapp_message_id' => 'MENSAJE_SIN_COORDENADAS',
            'recommendation_status' => null,
            'recommendation_processed_at' => null,
        ]);
    }

    public function test_genera_id_estable_si_whatsapp_web_no_entrega_message_id(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000001@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214437916890@c.us,5214437938996@c.us',
            'services.whatsapp.c5i_recommendation.enabled' => false,
        ]);

        $headers = ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'];
        $payload = [
            'group' => [
                'id' => '120363000000000001@g.us',
                'name' => 'Grupo sin ID de mensaje',
            ],
            'message' => [
                'author_id' => '5214437916890@c.us',
                'body' => 'Reporte sin identificador interno',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178001,
            ],
        ];

        $first = $this->postJson(
            'http://localhost/api/whatsapp-web-reader/messages',
            $payload,
            $headers
        )->assertOk();
        $second = $this->postJson(
            'http://localhost/api/whatsapp-web-reader/messages',
            $payload,
            $headers
        )->assertOk();

        $this->assertSame($first->json('message_id'), $second->json('message_id'));
        $this->assertSame(1, \App\Models\WhatsAppWebMessage::query()
            ->where('body', 'Reporte sin identificador interno')
            ->count());
        $this->assertDatabaseHas('whatsapp_web_messages', [
            'body' => 'Reporte sin identificador interno',
            'whatsapp_message_id' => 'fallback_' . hash('sha256', json_encode([
                'group_id' => '120363000000000001@g.us',
                'author_id' => '5214437916890@c.us',
                'body' => 'Reporte sin identificador interno',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178001,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ]);
    }

    public function test_ignora_mensajes_de_remitentes_no_autorizados(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000002@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214437916890@c.us,5214437938996@c.us',
        ]);

        $this->postJson('http://localhost/api/whatsapp-web-reader/messages', [
            'group' => [
                'id' => '120363000000000002@g.us',
                'name' => 'Grupo filtrado',
            ],
            'message' => [
                'id' => 'MENSAJE_NO_AUTORIZADO',
                'author_id' => '5214431111111@c.us',
                'body' => 'Este mensaje no debe almacenarse',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178002,
            ],
        ], ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'])
            ->assertStatus(202)
            ->assertJson([
                'ok' => true,
                'stored' => false,
                'reason' => 'author_not_allowed',
            ]);

        $this->assertDatabaseMissing('whatsapp_web_messages', [
            'whatsapp_message_id' => 'MENSAJE_NO_AUTORIZADO',
        ]);
    }

    public function test_acepta_arribo_operativo_sin_exigir_clave_86_ni_autorizacion_manual(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000003@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214437916890@c.us',
            'services.whatsapp.web_reader.allow_operational_authors' => true,
            'services.whatsapp.c5i_recommendation.enabled' => false,
            'services.whatsapp.c5i_response_time.enabled' => false,
        ]);

        $this->postJson('http://localhost/api/whatsapp-web-reader/messages', [
            'group' => [
                'id' => '120363000000000003@g.us',
                'name' => 'Grupo operativo',
            ],
            'message' => [
                'id' => 'ARRIBO_SIN_86',
                'quoted_message_id' => 'REPORTE_C5I_CITADO',
                'author_id' => '5214432222222@c.us',
                'body' => 'ya en el K6 indicado',
                'type' => 'chat',
                'has_media' => false,
                'timestamp' => 1784178003,
            ],
        ], ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'])
            ->assertOk()
            ->assertJsonPath('response_time_status', 'disabled');

        $this->assertDatabaseHas('whatsapp_web_messages', [
            'whatsapp_message_id' => 'ARRIBO_SIN_86',
            'quoted_whatsapp_message_id' => 'REPORTE_C5I_CITADO',
            'body' => 'ya en el K6 indicado',
        ]);
    }

    public function test_acepta_y_transcribe_audio_operativo_de_cualquier_companero(): void
    {
        config([
            'services.whatsapp.web_reader.secret' => 'reader-test-secret',
            'services.whatsapp.web_reader.allowed_group_ids' => '120363000000000005@g.us',
            'services.whatsapp.web_reader.allowed_author_ids' => '5214437916890@c.us',
            'services.whatsapp.web_reader.allow_operational_authors' => true,
            'services.whatsapp.c5i_recommendation.enabled' => false,
            'services.whatsapp.c5i_response_time.audio_max_bytes' => 5242880,
        ]);

        $responseTime = Mockery::mock(C5iResponseTimeService::class);
        $responseTime->shouldReceive('isOperationalMessageCandidate')->andReturn(false);
        $responseTime->shouldReceive('isOperationalAudioCandidate')->andReturn(true);
        $responseTime->shouldReceive('shouldTranscribeAudio')->once()->andReturn(true);
        $responseTime->shouldReceive('processMessage')->once()->andReturn(['status' => 'complete']);
        $this->app->instance(C5iResponseTimeService::class, $responseTime);

        $transcription = Mockery::mock(C5iAudioTranscriptionService::class);
        $transcription->shouldReceive('transcribe')
            ->once()
            ->andReturn([
                'status' => 'transcribed',
                'text' => 'Unidad 22-1110, 86 sobre el K6; se checa 13.',
                'model' => 'gpt-4o-mini-transcribe',
            ]);
        $this->app->instance(C5iAudioTranscriptionService::class, $transcription);

        $this->postJson('http://localhost/api/whatsapp-web-reader/messages', [
            'group' => [
                'id' => '120363000000000005@g.us',
                'name' => 'Grupo operativo con audios',
            ],
            'message' => [
                'id' => 'AUDIO_86_COMPANERO',
                'author_id' => '5214439999999@c.us',
                'body' => null,
                'type' => 'ptt',
                'has_media' => true,
                'media_base64' => base64_encode('audio de prueba'),
                'media_mimetype' => 'audio/ogg; codecs=opus',
                'timestamp' => 1784590860,
            ],
        ], ['X-WhatsApp-Reader-Secret' => 'reader-test-secret'])
            ->assertOk()
            ->assertJsonPath('response_time_status', 'complete')
            ->assertJsonPath('transcription_status', 'transcribed');

        $this->assertDatabaseHas('whatsapp_web_messages', [
            'whatsapp_message_id' => 'AUDIO_86_COMPANERO',
            'author_whatsapp_id' => '5214439999999@c.us',
            'message_type' => 'ptt',
            'transcription_status' => 'transcribed',
            'transcription_text' => 'Unidad 22-1110, 86 sobre el K6; se checa 13.',
        ]);
    }
}
