<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('turnos')) {
            $turnoId = DB::table('turnos')
                ->where('slug', 'instructor')
                ->orWhereRaw('UPPER(TRIM(nombre)) = ?', ['INSTRUCTOR'])
                ->value('id');

            $payload = $this->onlyExistingColumns('turnos', [
                'nombre' => 'Instructor',
                'slug' => 'instructor',
                'activo' => true,
                'tipo_rol' => 'LUN_VIE',
                'ciclo_inicio' => null,
                'trabajo_horas' => null,
                'descanso_horas' => null,
                'updated_at' => $now,
            ]);

            if ($turnoId) {
                DB::table('turnos')->where('id', $turnoId)->update($payload);
            } else {
                DB::table('turnos')->insert(array_merge(
                    $payload,
                    $this->onlyExistingColumns('turnos', ['created_at' => $now])
                ));
            }
        }

        if (Schema::hasTable('incidencia_tipos')) {
            $tipoId = DB::table('incidencia_tipos')
                ->where('clave', 'SERVICIO')
                ->orWhereRaw('UPPER(TRIM(nombre)) = ?', ['SERVICIO'])
                ->value('id');

            $payload = $this->onlyExistingColumns('incidencia_tipos', [
                'clave' => 'SERVICIO',
                'nombre' => 'SERVICIO',
                'categoria' => 'PERSONAL',
                'descuenta' => false,
                'requiere_documento' => false,
                'activo' => true,
                'updated_at' => $now,
            ]);

            if ($tipoId) {
                DB::table('incidencia_tipos')->where('id', $tipoId)->update($payload);
            } else {
                DB::table('incidencia_tipos')->insert(array_merge(
                    $payload,
                    $this->onlyExistingColumns('incidencia_tipos', ['created_at' => $now])
                ));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('turnos')) {
            $inUse = Schema::hasTable('personals')
                && DB::table('personals')
                    ->whereIn('turno_id', function ($query) {
                        $query->select('id')
                            ->from('turnos')
                            ->where('slug', 'instructor');
                    })
                    ->exists();

            if (!$inUse) {
                DB::table('turnos')->where('slug', 'instructor')->delete();
            }
        }

        if (Schema::hasTable('incidencia_tipos')) {
            $inUse = Schema::hasTable('personal_incidencias')
                && DB::table('personal_incidencias')
                    ->whereIn('incidencia_tipo_id', function ($query) {
                        $query->select('id')
                            ->from('incidencia_tipos')
                            ->where('clave', 'SERVICIO');
                    })
                    ->exists();

            if (!$inUse) {
                DB::table('incidencia_tipos')->where('clave', 'SERVICIO')->delete();
            }
        }
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
};
