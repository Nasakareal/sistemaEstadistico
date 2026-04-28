<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaPreguntasTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_preguntas', function (Blueprint $table) {
            $table->id();
            $table->text('pregunta');
            $table->enum('tipo_licencia', ['SERVICIO_PUBLICO', 'AUTOMOVILISTA', 'CHOFER', 'MOTOCICLISTA', 'PERMISO', 'GENERAL'])->default('GENERAL');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo_licencia');
            $table->index('activo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_preguntas');
    }
}
