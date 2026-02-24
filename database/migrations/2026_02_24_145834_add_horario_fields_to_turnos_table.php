<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->string('tipo_rol', 30)->nullable()->after('slug');
            $table->dateTime('ciclo_inicio')->nullable()->after('tipo_rol');
            $table->unsignedSmallInteger('trabajo_horas')->nullable()->after('ciclo_inicio');
            $table->unsignedSmallInteger('descanso_horas')->nullable()->after('trabajo_horas');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['tipo_rol', 'ciclo_inicio', 'trabajo_horas', 'descanso_horas']);
        });
    }
};
