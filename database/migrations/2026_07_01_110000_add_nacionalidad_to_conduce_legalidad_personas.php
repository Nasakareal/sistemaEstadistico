<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_personas', 'nacionalidad')) {
                $table->string('nacionalidad', 80)->nullable()->after('sexo');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_personas', 'nacionalidad')) {
                $table->dropColumn('nacionalidad');
            }
        });
    }
};