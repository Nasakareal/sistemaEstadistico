<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'ver operativos vialidades',
        'crear operativos vialidades',
        'editar operativos vialidades',
        'eliminar operativos vialidades',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach ([
            'Agente Vial' => [
                'ver operativos vialidades',
                'crear operativos vialidades',
            ],
            'Responsable de Turno' => [
                'ver operativos vialidades',
                'editar operativos vialidades',
            ],
            'Subdirector' => [
                'ver operativos vialidades',
            ],
            'Administrativo' => [
                'ver operativos vialidades',
            ],
            'Administrador' => $this->permissions,
        ] as $roleName => $permissions) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );

            if (in_array($roleName, ['Agente Vial', 'Responsable de Turno'], true)) {
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
    }

    public function down(): void
    {
        // No removemos permisos para no romper usuarios ya habilitados.
    }
};
