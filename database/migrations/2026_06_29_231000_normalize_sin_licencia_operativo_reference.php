<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';
    private const CODIGO = 'OP_CL_SIN_LICENCIA_SIN_HABILITADO';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->where('codigo', self::CODIGO)
            ->update([
                'articulo' => '402',
                'fraccion' => null,
                'inciso' => null,
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
                'articulo' => '402; 700; 702',
                'fraccion' => null,
                'inciso' => null,
                'updated_at' => now(),
            ]);
    }
};
