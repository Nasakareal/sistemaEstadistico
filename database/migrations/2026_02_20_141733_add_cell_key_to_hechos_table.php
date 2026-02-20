<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('cell_key', 32)->nullable()->index()->after('lng');
        });

        DB::statement("
            UPDATE hechos
            SET cell_key = CONCAT(ROUND(lat, 3), ',', ROUND(lng, 3))
            WHERE lat IS NOT NULL AND lng IS NOT NULL
        ");

        Schema::table('hechos', function (Blueprint $table) {
            $table->index(['cell_key', 'fecha', 'hora'], 'hechos_cell_fecha_hora_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropIndex('hechos_cell_fecha_hora_idx');
            $table->dropColumn('cell_key');
        });
    }
};
