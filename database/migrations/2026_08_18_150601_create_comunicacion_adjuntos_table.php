<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComunicacionAdjuntosTable extends Migration
{
    public function up()
    {
        Schema::create('comunicacion_adjuntos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comunicacion_id')
                ->constrained('comunicaciones')
                ->onDelete('cascade');

            $table->string('tipo', 30)->default('imagen');

            $table->string('disk', 50)->nullable();

            $table->string('ruta', 2048);

            $table->string('nombre_original')->nullable();

            $table->string('mime_type', 100)->nullable();

            $table->unsignedBigInteger('tamano_bytes')->nullable();

            $table->unsignedInteger('ancho')->nullable();

            $table->unsignedInteger('alto')->nullable();

            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();

            $table->index('comunicacion_id');
            $table->index('tipo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comunicacion_adjuntos');
    }
}
