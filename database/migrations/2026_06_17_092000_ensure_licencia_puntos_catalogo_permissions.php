<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'ver catalogo infracciones puntos licencias',
        'crear catalogo infracciones puntos licencias',
        'editar catalogo infracciones puntos licencias',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
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

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // No removemos permisos para no romper roles ya configurados.
    }
};
