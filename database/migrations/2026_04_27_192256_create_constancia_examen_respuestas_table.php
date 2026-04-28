<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaExamenRespuestasTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_examen_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constancia_examen_id');
            $table->unsignedBigInteger('pregunta_id');
            $table->unsignedBigInteger('respuesta_id')->nullable();
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();

            $table->index('constancia_examen_id');
            $table->index('pregunta_id');
            $table->index('respuesta_id');
            $table->index('es_correcta');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_examen_respuestas');
    }
}
