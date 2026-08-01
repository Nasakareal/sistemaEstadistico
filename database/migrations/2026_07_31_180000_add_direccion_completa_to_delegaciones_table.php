<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->string('direccion_completa', 500)->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->dropColumn('direccion_completa');
        });
    }
};
