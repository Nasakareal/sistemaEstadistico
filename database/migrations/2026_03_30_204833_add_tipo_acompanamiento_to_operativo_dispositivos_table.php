<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoAcompanamientoToOperativoDispositivosTable extends Migration
{
    public function up()
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->string('tipo_acompanamiento', 50)->nullable()->after('auxilios_viales');
        });
    }

    public function down()
    {
        Schema::table('operativo_dispositivos', function (Blueprint $table) {
            $table->dropColumn('tipo_acompanamiento');
        });
    }
}
