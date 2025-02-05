<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDictamensTable extends Migration
{
    public function up()
    {
        Schema::create('dictamens', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_dictamen')->unsigned();
            $table->year('anio');
            $table->string('nombre_policia', 100);
            $table->string('nombre_mp', 100);
            $table->string('area', 100);
            $table->string('archivo_dictamen')->nullable();
            $table->timestamps();

            $table->unique(['numero_dictamen', 'anio', 'area']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dictamens');
    }
}
