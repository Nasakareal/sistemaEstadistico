<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suspicious_place_visits', function (Blueprint $table) {
            $table->uuid('client_visit_id')->nullable()->after('active_key')->unique();
            $table->timestamp('client_entry_received_at')->nullable()->after('exit_alerted_at');
            $table->timestamp('client_exit_received_at')->nullable()->after('client_entry_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('suspicious_place_visits', function (Blueprint $table) {
            $table->dropUnique(['client_visit_id']);
            $table->dropColumn([
                'client_visit_id',
                'client_entry_received_at',
                'client_exit_received_at',
            ]);
        });
    }
};
