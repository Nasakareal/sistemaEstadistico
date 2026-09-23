<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'unidad_id')) {
            return;
        }

        $updates = [];
        if (Schema::hasColumn('users', 'compartir_ubicacion')) {
            $updates['compartir_ubicacion'] = false;
        }
        if (Schema::hasColumn('users', 'receive_waze_alerts')) {
            $updates['receive_waze_alerts'] = false;
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table('users')->where('unidad_id', 5)->update($updates);
        }
    }

    public function down(): void
    {
        // No se reactivan preferencias de privacidad automáticamente.
    }
};
