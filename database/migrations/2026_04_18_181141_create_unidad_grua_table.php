<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadGruaTable extends Migration
{
    public function up()
    {
        Schema::create('unidad_grua', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('grua_id');
            $table->timestamps();

            $table->foreign('unidad_id')->references('id')->on('unidades')->onDelete('cascade');
            $table->foreign('grua_id')->references('id')->on('gruas')->onDelete('cascade');

            $table->unique(['unidad_id', 'grua_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('unidad_grua');
    }
}
