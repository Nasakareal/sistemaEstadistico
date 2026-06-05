<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodigoPostalToHechosTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('hechos', 'codigo_postal')) {
            return;
        }

        Schema::table('hechos', function (Blueprint $table) {
            $table->string('codigo_postal', 10)->nullable()->after('municipio');
            $table->index('codigo_postal', 'hechos_codigo_postal_idx');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('hechos', 'codigo_postal')) {
            return;
        }

        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex('hechos_codigo_postal_idx');
            $table->dropColumn('codigo_postal');
        });
    }
}
