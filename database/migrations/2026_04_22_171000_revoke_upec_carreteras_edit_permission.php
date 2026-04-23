<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'Agente Upec')
            ->where('guard_name', 'web')
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('name', 'editar operativos carreteras')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }

    public function down(): void
    {
        // No se restaura: Agente UPEC captura, pero no revisa/edita.
    }
};
