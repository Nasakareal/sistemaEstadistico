<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchivoUsoFuerzaToPuestasDisposicionTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('puestas_disposicion', 'archivo_uso_fuerza')) {
            Schema::table('puestas_disposicion', function (Blueprint $table) {
                $table->string('archivo_uso_fuerza', 255)->nullable()->after('archivo_puesta');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('puestas_disposicion', 'archivo_uso_fuerza')) {
            Schema::table('puestas_disposicion', function (Blueprint $table) {
                $table->dropColumn('archivo_uso_fuerza');
            });
        }
    }
}
