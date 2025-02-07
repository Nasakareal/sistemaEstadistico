<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormatosTable extends Migration
{
    public function up()
    {
        Schema::create('formatos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('archivo_pdf');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('formatos');
    }
}
