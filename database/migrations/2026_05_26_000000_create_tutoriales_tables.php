<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorial_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('slug', 170)->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tutoriales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutorial_categoria_id')
                ->nullable()
                ->constrained('tutorial_categorias')
                ->nullOnDelete();
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->string('youtube_url', 500);
            $table->string('youtube_video_id', 40)->nullable();
            $table->string('plataforma', 30)->default('app_movil');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['plataforma', 'activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutoriales');
        Schema::dropIfExists('tutorial_categorias');
    }
};
