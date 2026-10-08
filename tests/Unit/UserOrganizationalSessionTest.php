<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserOrganizationalSessionTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalConnection);
        parent::tearDown();
    }

    public function test_changing_delegation_revokes_mobile_tokens(): void
    {
        $user = User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'delegacion@example.test',
            'password' => 'secret',
            'unidad_id' => 2,
            'delegacion_id' => 10,
        ]);
        $user->createToken('movil');

        $this->assertSame(1, $user->tokens()->count());

        $user->update(['delegacion_id' => 20]);

        $this->assertSame(0, $user->tokens()->count());
    }
}
