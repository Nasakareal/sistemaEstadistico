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

        $now = now();

        $tipos = [
            [
                'clave' => 'CUP',
                'nombre' => 'CUP',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
            [
                'clave' => 'CUIP',
                'nombre' => 'CUIP',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
            [
                'clave' => 'LICENCIA_CONDUCIR',
                'nombre' => 'Licencia de conducir',
                'requiere_vigencia' => true,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
            [
                'clave' => 'CREDENCIAL_INSTITUCIONAL',
                'nombre' => 'Credencial institucional',
                'requiere_vigencia' => true,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
            [
                'clave' => 'OFICIOS_PERSONAL',
                'nombre' => 'Oficios de personal',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
            [
                'clave' => 'OTRO_DOCUMENTO',
                'nombre' => 'Otro documento',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            $existe = DB::table('documento_tipos')
                ->where('clave', $tipo['clave'])
                ->exists();

            DB::table('documento_tipos')->updateOrInsert(
                ['clave' => $tipo['clave']],
                array_merge($tipo, [
                    'updated_at' => $now,
                ], $existe ? [] : [
                    'created_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('documento_tipos')) {
            return;
        }

        DB::table('documento_tipos')
            ->whereIn('clave', [
                'CUP',
                'CUIP',
                'LICENCIA_CONDUCIR',
                'CREDENCIAL_INSTITUCIONAL',
                'OTRO_DOCUMENTO',
            ])
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }
};
