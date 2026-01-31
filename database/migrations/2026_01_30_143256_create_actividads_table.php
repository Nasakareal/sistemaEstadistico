<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug', 140)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // nombre corto para evitar pedos
            $table->unique('nombre', 'uq_act_cat_nombre');
        });

        Schema::create('actividad_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_categoria_id')
                ->constrained('actividad_categorias')
                ->cascadeOnDelete();

            $table->string('nombre', 180);
            $table->string('slug', 200)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // nombre corto para evitar pedos
            $table->unique(['actividad_categoria_id', 'nombre'], 'uq_act_sub_cat_nombre');
        });

        Schema::create('actividades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actividad_categoria_id')
                ->constrained('actividad_categorias')
                ->restrictOnDelete();

            $table->foreignId('actividad_subcategoria_id')
                ->nullable()
                ->constrained('actividad_subcategorias')
                ->nullOnDelete();

            $table->string('nombre', 200);

            $table->unsignedInteger('cantidad')->default(0);

            $table->string('foto_path', 255)->nullable();
            $table->string('foto_nombre_original', 255)->nullable();

            $table->string('foto_hash', 64)->nullable();
            $table->unique('foto_hash', 'uq_act_foto_hash');

            $table->timestamps();

            $table->unique(
                ['actividad_categoria_id', 'actividad_subcategoria_id', 'nombre'],
                'uq_act_cat_sub_nombre'
            );

            $table->index(
                ['actividad_categoria_id', 'actividad_subcategoria_id'],
                'ix_act_cat_sub'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
        Schema::dropIfExists('actividad_subcategorias');
        Schema::dropIfExists('actividad_categorias');
    }
};
