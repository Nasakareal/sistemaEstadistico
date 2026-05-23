<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePermissions();
        $this->ensureDelegacionModulos();
    }

    public function down(): void
    {
        // No retiramos permisos ni modulos para no romper constancias ya generadas.
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $now = now();
        $permissions = [
            'ver modulo examenes',
            'crear modulo examenes',
            'editar modulo examenes',
            'eliminar modulo examenes',
        ];

        foreach ($permissions as $permission) {
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
            ->where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('id', 'name');

        $rolePermissions = [
            'Administrador' => $permissions,
            'Superadmin' => $permissions,
            'Subdirector' => $permissions,
            'Administrativo' => [
                'ver modulo examenes',
                'crear modulo examenes',
                'editar modulo examenes',
            ],
            'Delegado' => [
                'ver modulo examenes',
                'crear modulo examenes',
                'editar modulo examenes',
            ],
            'Perito' => [
                'ver modulo examenes',
                'crear modulo examenes',
                'editar modulo examenes',
            ],
            'Jefe de Grupo' => [
                'ver modulo examenes',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (!$roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;

                if (!$permissionId) {
                    continue;
                }

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

    private function ensureDelegacionModulos(): void
    {
        if (!Schema::hasTable('delegaciones') || !Schema::hasTable('constancia_modulos')) {
            return;
        }

        $now = now();
        $delegaciones = DB::table('delegaciones')
            ->select('id', 'nombre', 'municipio')
            ->when(Schema::hasColumn('delegaciones', 'activa'), function ($query) {
                $query->where('activa', true);
            })
            ->orderBy('nombre')
            ->get();

        foreach ($delegaciones as $delegacion) {
            $exists = DB::table('constancia_modulos')
                ->where('tipo', 'DELEGACION')
                ->where('delegacion_id', $delegacion->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('constancia_modulos')->insert([
                'nombre' => $delegacion->nombre,
                'tipo' => 'DELEGACION',
                'municipio' => $delegacion->municipio ?: $delegacion->nombre,
                'delegacion_id' => $delegacion->id,
                'unidad_id' => 2,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
