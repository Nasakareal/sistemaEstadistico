<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaModulosTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', ['SINIESTROS', 'DELEGACION']);
            $table->string('municipio')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('municipio');
            $table->index('delegacion_id');
            $table->index('unidad_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_modulos');
    }
}
