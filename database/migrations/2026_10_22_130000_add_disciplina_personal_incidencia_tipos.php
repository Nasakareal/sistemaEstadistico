<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        foreach ([
            ['clave' => 'AMONESTACION', 'nombre' => 'AMONESTACIÓN'],
            ['clave' => 'ARRESTO', 'nombre' => 'ARRESTO'],
        ] as $tipo) {
            DB::table('incidencia_tipos')->updateOrInsert(
                ['clave' => $tipo['clave']],
                [
                    'nombre' => $tipo['nombre'],
                    'categoria' => 'DISCIPLINA',
                    'descuenta' => false,
                    'requiere_documento' => false,
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('incidencia_tipos')
            ->whereIn('clave', ['AMONESTACION', 'ARRESTO'])
            ->update(['activo' => false, 'updated_at' => now()]);
    }
};
