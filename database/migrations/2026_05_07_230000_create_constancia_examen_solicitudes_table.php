<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaExamenSolicitudesTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_examen_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('folio_examen', 30)->unique();
            $table->string('token', 100)->unique();
            $table->unsignedBigInteger('modulo_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('constancia_id')->nullable();
            $table->string('nombre_solicitante');
            $table->string('sexo', 20);
            $table->string('curp', 18)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('tipo_licencia', 40);
            $table->string('modalidad', 20);
            $table->string('estatus', 20)->default('PENDIENTE');
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->unsignedInteger('total_preguntas')->default(0);
            $table->unsignedInteger('aciertos')->default(0);
            $table->unsignedInteger('errores')->default(0);
            $table->dateTime('fecha_examen')->nullable();
            $table->dateTime('token_expira')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('modulo_id');
            $table->index('delegacion_id');
            $table->index('user_id');
            $table->index('constancia_id');
            $table->index('nombre_solicitante');
            $table->index('curp');
            $table->index('sexo');
            $table->index('tipo_licencia');
            $table->index('modalidad');
            $table->index('estatus');
            $table->index('fecha_examen');
            $table->index('token_expira');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_examen_solicitudes');
    }
}
