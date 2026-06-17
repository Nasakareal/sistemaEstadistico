<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $mutationPermissions = [
        'crear puntos licencias',
        'editar puntos licencias',
        'registrar infracciones puntos licencias',
    ];

    public function up(): void
    {
        if (
            !Schema::hasTable('permissions')
            || !Schema::hasTable('roles')
            || !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->mutationPermissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $superadminRoleId = DB::table('roles')
            ->where('name', 'Superadmin')
            ->where('guard_name', 'web')
            ->value('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->when($superadminRoleId, function ($query) use ($superadminRoleId) {
                $query->where('role_id', '!=', $superadminRoleId);
            })
            ->delete();

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // No se restauran permisos de mutacion mientras el modulo siga en prueba.
    }
};
