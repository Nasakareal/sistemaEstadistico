<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('hechos', 'foto_lugar_2')) {
            Schema::table('hechos', function (Blueprint $table) {
                $table->string('foto_lugar_2')->nullable()->after('foto_lugar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hechos', 'foto_lugar_2')) {
            Schema::table('hechos', function (Blueprint $table) {
                $table->dropColumn('foto_lugar_2');
            });
        }
    }
};
