<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fomento_municipios_atendidos_historicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->string('municipio', 120);
            $table->unsignedInteger('eventos')->default(0);
            $table->unsignedInteger('poblacion_atendida')->default(0);
            $table->string('source_marker', 160);
            $table->string('activity_marker', 160)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes', 'municipio', 'source_marker'], 'fcv_mun_hist_unique');
            $table->index(['anio', 'mes'], 'fcv_mun_hist_anio_mes_idx');
            $table->index('activity_marker', 'fcv_mun_hist_activity_marker_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fomento_municipios_atendidos_historicos');
    }
};
