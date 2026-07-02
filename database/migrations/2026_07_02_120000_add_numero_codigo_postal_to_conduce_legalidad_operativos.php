<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_operativos', 'numero')) {
                $table->string('numero', 40)->nullable()->after('lugar');
            }

            if (!Schema::hasColumn('conduce_legalidad_operativos', 'codigo_postal')) {
                $table->string('codigo_postal', 10)->nullable()->after('colonia');
                $table->index('codigo_postal', 'cl_operativos_codigo_postal_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_operativos', 'codigo_postal')) {
                $table->dropIndex('cl_operativos_codigo_postal_idx');
                $table->dropColumn('codigo_postal');
            }

            if (Schema::hasColumn('conduce_legalidad_operativos', 'numero')) {
                $table->dropColumn('numero');
            }
        });
    }
};
