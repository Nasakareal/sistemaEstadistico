<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDelegacionGruaTable extends Migration
{
    public function up()
    {
        Schema::create('delegacion_grua', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delegacion_id');
            $table->unsignedBigInteger('grua_id');
            $table->timestamps();

            $table->foreign('delegacion_id')->references('id')->on('delegaciones')->onDelete('cascade');
            $table->foreign('grua_id')->references('id')->on('gruas')->onDelete('cascade');

            $table->unique(['delegacion_id', 'grua_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('delegacion_grua');
    }
}
