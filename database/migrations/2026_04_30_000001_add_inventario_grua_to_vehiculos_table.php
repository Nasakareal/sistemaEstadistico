<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInventarioGruaToVehiculosTable extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('vehiculos', 'grua_id')) {
                $table->unsignedBigInteger('grua_id')->nullable()->after('grua')->index();
            }

            if (!Schema::hasColumn('vehiculos', 'numero_inventario_grua')) {
                $table->string('numero_inventario_grua', 100)->nullable()->after('grua_id');
            }

            if (!Schema::hasColumn('vehiculos', 'foto_inventario_grua')) {
                $table->string('foto_inventario_grua')->nullable()->after('numero_inventario_grua');
            }

            if (!Schema::hasColumn('vehiculos', 'fecha_inventario_grua')) {
                $table->timestamp('fecha_inventario_grua')->nullable()->after('foto_inventario_grua');
            }
        });
    }

    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            foreach (['fecha_inventario_grua', 'foto_inventario_grua', 'numero_inventario_grua', 'grua_id'] as $column) {
                if (Schema::hasColumn('vehiculos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
