<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehiculoIdToServiciosTable extends Migration
{
    public function up()
    {
        Schema::table('servicios', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->unsignedBigInteger('vehiculo_id')->after('id');

            // (opcional) Agrega la relación si tienes la tabla vehículos
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('servicios', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropColumn('vehiculo_id');
        });
    }
}
