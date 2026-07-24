<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->foreignId('actividad_id')
                ->nullable()
                ->after('hecho_id')
                ->constrained('actividades')
                ->nullOnDelete();
            $table->unique('actividad_id', 'puestas_disposicion_actividad_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->dropUnique('puestas_disposicion_actividad_id_unique');
            $table->dropConstrainedForeignId('actividad_id');
        });
    }
};
