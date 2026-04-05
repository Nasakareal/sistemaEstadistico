<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVialidadDispositivoDetallesTable extends Migration
{
    public function up()
    {
        Schema::create('vialidad_dispositivo_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vialidad_dispositivo_id');
            $table->unsignedInteger('orden')->default(1);
            $table->string('tipo')->default('texto');
            $table->string('titulo')->nullable();
            $table->text('contenido');
            $table->string('ubicacion')->nullable();
            $table->time('hora')->nullable();
            $table->timestamps();

            $table->foreign('vialidad_dispositivo_id', 'fk_vdd_dispositivo')
                ->references('id')
                ->on('vialidad_dispositivos')
                ->onDelete('cascade');

            $table->index(['vialidad_dispositivo_id', 'orden'], 'idx_vdd_dispositivo_orden');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vialidad_dispositivo_detalles');
    }
}
