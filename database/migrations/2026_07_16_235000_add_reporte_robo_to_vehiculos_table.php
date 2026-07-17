<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vehiculos', 'reporte_robo')) {
            Schema::table('vehiculos', function (Blueprint $table): void {
                $table->boolean('reporte_robo')
                    ->default(false)
                    ->after('antecedente_vehiculo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehiculos', 'reporte_robo')) {
            Schema::table('vehiculos', function (Blueprint $table): void {
                $table->dropColumn('reporte_robo');
            });
        }
    }
};
