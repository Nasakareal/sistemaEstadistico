<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperativoDispositivoCatalogosTable extends Migration
{
    public function up()
    {
        Schema::create('operativo_dispositivo_catalogos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id')->nullable()->index();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->boolean('activo')->default(1);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('operativo_dispositivo_catalogos');
    }
}
