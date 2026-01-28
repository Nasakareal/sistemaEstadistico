<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('updated_at');
            $table->string('connection_status', 20)->default('unknown')->after('last_seen_at');
            $table->timestamp('disconnected_alert_sent_at')->nullable()->after('connection_status');

            $table->index('last_seen_at');
            $table->index('connection_status');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['connection_status']);

            $table->dropColumn('disconnected_alert_sent_at');
            $table->dropColumn('connection_status');
            $table->dropColumn('last_seen_at');
        });
    }
};
