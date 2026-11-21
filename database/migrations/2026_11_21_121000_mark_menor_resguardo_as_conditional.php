<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CODIGO = 'OP_CL_MENOR_CONDUCIENDO_SIN_HABILITADO';

    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        DB::table('licencia_punto_infracciones')
            ->where('codigo', self::CODIGO)
            ->update([
                'deposito_si_sin_persona_habilitada' => true,
                'retencion_vehiculo' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        DB::table('licencia_punto_infracciones')
            ->where('codigo', self::CODIGO)
            ->update([
                'deposito_si_sin_persona_habilitada' => false,
                'retencion_vehiculo' => true,
                'updated_at' => now(),
            ]);
    }
};
