<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuestasDisposicionPersonasTable extends Migration
{
    public function up()
    {
        Schema::create('puestas_disposicion_personas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('puesta_disposicion_id');

            $table->string('nombre_completo', 255);
            $table->string('alias', 255)->nullable();
            $table->integer('edad')->nullable();
            $table->string('sexo', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('curp', 50)->nullable();
            $table->string('rfc', 30)->nullable();
            $table->text('domicilio')->nullable();

            $table->string('calidad', 100);
            $table->string('delito_o_motivo', 255)->nullable();
            $table->boolean('orden_aprehension')->default(false);
            $table->string('mandamiento_judicial', 255)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('puesta_disposicion_id');
            $table->index('nombre_completo');
            $table->index('calidad');

            $table->foreign('puesta_disposicion_id')
                ->references('id')
                ->on('puestas_disposicion')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('puestas_disposicion_personas');
    }
}
