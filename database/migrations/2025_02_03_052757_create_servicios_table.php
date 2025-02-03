<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiciosTable extends Migration
{
    public function up()
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grua_id')->constrained('gruas')->onDelete('cascade');
            $table->string('tipo_vehiculo');
            $table->string('aseguradora')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('foto_vehiculo')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('servicios');
    }
}
