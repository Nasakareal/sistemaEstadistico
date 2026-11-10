<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';
    private const CODIGO = 'ART440_FVI_MOTO_CARRILES_CENTRALES';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->where('codigo', self::CODIGO)
            ->update([
                'nombre' => 'Moto circula por carriles centrales o puentes',
                'deposito_si_sin_persona_habilitada' => true,
                'activa' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->where('codigo', self::CODIGO)
            ->update([
                'nombre' => 'Motocicleta circula por carriles centrales de acceso controlado',
                'deposito_si_sin_persona_habilitada' => false,
                'updated_at' => now(),
            ]);
    }
};
