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
        config(['services.whatsapp.web_reader.secret' => 'reader-test-secret']);

        $this->postJson('http://localhost/api/whatsapp-web-reader/groups', [
            'groups' => [],
        ])->assertStatus(403);
    }

    public function test_sincroniza_grupos_y_guarda_mensajes_sin_enviar_respuestas(): void
    {
        config(['services.whatsapp.web_reader.secret' => 'reader-test-secret']);

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
}
