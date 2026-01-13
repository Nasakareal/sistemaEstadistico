<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('serie', 17)->nullable(false)->change();
            $table->string('tarjeta_circulacion_nombre', 60)->nullable(false)->change();
        });
    }
};
