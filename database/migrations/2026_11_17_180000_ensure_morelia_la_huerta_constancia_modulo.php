<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('constancia_modulos')) {
            return;
        }

        $now = now();
        $attributes = [
            'nombre' => 'La Huerta',
            'tipo' => 'SINIESTROS',
            'municipio' => 'Morelia',
        ];
        $exists = DB::table('constancia_modulos')
            ->where($attributes)
            ->exists();

        DB::table('constancia_modulos')->updateOrInsert(
            $attributes,
            array_merge([
                'delegacion_id' => null,
                'unidad_id' => 1,
                'activo' => true,
                'updated_at' => $now,
            ], $exists ? [] : ['created_at' => $now])
        );
    }

    public function down(): void
    {
        // Se conserva el módulo para no invalidar usuarios o constancias asignadas.
    }
};
