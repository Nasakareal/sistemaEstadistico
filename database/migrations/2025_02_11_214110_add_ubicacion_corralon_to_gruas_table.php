<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUbicacionCorralonToGruasTable extends Migration
{
    public function up()
    {
        Schema::table('gruas', function (Blueprint $table) {
            $table->json('ubicacion_corralon')->nullable()->after('direccion');
        });
    }

    public function down()
    {
        Schema::table('gruas', function (Blueprint $table) {
            $table->dropColumn('ubicacion_corralon');
        });
    }
}
