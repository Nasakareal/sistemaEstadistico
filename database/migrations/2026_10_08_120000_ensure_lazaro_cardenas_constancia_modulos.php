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

        foreach (['Casa Cuna', 'Macro Modulo'] as $nombre) {
            $exists = DB::table('constancia_modulos')
                ->where('nombre', $nombre)
                ->where('tipo', 'SINIESTROS')
                ->where('municipio', 'Lázaro Cárdenas')
                ->exists();

            DB::table('constancia_modulos')->updateOrInsert(
                [
                    'nombre' => $nombre,
                    'tipo' => 'SINIESTROS',
                    'municipio' => 'Lázaro Cárdenas',
                ],
                array_merge([
                    'delegacion_id' => null,
                    'unidad_id' => 1,
                    'activo' => true,
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        // Se conservan los módulos para no invalidar usuarios o constancias asignadas.
    }
};
