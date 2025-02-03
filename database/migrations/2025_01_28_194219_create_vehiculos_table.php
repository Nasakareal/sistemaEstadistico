<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiculosTable extends Migration
{
    public function up()
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('marca', 50);
            $table->string('modelo', 10);
            $table->string('tipo', 50);
            $table->string('linea', 50);
            $table->string('color', 30);
            $table->string('placas', 15)->unique();
            $table->string('estado_placas', 15)->unique();
            $table->string('serie', 17)->unique();
            $table->integer('capacidad_personas');
            $table->string('tipo_servicio', 50);
            $table->string('tarjeta_circulacion_nombre', 60);
            $table->string('grua')->nullable()->default('N/A');
            $table->string('corralon')->nullable()->default('N/A');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('hecho_vehiculo');
        Schema::dropIfExists('vehiculos');
        Schema::enableForeignKeyConstraints();
    }


}
