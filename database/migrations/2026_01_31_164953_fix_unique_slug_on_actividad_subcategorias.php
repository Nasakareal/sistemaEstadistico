<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividad_subcategorias', function (Blueprint $table) {
            $table->dropUnique('actividad_subcategorias_slug_unique');
            $table->unique(['actividad_categoria_id', 'slug'], 'actividad_subcategorias_categoria_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('actividad_subcategorias', function (Blueprint $table) {
            $table->dropUnique('actividad_subcategorias_categoria_slug_unique');
            $table->unique('slug', 'actividad_subcategorias_slug_unique');
        });
    }
};
