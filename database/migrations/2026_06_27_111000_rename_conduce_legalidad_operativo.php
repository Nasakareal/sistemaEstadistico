<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_NAME = 'Operativo Conduce con legalidad';
    private const NEW_NAME = 'Operativo conduce con legalidad';

    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        DB::table('conduce_legalidad_operativos')
            ->where('nombre', self::OLD_NAME)
            ->update([
                'nombre' => self::NEW_NAME,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        DB::table('conduce_legalidad_operativos')
            ->where('nombre', self::NEW_NAME)
            ->update([
                'nombre' => self::OLD_NAME,
                'updated_at' => now(),
            ]);
    }
};
