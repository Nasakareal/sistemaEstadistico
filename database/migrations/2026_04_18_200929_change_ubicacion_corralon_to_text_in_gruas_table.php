<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeUbicacionCorralonToTextInGruasTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE gruas MODIFY ubicacion_corralon TEXT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE gruas MODIFY ubicacion_corralon JSON NULL");
    }
}
