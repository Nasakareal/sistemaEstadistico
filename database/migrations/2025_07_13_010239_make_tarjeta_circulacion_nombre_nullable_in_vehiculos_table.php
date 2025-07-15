<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTarjetaCirculacionNombreNullableInVehiculosTable extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('tarjeta_circulacion_nombre')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('tarjeta_circulacion_nombre')->nullable(false)->change();
        });
    }
}
