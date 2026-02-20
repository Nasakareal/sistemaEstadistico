<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->string('cell_key', 32)->nullable()->index()->after('lng');
        });

        DB::statement("
            UPDATE waze_alerts
            SET cell_key = CONCAT(ROUND(lat, 3), ',', ROUND(lng, 3))
            WHERE lat IS NOT NULL AND lng IS NOT NULL
        ");

        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->index(['type', 'published_at'], 'waze_type_published_idx');
            $table->index(['cell_key', 'published_at'], 'waze_cell_published_idx');
        });
    }

    public function down(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->dropIndex('waze_type_published_idx');
            $table->dropIndex('waze_cell_published_idx');
            $table->dropColumn('cell_key');
        });
    }
};
