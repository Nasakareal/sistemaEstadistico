<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAseguradoraToVehiculosTable extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('aseguradora', 100)->nullable()->after('corralon');
            $table->string('fotos')->nullable()->after('aseguradora');
        });
    }

    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('fotos');
            $table->dropColumn('aseguradora');
        });
    }
}
