<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAntecedenteVehiculoToVehiculosTable extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->boolean('antecedente_vehiculo')->default(0)->after('fotos');
        });
    }

    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('antecedente_vehiculo');
        });
    }
}
