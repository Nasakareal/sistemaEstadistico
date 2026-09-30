<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AseguradorasPreviewTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_superadmin_can_open_insurer_preview(): void
    {
        $user = $this->userWithSettingsPermission();
        $user->assignRole(Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => 'web',
        ]));

        $this->actingAs($user)
            ->get(route('settings.aseguradoras_preview.index'))
            ->assertOk()
            ->assertSee('VIALYTICS')
            ->assertSee('Eficiencia de asistencia vial')
            ->assertSee('Auditoría de grúas')
            ->assertSee('Datos del sistema')
            ->assertSee('Costo promedio supuesto por servicio')
            ->assertDontSee('12,480')
            ->assertDontSee('$18.6 M')
            ->assertDontSee('$3.4 M');
    }

    public function test_non_superadmin_cannot_open_insurer_preview(): void
    {
        $user = $this->userWithSettingsPermission();

        $this->actingAs($user)
            ->get(route('settings.aseguradoras_preview.index'))
            ->assertForbidden();
    }

    public function test_settings_card_is_only_visible_to_superadmin(): void
    {
        $regular = $this->userWithSettingsPermission();

        $this->actingAs($regular)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('Inteligencia para Aseguradoras');

        $superadmin = $this->userWithSettingsPermission();
        $superadmin->assignRole(Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => 'web',
        ]));

        $this->actingAs($superadmin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Inteligencia para Aseguradoras');
    }

    private function userWithSettingsPermission(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'ver configuraciones',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['estado' => 'Activo']);
        $user->givePermissionTo($permission);

        return $user;
    }
}
