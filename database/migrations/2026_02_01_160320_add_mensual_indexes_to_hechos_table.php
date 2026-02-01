<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {

            $table->index('fecha', 'hechos_fecha_idx');
            $table->index(['fecha', 'tipo_hecho'], 'hechos_fecha_tipo_idx');
            $table->index(['fecha', 'sector'], 'hechos_fecha_sector_idx');
            $table->index(['fecha', 'situacion'], 'hechos_fecha_situacion_idx');
            $table->index(['fecha', 'municipio'], 'hechos_fecha_municipio_idx');
            $table->index(['fecha', 'colonia'], 'hechos_fecha_colonia_idx');
            $table->index(['fecha', 'lat', 'lng'], 'hechos_fecha_coords_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex('hechos_fecha_idx');
            $table->dropIndex('hechos_fecha_tipo_idx');
            $table->dropIndex('hechos_fecha_sector_idx');
            $table->dropIndex('hechos_fecha_situacion_idx');
            $table->dropIndex('hechos_fecha_municipio_idx');
            $table->dropIndex('hechos_fecha_colonia_idx');
            $table->dropIndex('hechos_fecha_coords_idx');
        });
    }
};
