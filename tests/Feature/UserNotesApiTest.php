<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNote;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserNotesApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');

        config()->set('database.default', 'notes_testing');
        config()->set('database.connections.notes_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('notes_testing');
        DB::setDefaultConnection('notes_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title', 160)->nullable();
            $table->longText('content')->nullable();
            $table->string('color', 24)->default('neutral');
            $table->json('highlights')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_user_can_store_and_recover_a_colored_note_with_highlights(): void
    {
        $user = $this->user('uno@example.test');
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.notes.store'), [
            'title' => 'Pendientes',
            'content' => 'Revisar operativo',
            'color' => 'blue',
            'is_pinned' => true,
            'highlights' => [
                ['start' => 0, 'end' => 7, 'color' => 'yellow'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Pendientes')
            ->assertJsonPath('data.color', 'blue')
            ->assertJsonPath('data.is_pinned', true)
            ->assertJsonPath('data.highlights.0.end', 7);

        $this->getJson(route('api.notes.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Revisar operativo');
    }

    public function test_notes_are_private_to_the_authenticated_user(): void
    {
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        $note = UserNote::query()->create([
            'user_id' => $owner->id,
            'title' => 'Privada',
            'content' => 'Solo del propietario',
            'color' => 'neutral',
            'highlights' => [],
            'is_pinned' => false,
        ]);

        Sanctum::actingAs($other);

        $this->getJson(route('api.notes.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->putJson(route('api.notes.update', ['note' => $note->id]), [
            'title' => 'Intento',
            'content' => 'No permitido',
            'color' => 'neutral',
            'is_pinned' => false,
            'highlights' => [],
        ])->assertNotFound();
    }

    public function test_highlight_offsets_preserve_flutter_utf16_units_with_emoji(): void
    {
        $user = $this->user('emoji@example.test');
        Sanctum::actingAs($user);

        $this->postJson(route('api.notes.store'), [
            'title' => 'Emoji',
            'content' => '🚓ABC',
            'color' => 'yellow',
            'is_pinned' => false,
            'highlights' => [
                ['start' => 2, 'end' => 5, 'color' => 'green'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.highlights.0.start', 2)
            ->assertJsonPath('data.highlights.0.end', 5);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'Usuario Prueba',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
