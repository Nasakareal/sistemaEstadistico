<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'cl_vehiculos_numero_inventario_unique';

    public function up(): void
    {
        if (
            !Schema::hasTable('conduce_legalidad_vehiculos')
            || !Schema::hasColumn('conduce_legalidad_vehiculos', 'numero_inventario')
        ) {
            return;
        }

        Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
            $table->unique('numero_inventario', self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('conduce_legalidad_vehiculos')
            && Schema::hasColumn('conduce_legalidad_vehiculos', 'numero_inventario')
        ) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }
    }
};
