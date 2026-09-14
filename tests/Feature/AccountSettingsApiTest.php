<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountSettingsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');

        config()->set('database.default', 'account_settings_testing');
        config()->set('database.connections.account_settings_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('account_settings_testing');
        DB::setDefaultConnection('account_settings_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('receive_waze_alerts')->default(true);
            $table->timestamps();
        });
    }

    public function test_user_can_disable_only_their_own_waze_alerts(): void
    {
        $user = $this->user('uno@example.test');
        $other = $this->user('otro@example.test');
        Sanctum::actingAs($user);

        $this->getJson(route('api.account.settings.show'))
            ->assertOk()
            ->assertJsonPath('data.receive_waze_alerts', true);

        $this->putJson(route('api.account.settings.update'), [
            'receive_waze_alerts' => false,
        ])->assertOk()
            ->assertJsonPath('data.receive_waze_alerts', false);

        $this->assertFalse((bool) $user->fresh()->receive_waze_alerts);
        $this->assertTrue((bool) $other->fresh()->receive_waze_alerts);
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
