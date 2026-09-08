<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaleaEncuestasTables extends Migration
{
    public function up()
    {
        Schema::create('calea_encuestas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->default(10);
            $table->unsignedTinyInteger('calificacion_minima')->default(70);
            $table->boolean('activa')->default(false);
            $table->boolean('permite_externos')->default(false);
            $table->string('codigo_publico', 32)->nullable()->unique();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('creada_por');
            $table->timestamp('disponible_desde')->nullable();
            $table->timestamp('disponible_hasta')->nullable();
            $table->timestamps();
            $table->foreign('unidad_id')->references('id')->on('unidades')->nullOnDelete();
            $table->foreign('creada_por')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['activa', 'unidad_id']);
        });

        Schema::create('calea_encuesta_preguntas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encuesta_id');
            $table->text('texto');
            $table->string('tipo', 20)->default('opcion_unica');
            $table->unsignedSmallInteger('orden');
            $table->unsignedSmallInteger('puntos')->default(1);
            $table->boolean('obligatoria')->default(true);
            $table->timestamps();
            $table->foreign('encuesta_id')->references('id')->on('calea_encuestas')->cascadeOnDelete();
            $table->unique(['encuesta_id', 'orden']);
        });

        Schema::create('calea_encuesta_opciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pregunta_id');
            $table->text('texto');
            $table->unsignedSmallInteger('orden');
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();
            $table->foreign('pregunta_id')->references('id')->on('calea_encuesta_preguntas')->cascadeOnDelete();
            $table->unique(['pregunta_id', 'orden']);
        });

        Schema::create('calea_encuesta_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encuesta_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->boolean('todos')->default(false);
            $table->unsignedBigInteger('asignada_por');
            $table->timestamps();
            $table->foreign('encuesta_id')->references('id')->on('calea_encuestas')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('unidad_id')->references('id')->on('unidades')->cascadeOnDelete();
            $table->foreign('asignada_por')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['encuesta_id', 'user_id']);
            $table->index(['encuesta_id', 'unidad_id']);
        });

        Schema::create('calea_encuesta_intentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('encuesta_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('participante_nombre')->nullable();
            $table->string('participante_email')->nullable();
            $table->string('participante_telefono', 30)->nullable();
            $table->string('participante_identificador', 80)->nullable();
            $table->unsignedBigInteger('participante_unidad_id')->nullable();
            $table->string('estado', 20)->default('en_curso');
            $table->unsignedSmallInteger('numero_intento')->default(1);
            $table->timestamp('iniciado_at');
            $table->timestamp('expira_at');
            $table->timestamp('finalizado_at')->nullable();
            $table->unsignedSmallInteger('aciertos')->default(0);
            $table->unsignedSmallInteger('total_preguntas')->default(0);
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->boolean('aprobado')->nullable();
            $table->string('ip_inicio', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->foreign('encuesta_id')->references('id')->on('calea_encuestas')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('participante_unidad_id')->references('id')->on('unidades')->nullOnDelete();
            $table->index(['encuesta_id', 'user_id', 'estado']);
            $table->index(['estado', 'expira_at']);
        });

        Schema::create('calea_encuesta_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intento_id');
            $table->unsignedBigInteger('pregunta_id');
            $table->unsignedBigInteger('opcion_id')->nullable();
            $table->text('respuesta_texto')->nullable();
            $table->boolean('correcta')->nullable();
            $table->timestamps();
            $table->foreign('intento_id')->references('id')->on('calea_encuesta_intentos')->cascadeOnDelete();
            $table->foreign('pregunta_id')->references('id')->on('calea_encuesta_preguntas')->cascadeOnDelete();
            $table->foreign('opcion_id')->references('id')->on('calea_encuesta_opciones')->nullOnDelete();
            $table->unique(['intento_id', 'pregunta_id']);
        });

        Schema::create('calea_encuesta_reautorizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encuesta_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('autorizada_por');
            $table->text('motivo')->nullable();
            $table->timestamp('consumida_at')->nullable();
            $table->timestamps();
            $table->foreign('encuesta_id')->references('id')->on('calea_encuestas')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('autorizada_por')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['encuesta_id', 'user_id', 'consumida_at'], 'calea_reautorizacion_disponible_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calea_encuesta_reautorizaciones');
        Schema::dropIfExists('calea_encuesta_respuestas');
        Schema::dropIfExists('calea_encuesta_intentos');
        Schema::dropIfExists('calea_encuesta_asignaciones');
        Schema::dropIfExists('calea_encuesta_opciones');
        Schema::dropIfExists('calea_encuesta_preguntas');
        Schema::dropIfExists('calea_encuestas');
    }
}
