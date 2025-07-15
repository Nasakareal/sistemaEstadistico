<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiberacionesTable extends Migration
{
    public function up()
    {
        Schema::create('liberaciones', function (Blueprint $table) {
            $table->id();

            // Relación con vehículo
            $table->foreignId('vehiculo_id')->constrained()->onDelete('cascade');

            // Para el acceso por QR
            $table->uuid('token_unico')->unique();

            // Información de la liberación
            $table->date('fecha_liberacion')->nullable();
            $table->text('personas_autorizadas')->nullable();
            $table->text('observaciones')->nullable();

            // PDF que sube grúas
            $table->string('pdf_gruas')->nullable();

            // Usuario que creó la liberación
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('liberaciones');
    }
}
