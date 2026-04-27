<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cultura_vial_intentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sala_id')->constrained('cultura_vial_salas')->cascadeOnDelete();
            $table->foreignId('participante_id')->constrained('cultura_vial_participantes')->cascadeOnDelete();
            $table->string('juego_slug', 80)->default('ciudad_segura');
            $table->unsignedInteger('puntaje')->default(0);
            $table->unsignedTinyInteger('aciertos')->default(0);
            $table->unsignedTinyInteger('errores')->default(0);
            $table->unsignedInteger('duracion_segundos')->default(0);
            $table->json('decisiones_json')->nullable();
            $table->timestamp('terminado_at')->nullable();
            $table->timestamps();

            $table->index(['sala_id', 'puntaje']);
            $table->index(['participante_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cultura_vial_intentos');
    }
};
