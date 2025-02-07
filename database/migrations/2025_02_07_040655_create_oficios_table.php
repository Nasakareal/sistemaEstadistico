<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOficiosTable extends Migration
{
    public function up()
    {
        Schema::create('oficios', function (Blueprint $table) {
            $table->id();
            $table->string('numero_oficio')->unique();
            $table->text('descripcion')->nullable();
            $table->string('pdf_path');
            $table->json('fotos')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oficios');
    }
}
