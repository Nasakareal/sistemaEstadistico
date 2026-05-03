<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = now();

        foreach ([
            'ver estadisticas globales',
            'ver estadisticas actividades',
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        $globalesPermissionId = DB::table('permissions')
            ->where('name', 'ver estadisticas globales')
            ->where('guard_name', 'web')
            ->value('id');

        $actividadesPermissionId = DB::table('permissions')
            ->where('name', 'ver estadisticas actividades')
            ->where('guard_name', 'web')
            ->value('id');

        if ($actividadesPermissionId) {
            $roleIds = collect();

            if ($globalesPermissionId) {
                $roleIds = DB::table('role_has_permissions')
                    ->where('permission_id', $globalesPermissionId)
                    ->pluck('role_id');
            }

            if ($roleIds->isEmpty()) {
                $roleIds = DB::table('roles')
                    ->where('guard_name', 'web')
                    ->whereIn('name', [
                        'Superadmin',
                        'Administrador',
                        'Subdirector',
                        'Administrativo',
                        'Jefe de Grupo',
                        'Coordinador',
                        'Observador',
                        'Policía',
                    ])
                    ->pluck('id');
            }

            foreach ($roleIds->unique() as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $actividadesPermissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No retiramos permisos para no romper usuarios ya habilitados.
    }
};
