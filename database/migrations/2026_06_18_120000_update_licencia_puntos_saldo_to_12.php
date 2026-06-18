<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_cuentas') || !Schema::hasColumn('licencia_punto_cuentas', 'saldo_actual')) {
            return;
        }

        DB::statement('ALTER TABLE licencia_punto_cuentas MODIFY saldo_actual TINYINT UNSIGNED NOT NULL DEFAULT 12');

        DB::table('licencia_punto_cuentas')->update([
            'saldo_actual' => DB::raw('LEAST(saldo_actual + 4, 12)'),
            'updated_at' => now(),
        ]);

        DB::table('licencia_punto_cuentas')
            ->where('estado', 'procedimiento_administrativo')
            ->where('saldo_actual', '>', 0)
            ->update([
                'estado' => 'vigente',
                'fecha_agotamiento' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_cuentas') || !Schema::hasColumn('licencia_punto_cuentas', 'saldo_actual')) {
            return;
        }

        DB::statement('ALTER TABLE licencia_punto_cuentas MODIFY saldo_actual TINYINT UNSIGNED NOT NULL DEFAULT 8');
    }
};
