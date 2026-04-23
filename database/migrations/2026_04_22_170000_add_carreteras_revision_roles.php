<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $carreterasPermissions = [
        'ver operativos carreteras',
        'crear operativos carreteras',
        'editar operativos carreteras',
        'ver estadisticas carreteras',
    ];

    public function up(): void
    {
        $now = now();

        foreach ([
            'ver operativos carreteras',
            'crear operativos carreteras',
            'editar operativos carreteras',
            'eliminar operativos carreteras',
            'ver estadisticas carreteras',
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach ([
            'Agente Upec' => ['ver operativos carreteras', 'crear operativos carreteras'],
            'RT' => ['ver operativos carreteras', 'editar operativos carreteras', 'ver estadisticas carreteras'],
            'Encargado de Destacamento' => ['ver operativos carreteras', 'editar operativos carreteras', 'ver estadisticas carreteras'],
        ] as $roleName => $permissions) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['unidad_id' => 4, 'updated_at' => $now, 'created_at' => $now]
            );

            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

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

        foreach ([
            'Subdirector' => ['ver operativos carreteras', 'ver estadisticas carreteras'],
            'Administrativo' => ['ver operativos carreteras', 'ver estadisticas carreteras'],
        ] as $roleName => $permissions) {
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
        // No quitamos permisos/roles para no romper usuarios ya asignados.
    }
};
