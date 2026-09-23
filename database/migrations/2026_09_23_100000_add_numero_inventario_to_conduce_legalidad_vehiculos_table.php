<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('conduce_legalidad_vehiculos')
            && !Schema::hasColumn('conduce_legalidad_vehiculos', 'numero_inventario')
        ) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->string('numero_inventario', 100)
                    ->nullable()
                    ->after('serie');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('conduce_legalidad_vehiculos')
            && Schema::hasColumn('conduce_legalidad_vehiculos', 'numero_inventario')
        ) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->dropColumn('numero_inventario');
            });
        }
    }
};
