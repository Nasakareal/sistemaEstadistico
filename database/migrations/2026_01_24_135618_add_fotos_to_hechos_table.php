<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('foto_lugar', 255)->nullable()->after('situacion');
            $table->string('foto_situacion', 255)->nullable()->after('foto_lugar');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn(['foto_lugar', 'foto_situacion']);
        });
    }
};
