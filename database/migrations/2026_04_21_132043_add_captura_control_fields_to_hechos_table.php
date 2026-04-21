<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->unsignedInteger('vehiculos_esperados')->default(0)->after('personas_mp');
            $table->unsignedInteger('conductores_esperados')->default(0)->after('vehiculos_esperados');
            $table->unsignedInteger('lesionados_esperados')->default(0)->after('conductores_esperados');

            $table->unsignedInteger('vehiculos_capturados')->default(0)->after('lesionados_esperados');
            $table->unsignedInteger('conductores_capturados')->default(0)->after('vehiculos_capturados');
            $table->unsignedInteger('lesionados_capturados')->default(0)->after('conductores_capturados');

            $table->boolean('captura_completa')->default(false)->after('lesionados_capturados');
            $table->timestamp('captura_completa_at')->nullable()->after('captura_completa');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn([
                'vehiculos_esperados',
                'conductores_esperados',
                'lesionados_esperados',
                'vehiculos_capturados',
                'conductores_capturados',
                'lesionados_capturados',
                'captura_completa',
                'captura_completa_at',
            ]);
        });
    }
};
