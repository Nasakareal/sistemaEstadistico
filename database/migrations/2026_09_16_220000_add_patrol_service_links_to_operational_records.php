<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['actividades', 'hechos'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('patrulla_id')
                    ->nullable()
                    ->constrained('patrullas')
                    ->nullOnDelete();
                $table->foreignId('bitacora_servicio_patrulla_id')
                    ->nullable()
                    ->constrained('bitacora_servicio_patrullas')
                    ->nullOnDelete();
                $table->index(
                    ['bitacora_servicio_patrulla_id', 'fecha'],
                    'operational_patrol_log_date_idx'
                );
            });
        }
    }

    public function down(): void
    {
        foreach (['actividades', 'hechos'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('operational_patrol_log_date_idx');
                $table->dropConstrainedForeignId('bitacora_servicio_patrulla_id');
                $table->dropConstrainedForeignId('patrulla_id');
            });
        }
    }
};
