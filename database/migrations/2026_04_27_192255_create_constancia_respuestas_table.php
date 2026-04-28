<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaRespuestasTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pregunta_id');
            $table->text('respuesta');
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();

            $table->index('pregunta_id');
            $table->index('es_correcta');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_respuestas');
    }
}
