<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspicious_place_visits', function (Blueprint $table) {
            $table->id();
            $table->string('active_key', 191)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrulla_id')->constrained('patrullas')->cascadeOnDelete();
            $table->string('place_key', 80);
            $table->string('place_name', 150);
            $table->timestamp('entered_at');
            $table->timestamp('last_inside_at');
            $table->timestamp('last_location_at');
            $table->timestamp('dwell_alerted_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->timestamp('exit_alerted_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('last_distance_meters', 10, 2)->nullable();
            $table->string('status', 30)->default('monitoring');
            $table->string('entry_notification_status', 30)->nullable();
            $table->string('exit_notification_status', 30)->nullable();
            $table->json('notification_meta')->nullable();
            $table->timestamps();

            $table->index(['patrulla_id', 'place_key', 'entered_at'], 'suspicious_visit_lookup_idx');
            $table->index(['status', 'last_location_at'], 'suspicious_visit_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_place_visits');
    }
};
