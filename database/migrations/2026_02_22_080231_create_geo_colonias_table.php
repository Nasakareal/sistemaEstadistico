<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_colonias', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 190)->index();
            $table->string('municipio', 190)->nullable()->index();

            $table->longText('poligono_geojson')->nullable();

            $table->decimal('centroid_lat', 10, 7)->nullable()->index();
            $table->decimal('centroid_lng', 10, 7)->nullable()->index();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->unique(['nombre', 'municipio'], 'uniq_geo_colonias_nombre_municipio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_colonias');
    }
};
