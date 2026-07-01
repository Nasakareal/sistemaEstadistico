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
            if (!Schema::hasColumn('conduce_legalidad_operativos', 'colonia')) {
                $table->string('colonia', 120)->nullable()->after('lugar');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_operativos')) {
            return;
        }

        Schema::table('conduce_legalidad_operativos', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_operativos', 'colonia')) {
                $table->dropColumn('colonia');
            }
        });
    }
};