<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('waze_alert_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('waze_alert_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'waze_alert_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('waze_alert_id')->references('id')->on('waze_alerts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waze_alert_reads');
    }
};
