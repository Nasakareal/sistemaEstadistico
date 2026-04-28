<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciasManejoTable extends Migration
{
    public function up()
    {
        Schema::create('constancias_manejo', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->string('folio_qr', 50)->unique();
            $table->unsignedBigInteger('modulo_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('perito_activador_id')->nullable();
            $table->string('nombre_solicitante');
            $table->string('curp', 18)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->enum('tipo_licencia', ['SERVICIO_PUBLICO', 'AUTOMOVILISTA', 'CHOFER', 'MOTOCICLISTA', 'PERMISO']);
            $table->enum('tipo_examen', ['LINEA', 'IMPRESO']);
            $table->enum('estatus', ['IMPRESA_INACTIVA', 'ACTIVA', 'EXPIRADA', 'CANCELADA'])->default('IMPRESA_INACTIVA');
            $table->dateTime('fecha_impresion')->nullable();
            $table->dateTime('fecha_activacion')->nullable();
            $table->dateTime('fecha_expiracion')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('qr_token', 100)->unique();
            $table->timestamps();

            $table->index('modulo_id');
            $table->index('delegacion_id');
            $table->index('user_id');
            $table->index('perito_activador_id');
            $table->index('nombre_solicitante');
            $table->index('curp');
            $table->index('tipo_licencia');
            $table->index('tipo_examen');
            $table->index('estatus');
            $table->index('fecha_impresion');
            $table->index('fecha_activacion');
            $table->index('fecha_expiracion');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancias_manejo');
    }
}
