<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_grid_cells', function (Blueprint $table) {
            $table->id();

            $table->string('cell_key', 120)->unique();

            $table->unsignedInteger('cell_size_m')->default(500)->index();

            $table->decimal('min_lat', 10, 7)->index();
            $table->decimal('min_lng', 10, 7)->index();
            $table->decimal('max_lat', 10, 7)->index();
            $table->decimal('max_lng', 10, 7)->index();

            $table->decimal('centroid_lat', 10, 7)->index();
            $table->decimal('centroid_lng', 10, 7)->index();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->index(['min_lat', 'max_lat'], 'idx_geo_grid_cells_lat_range');
            $table->index(['min_lng', 'max_lng'], 'idx_geo_grid_cells_lng_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_grid_cells');
    }
};
