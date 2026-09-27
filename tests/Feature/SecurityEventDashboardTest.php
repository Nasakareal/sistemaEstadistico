<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityEventDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_superadmin_can_open_security_dashboard(): void
    {
        $user = $this->userWithSettingsPermission();
        $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $user->assignRole($role);

        SecurityEvent::create([
            'occurred_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'bucket_at' => now()->startOfMinute(),
            'occurrences' => 3,
            'severity' => 'high',
            'category' => 'authentication',
            'event_code' => 'login_failed',
            'description' => 'Prueba del panel',
            'ip_address' => '192.0.2.80',
            'method' => 'POST',
            'path' => '/login',
            'status_code' => 401,
            'fingerprint' => hash('sha256', 'dashboard-test'),
        ]);

        $this->actingAs($user)
            ->get('/admin/settings/security-events')
            ->assertOk()
            ->assertSee('Eventos de Seguridad')
            ->assertSee('192.0.2.80')
            ->assertSee('login_failed')
            ->assertSee('Intención probable');
    }

    public function test_non_superadmin_cannot_open_security_dashboard(): void
    {
        $user = $this->userWithSettingsPermission();

        $this->actingAs($user)
            ->get('/admin/settings/security-events')
            ->assertForbidden();
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
