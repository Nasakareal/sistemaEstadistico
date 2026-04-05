<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVialidadDispositivoCatalogosTable extends Migration
{
    public function up()
    {
        Schema::create('vialidad_dispositivo_catalogos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();

            $table->foreign('unidad_id', 'fk_vdc_unidad')
                ->references('id')
                ->on('unidades')
                ->onDelete('cascade');

            $table->index(['unidad_id', 'activo'], 'idx_vdc_unidad_activo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vialidad_dispositivo_catalogos');
    }
}
