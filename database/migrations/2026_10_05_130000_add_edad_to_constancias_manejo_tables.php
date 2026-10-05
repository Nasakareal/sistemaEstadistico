<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEdadToConstanciasManejoTables extends Migration
{
    public function up()
    {
        Schema::table('constancia_examen_solicitudes', function (Blueprint $table) {
            $table->unsignedTinyInteger('edad')->nullable()->after('sexo');
        });

        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->unsignedTinyInteger('edad')->nullable()->after('sexo');
        });
    }

    public function down()
    {
        Schema::table('constancia_examen_solicitudes', function (Blueprint $table) {
            $table->dropColumn('edad');
        });

        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->dropColumn('edad');
        });
    }
}
