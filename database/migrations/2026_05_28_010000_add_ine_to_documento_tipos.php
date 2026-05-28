<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('documento_tipos')) {
            return;
        }

        $existe = DB::table('documento_tipos')
            ->where('clave', 'INE')
            ->exists();

        DB::table('documento_tipos')->updateOrInsert(
            ['clave' => 'INE'],
            array_merge([
                'nombre' => 'INE / Identificacion oficial',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
                'updated_at' => now(),
            ], $existe ? [] : [
                'created_at' => now(),
            ])
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('documento_tipos')) {
            return;
        }

        DB::table('documento_tipos')
            ->where('clave', 'INE')
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }
};
