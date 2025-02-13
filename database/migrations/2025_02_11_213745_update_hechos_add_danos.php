<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHechosAddDanos extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->text('danos_patrimoniales')->nullable()->after('situacion');
            $table->text('propiedades_afectadas')->nullable()->after('danos_patrimoniales');
            $table->decimal('monto_danos_patrimoniales', 10, 2)->nullable()->after('propiedades_afectadas');
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn(['danos_patrimoniales', 'propiedades_afectadas', 'monto_danos_patrimoniales']);
        });
    }
}
