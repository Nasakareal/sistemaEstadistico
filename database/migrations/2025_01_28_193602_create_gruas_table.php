<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGruasTable extends Migration
{
    public function up()
    {
        Schema::create('gruas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('direccion')->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('gruas');
    }
}
