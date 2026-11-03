<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicCitizenRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');

        config()->set('database.default', 'citizen_registration_testing');
        config()->set('database.connections.citizen_registration_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('citizen_registration_testing');
        DB::setDefaultConnection('citizen_registration_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('estado', 11)->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->boolean('compartir_ubicacion')->default(false);
            $table->boolean('receive_waze_alerts')->default(false);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }

    public function test_public_registration_forces_restricted_citizen_role(): void
    {
        $response = $this->postJson(route('api.register'), [
            'name' => '  Persona   Ciudadana  ',
            'email' => 'PERSONA@EXAMPLE.TEST',
            'password' => 'Clave-Segura-2026',
            'password_confirmation' => 'Clave-Segura-2026',
            'account_type' => 'ciudadano',
            'role_id' => 1,
            'unidad_id' => 1,
            'permissions' => ['ver hechos'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'persona@example.test')
            ->assertJsonPath('user.role.name', 'Ciudadano');

        $user = User::query()->where('email', 'persona@example.test')->firstOrFail();

        $this->assertSame('Persona Ciudadana', $user->name);
        $this->assertNull($user->unidad_id);
        $this->assertFalse((bool) $user->compartir_ubicacion);
        $this->assertFalse((bool) $user->receive_waze_alerts);
        $this->assertTrue(Hash::check('Clave-Segura-2026', $user->password));
        $this->assertSame(['Ciudadano'], $user->roles()->pluck('name')->all());
    }

    public function test_registration_rejects_duplicate_email_and_foreign_account_type(): void
    {
        $payload = [
            'name' => 'Persona Ciudadana',
            'email' => 'persona@example.test',
            'password' => 'Clave-Segura-2026',
            'password_confirmation' => 'Clave-Segura-2026',
            'account_type' => 'ciudadano',
        ];

        $this->postJson(route('api.register'), $payload)->assertCreated();
        $this->postJson(route('api.register'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $payload['email'] = 'otra@example.test';
        $payload['account_type'] = 'administrador';
        $this->postJson(route('api.register'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('account_type');
    }
}
