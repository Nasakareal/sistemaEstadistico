<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('serie', 17)->nullable()->change();
            $table->string('tarjeta_circulacion_nombre', 60)->nullable()->change();
        });
    }

    public function down()
    {
        DB::table('vehiculos')
            ->whereNull('serie')
            ->update(['serie' => '']);

        DB::table('vehiculos')
            ->whereNull('tarjeta_circulacion_nombre')
            ->update(['tarjeta_circulacion_nombre' => '']);

        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('serie', 17)->nullable(false)->change();
            $table->string('tarjeta_circulacion_nombre', 60)->nullable(false)->change();
        });
    }
};
