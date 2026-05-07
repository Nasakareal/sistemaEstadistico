<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrigenToServiciosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('servicios', 'unidad_id')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->unsignedBigInteger('unidad_id')->nullable()->after('grua_id')->index();
            });
        }

        if (!Schema::hasColumn('servicios', 'delegacion_id')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->unsignedBigInteger('delegacion_id')->nullable()->after('unidad_id')->index();
            });
        }
    }

    public function down()
    {
        // Estas columnas conservan contexto historico para estadisticas de gruas.
    }
}
