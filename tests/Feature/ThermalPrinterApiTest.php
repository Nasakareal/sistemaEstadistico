<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ThermalPrinter;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ThermalPrinterApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');

        config()->set('database.default', 'thermal_printer_testing');
        config()->set('database.connections.thermal_printer_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('thermal_printer_testing');
        DB::setDefaultConnection('thermal_printer_testing');

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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('thermal_printers', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 80)->unique();
            $table->string('mac_address', 17)->unique();
            $table->string('hardware_name', 80)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_regular_user_can_only_list_active_printers(): void
    {
        ThermalPrinter::query()->create([
            'display_name' => 'UAS-001',
            'mac_address' => '86:67:7A:62:D8:C3',
            'hardware_name' => 'BT-802',
            'active' => true,
        ]);
        ThermalPrinter::query()->create([
            'display_name' => 'UAS-002',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'hardware_name' => 'BT-802',
            'active' => false,
        ]);

        Sanctum::actingAs($this->user('usuario@example.test'));

        $this->getJson('/api/thermal-printers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.display_name', 'UAS-001');

        $this->postJson('/api/thermal-printers', [
            'display_name' => 'UAS-003',
            'mac_address' => '11:22:33:44:55:66',
            'hardware_name' => 'BT-802',
            'active' => true,
        ])->assertForbidden();

        $this->assertDatabaseMissing('thermal_printers', [
            'display_name' => 'UAS-003',
        ]);
    }

    public function test_superadmin_can_create_rename_and_delete_printers(): void
    {
        $superadmin = $this->user('superadmin@example.test');
        $role = Role::query()->create([
            'name' => 'Superadmin',
            'guard_name' => 'web',
        ]);
        $superadmin->assignRole($role);
        Sanctum::actingAs($superadmin);

        $created = $this->postJson('/api/thermal-printers', [
            'display_name' => 'UAS-001',
            'mac_address' => '86-67-7a-62-d8-c3',
            'hardware_name' => 'BT-802',
            'active' => true,
        ])->assertCreated()
            ->assertJsonPath('data.display_name', 'UAS-001')
            ->assertJsonPath('data.mac_address', '86:67:7A:62:D8:C3');

        $printerId = $created->json('data.id');

        $this->putJson("/api/thermal-printers/{$printerId}", [
            'display_name' => 'LAZARO-001',
            'mac_address' => '86:67:7A:62:D8:C3',
            'hardware_name' => 'BT-802',
            'active' => true,
        ])->assertOk()
            ->assertJsonPath('data.display_name', 'LAZARO-001');

        $this->deleteJson("/api/thermal-printers/{$printerId}")
            ->assertOk();

        $this->assertDatabaseMissing('thermal_printers', ['id' => $printerId]);
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
