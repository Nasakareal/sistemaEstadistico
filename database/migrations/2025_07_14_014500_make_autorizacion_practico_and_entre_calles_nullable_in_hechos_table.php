<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeAutorizacionPracticoAndEntreCallesNullableInHechosTable extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('autorizacion_practico', 255)->nullable()->change();
            $table->string('entre_calles', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('autorizacion_practico', 255)->nullable()->change();
            $table->string('entre_calles', 255)->nullable()->change();
        });
    }
}
