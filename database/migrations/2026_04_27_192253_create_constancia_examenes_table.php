<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaExamenesTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_examenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constancia_id');
            $table->enum('modalidad', ['LINEA', 'IMPRESO']);
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->unsignedInteger('total_preguntas')->default(0);
            $table->unsignedInteger('aciertos')->default(0);
            $table->unsignedInteger('errores')->default(0);
            $table->enum('resultado', ['APROBADO', 'REPROBADO']);
            $table->unsignedBigInteger('capturado_por')->nullable();
            $table->dateTime('fecha_examen');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('constancia_id');
            $table->index('modalidad');
            $table->index('resultado');
            $table->index('capturado_por');
            $table->index('fecha_examen');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_examenes');
    }
}
