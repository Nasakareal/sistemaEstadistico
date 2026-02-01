<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {

            $table->decimal('lat', 10, 7)->nullable()->after('municipio');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('calidad_geo', 20)->nullable()->after('lng');
            $table->text('nota_geo')->nullable()->after('calidad_geo');
            $table->string('fuente_ubicacion', 20)->nullable()->after('nota_geo');
            $table->text('ubicacion_formateada')->nullable()->after('fuente_ubicacion');
            $table->string('place_id', 128)->nullable()->after('ubicacion_formateada');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn([
                'lat',
                'lng',
                'calidad_geo',
                'nota_geo',
                'fuente_ubicacion',
                'ubicacion_formateada',
                'place_id',
            ]);
        });
    }
};
