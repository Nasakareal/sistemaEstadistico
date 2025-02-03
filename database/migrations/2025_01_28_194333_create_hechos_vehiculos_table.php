<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHechosVehiculosTable extends Migration
{
    public function up()
    {
        Schema::create('hecho_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hecho_id')->constrained('hechos')->onDelete('cascade');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hecho_vehiculos');
    }
}
