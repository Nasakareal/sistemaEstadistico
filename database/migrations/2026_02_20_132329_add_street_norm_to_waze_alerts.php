<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->string('street_norm', 255)->nullable()->index()->after('street');
        });
    }

    public function down(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->dropIndex(['street_norm']);
            $table->dropColumn('street_norm');
        });
    }
};
