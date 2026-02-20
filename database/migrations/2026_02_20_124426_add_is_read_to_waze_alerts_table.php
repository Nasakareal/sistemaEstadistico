<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('notified');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::table('waze_alerts', function (Blueprint $table) {
            $table->dropIndex(['is_read']);
            $table->dropColumn('is_read');
        });
    }
};
