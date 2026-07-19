<?php

use Carbon\Carbon;
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
            ['base' => 'a', 'anterior' => 'radio-a', 'grupo' => 'A', 'inicio' => '2026-02-23 07:00:00'],
            ['base' => 'b', 'anterior' => 'radio-b', 'grupo' => 'B', 'inicio' => '2026-02-24 07:00:00'],
        ] as $radio) {
            $turnoBase = DB::table('turnos')->where('slug', $radio['base'])->first();
            $inicioManana = Carbon::parse($turnoBase->ciclo_inicio ?? $radio['inicio']);
            $turnoManana = DB::table('turnos')
                ->whereIn('slug', [$radio['anterior'], 'radio-' . strtolower($radio['grupo']) . '-manana'])
                ->first();
            $datosManana = [
                'nombre' => 'Radio-' . $radio['grupo'] . ' Mañana',
                'slug' => 'radio-' . strtolower($radio['grupo']) . '-manana',
                'tipo_rol' => 'RADIO_12X36',
                'ciclo_inicio' => $inicioManana->toDateTimeString(),
                'trabajo_horas' => 12,
                'descanso_horas' => 36,
                'activo' => true,
                'updated_at' => $ahora,
            ];

            if ($turnoManana) {
                DB::table('turnos')->where('id', $turnoManana->id)->update($datosManana);
            } else {
                DB::table('turnos')->insert(array_merge($datosManana, ['created_at' => $ahora]));
            }

            DB::table('turnos')->updateOrInsert(
                ['slug' => 'radio-' . strtolower($radio['grupo']) . '-noche'],
                [
                    'nombre' => 'Radio-' . $radio['grupo'] . ' Noche',
                    'tipo_rol' => 'RADIO_12X36',
                    'ciclo_inicio' => $inicioManana->copy()->addHours(12)->toDateTimeString(),
                    'trabajo_horas' => 12,
                    'descanso_horas' => 36,
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

        foreach (['radio-a-noche', 'radio-b-noche'] as $slug) {
            $turno = DB::table('turnos')->where('slug', $slug)->first();

            if ($turno && !$this->turnoEnUso((int) $turno->id)) {
                DB::table('turnos')->where('id', $turno->id)->delete();
            }
        }

        foreach ([
            ['actual' => 'radio-a-manana', 'slug' => 'radio-a', 'nombre' => 'Radio-A'],
            ['actual' => 'radio-b-manana', 'slug' => 'radio-b', 'nombre' => 'Radio-B'],
        ] as $radio) {
            DB::table('turnos')->where('slug', $radio['actual'])->update([
                'slug' => $radio['slug'],
                'nombre' => $radio['nombre'],
                'tipo_rol' => 'RADIO_24X24',
                'trabajo_horas' => 24,
                'descanso_horas' => 24,
                'updated_at' => now(),
            ]);
        }
    }

    private function turnoEnUso(int $turnoId): bool
    {
        return DB::table('users')->where('turno_id', $turnoId)->exists()
            || DB::table('personals')->where('turno_id', $turnoId)->exists()
            || DB::table('patrullas')->where('turno_id', $turnoId)->exists();
    }
};
