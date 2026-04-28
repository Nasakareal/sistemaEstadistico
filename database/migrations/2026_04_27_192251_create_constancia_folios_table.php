<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaFoliosTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_folios', function (Blueprint $table) {
            $table->id();
            $table->string('prefijo', 5);
            $table->unsignedInteger('numero');
            $table->string('folio', 20)->unique();
            $table->enum('origen', ['SINIESTROS', 'DELEGACIONES']);
            $table->unsignedBigInteger('modulo_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('constancia_id')->nullable();
            $table->enum('estatus', ['DISPONIBLE', 'ASIGNADO', 'CANCELADO'])->default('DISPONIBLE');
            $table->timestamps();

            $table->unique(['prefijo', 'numero']);
            $table->index('origen');
            $table->index('modulo_id');
            $table->index('delegacion_id');
            $table->index('constancia_id');
            $table->index('estatus');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_folios');
    }
}
