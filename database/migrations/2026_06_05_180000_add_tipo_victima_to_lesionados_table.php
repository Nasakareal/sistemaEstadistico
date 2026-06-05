<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoVictimaToLesionadosTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('lesionados', 'tipo_victima')) {
            return;
        }

        Schema::table('lesionados', function (Blueprint $table) {
            $table->string('tipo_victima', 20)->nullable()->after('tipo_lesion');
            $table->index('tipo_victima', 'lesionados_tipo_victima_idx');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('lesionados', 'tipo_victima')) {
            return;
        }

        Schema::table('lesionados', function (Blueprint $table) {
            $table->dropIndex('lesionados_tipo_victima_idx');
            $table->dropColumn('tipo_victima');
        });
    }
}
