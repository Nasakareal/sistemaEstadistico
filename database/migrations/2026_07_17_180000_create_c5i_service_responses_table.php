<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateC5iServiceResponsesTable extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->string('quoted_whatsapp_message_id', 191)->nullable()->after('whatsapp_message_id');
            $table->index('quoted_whatsapp_message_id', 'wa_web_messages_quoted_id_idx');
        });

        Schema::create('c5i_service_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_web_group_id')
                ->constrained('whatsapp_web_groups')
                ->cascadeOnDelete();
            $table->foreignId('incident_message_id')
                ->unique()
                ->constrained('whatsapp_web_messages')
                ->cascadeOnDelete();
            $table->foreignId('assignment_message_id')
                ->nullable()
                ->unique()
                ->constrained('whatsapp_web_messages')
                ->nullOnDelete();
            $table->foreignId('arrival_message_id')
                ->nullable()
                ->unique()
                ->constrained('whatsapp_web_messages')
                ->nullOnDelete();
            $table->foreignId('patrulla_id')
                ->nullable()
                ->constrained('patrullas')
                ->nullOnDelete();
            $table->string('incident_reference', 100)->nullable();
            $table->text('incident_location')->nullable();
            $table->decimal('incident_lat', 10, 7);
            $table->decimal('incident_lng', 10, 7);
            $table->timestamp('reported_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('gps_arrived_at')->nullable();
            $table->timestamp('arrival_reported_at')->nullable();
            $table->unsignedInteger('report_to_gps_seconds')->nullable();
            $table->unsignedInteger('assignment_to_gps_seconds')->nullable();
            $table->integer('arrival_message_delay_seconds')->nullable();
            $table->decimal('gps_distance_meters', 10, 2)->nullable();
            $table->decimal('gps_accuracy_meters', 10, 2)->nullable();
            $table->string('status', 32)->default('reported');
            $table->string('notification_status', 32)->nullable();
            $table->longText('notification_meta')->nullable();
            $table->timestamp('notification_processed_at')->nullable();
            $table->timestamps();

            $table->index(['patrulla_id', 'status'], 'c5i_response_patrulla_status_idx');
            $table->index(['whatsapp_web_group_id', 'reported_at'], 'c5i_response_group_reported_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('c5i_service_responses');

        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->dropIndex('wa_web_messages_quoted_id_idx');
            $table->dropColumn('quoted_whatsapp_message_id');
        });
    }
}
