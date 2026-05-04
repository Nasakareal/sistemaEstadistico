<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIphDelegacionesPathToHechosTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('hechos', 'iph_delegaciones_path')) {
            return;
        }

        Schema::table('hechos', function (Blueprint $table) {
            $table->string('iph_delegaciones_path')->nullable()->after('foto_situacion');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('hechos', 'iph_delegaciones_path')) {
            return;
        }

        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn('iph_delegaciones_path');
        });
    }
}
