<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'ver puntos licencias',
        'acreditar capacitacion puntos licencias',
    ];

    private array $roles = [
        'Instructor',
        'Instructor Fomento',
        'Instructor de Fomento',
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

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        $fomentoUnidadId = $this->fomentoUnidadId();

        foreach ($this->roles as $roleName) {
            $role = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $payload = ['updated_at' => $now];

            if (Schema::hasColumn('roles', 'unidad_id') && $fomentoUnidadId) {
                $payload['unidad_id'] = $fomentoUnidadId;
            }

            if (!$role) {
                $payload['created_at'] = $now;
                $payload['name'] = $roleName;
                $payload['guard_name'] = 'web';
                $roleId = DB::table('roles')->insertGetId($payload);
            } else {
                DB::table('roles')->where('id', $role->id)->update($payload);
                $roleId = $role->id;
            }

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
        // No retiramos permisos ni roles para no romper usuarios ya habilitados.
    }

    private function fomentoUnidadId(): ?int
    {
        if (!Schema::hasTable('unidades')) {
            return null;
        }

        $id = DB::table('unidades')
            ->whereIn('slug', ['fomento-cultura-vial', 'cultura-vial', 'fomento'])
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        $id = DB::table('unidades')
            ->where('nombre', 'like', '%Fomento%')
            ->where('nombre', 'like', '%Cultura Vial%')
            ->value('id');

        return $id ? (int) $id : null;
    }
};
