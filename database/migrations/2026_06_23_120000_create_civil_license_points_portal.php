<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permission = 'ver portal ciudadano puntos licencias';
    private string $role = 'Ciudadano';

    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_cuenta_user')) {
            Schema::create('licencia_punto_cuenta_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('cuenta_id')->constrained('licencia_punto_cuentas')->cascadeOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('last_accessed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'cuenta_id'], 'lp_cuenta_user_unique');
                $table->index(['cuenta_id', 'user_id'], 'lp_cuenta_user_cuenta_idx');
            });
        }

        $this->ensureCitizenRole();
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_punto_cuenta_user');
    }

    private function ensureCitizenRole(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission, 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        $rolePayload = [
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('roles', 'unidad_id')) {
            $rolePayload['unidad_id'] = null;
        }

        DB::table('roles')->updateOrInsert(
            ['name' => $this->role, 'guard_name' => 'web'],
            $rolePayload
        );

        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        $roleId = DB::table('roles')
            ->where('name', $this->role)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId && $roleId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
