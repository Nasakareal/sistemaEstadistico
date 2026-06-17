<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'ver puntos licencias',
        'crear puntos licencias',
        'editar puntos licencias',
        'registrar infracciones puntos licencias',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                array_merge(['updated_at' => $now], $exists ? [] : ['created_at' => $now])
            );
        }

        $rolePermissions = [
            'Superadmin' => $this->permissions,
            'Administrador' => [
                'ver puntos licencias',
            ],
            'Subdirector' => [
                'ver puntos licencias',
            ],
            'Administrativo' => [
                'ver puntos licencias',
            ],
            'Agente Vial' => [
                'ver puntos licencias',
            ],
            'Responsable de Turno' => [
                'ver puntos licencias',
            ],
            'Delegado' => [
                'ver puntos licencias',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );

            if (in_array($roleName, ['Agente Vial', 'Responsable de Turno'], true) && Schema::hasColumn('roles', 'unidad_id')) {
                DB::table('roles')
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->update(['unidad_id' => 5, 'updated_at' => $now]);
            }

            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (!$roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // No removemos permisos para no romper usuarios ya habilitados.
    }
};
