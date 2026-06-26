<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->columns() as $column) {
            if (!Schema::hasColumn('actividades', $column)) {
                continue;
            }

            Schema::table('actividades', function (Blueprint $table) use ($column) {
                $table->longText($column)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns() as $column) {
            if (!Schema::hasColumn('actividades', $column)) {
                continue;
            }

            Schema::table('actividades', function (Blueprint $table) use ($column) {
                $table->text($column)->nullable()->change();
            });
        }
    }

    private function columns(): array
    {
        return [
            'narrativa',
            'acciones_realizadas',
            'observaciones',
            'elementos_participantes_texto',
            'patrullas_participantes_texto',
        ];
    }
};
