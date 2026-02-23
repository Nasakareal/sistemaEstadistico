<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramos', function (Blueprint $table) {
            $table->decimal('lat_inicio', 10, 7)->nullable()->after('km_inicio');
            $table->decimal('lng_inicio', 10, 7)->nullable()->after('lat_inicio');
            $table->decimal('lat_fin', 10, 7)->nullable()->after('km_fin');
            $table->decimal('lng_fin', 10, 7)->nullable()->after('lat_fin');
        });
    }

    public function down(): void
    {
        Schema::table('tramos', function (Blueprint $table) {
            $table->dropColumn(['lat_inicio', 'lng_inicio', 'lat_fin', 'lng_fin']);
        });
    }
};
