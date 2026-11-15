<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sierse_integration_events', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 80);
            $table->string('external_event_id', 120);
            $table->string('vehicle_source', 40);
            $table->unsignedBigInteger('vehicle_id');
            $table->string('event_type', 40);
            $table->string('schema_version', 20)->default('1.0');
            $table->char('payload_sha256', 64);
            $table->json('payload');
            $table->string('status', 30)->default('RECEIVED');
            $table->text('processing_message')->nullable();
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'external_event_id'], 'sierse_events_client_external_unique');
            $table->index(['vehicle_source', 'vehicle_id'], 'sierse_events_vehicle_idx');
            $table->index(['status', 'received_at'], 'sierse_events_status_received_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sierse_integration_events');
    }
};
