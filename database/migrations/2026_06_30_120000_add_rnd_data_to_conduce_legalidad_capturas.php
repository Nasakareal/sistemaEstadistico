<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_capturas', 'rnd_data')) {
                $table->json('rnd_data')->nullable()->after('observaciones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_capturas', 'rnd_data')) {
                $table->dropColumn('rnd_data');
            }
        });
    }
};