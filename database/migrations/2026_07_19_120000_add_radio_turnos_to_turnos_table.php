<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('turnos')) {
            return;
        }

        $ahora = now();

        foreach ([
            ['base' => 'a', 'nombre' => 'Radio-A', 'slug' => 'radio-a', 'inicio' => '2026-02-23 07:00:00'],
            ['base' => 'b', 'nombre' => 'Radio-B', 'slug' => 'radio-b', 'inicio' => '2026-02-24 07:00:00'],
        ] as $radio) {
            $turnoBase = DB::table('turnos')->where('slug', $radio['base'])->first();

            DB::table('turnos')->updateOrInsert(
                ['slug' => $radio['slug']],
                [
                    'nombre' => $radio['nombre'],
                    'tipo_rol' => 'RADIO_24X24',
                    'ciclo_inicio' => $turnoBase->ciclo_inicio ?? $radio['inicio'],
                    'trabajo_horas' => $turnoBase->trabajo_horas ?? 24,
                    'descanso_horas' => $turnoBase->descanso_horas ?? 24,
                    'activo' => true,
                    'updated_at' => $ahora,
                    'created_at' => $ahora,
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('turnos')) {
            return;
        }

        DB::table('turnos')
            ->whereIn('slug', ['radio-a', 'radio-b'])
            ->get(['id'])
            ->each(function ($turno) {
                $enUso = DB::table('users')->where('turno_id', $turno->id)->exists()
                    || DB::table('personals')->where('turno_id', $turno->id)->exists()
                    || DB::table('patrullas')->where('turno_id', $turno->id)->exists();

                if (!$enUso) {
                    DB::table('turnos')->where('id', $turno->id)->delete();
                }
            });
    }
};
