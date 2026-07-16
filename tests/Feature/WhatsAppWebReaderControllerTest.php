<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
