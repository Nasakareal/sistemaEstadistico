<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSexoToConstanciasManejoTable extends Migration
{
    public function up()
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            if (!Schema::hasColumn('constancias_manejo', 'sexo')) {
                $table->string('sexo', 20)->nullable()->after('nombre_solicitante');
            }
        });
    }

    public function down()
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            if (Schema::hasColumn('constancias_manejo', 'sexo')) {
                $table->dropColumn('sexo');
            }
        });
    }
}
