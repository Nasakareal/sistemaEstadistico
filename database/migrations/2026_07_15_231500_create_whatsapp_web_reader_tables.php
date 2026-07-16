<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappWebReaderTables extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_web_groups', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_id', 191)->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('participant_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_web_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_web_group_id')
                ->constrained('whatsapp_web_groups')
                ->cascadeOnDelete();
            $table->string('whatsapp_message_id', 191)->unique();
            $table->string('author_whatsapp_id', 191)->nullable();
            $table->text('body')->nullable();
            $table->string('message_type', 50)->default('unknown');
            $table->boolean('has_media')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_web_group_id', 'sent_at'], 'wa_web_messages_group_sent_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_web_messages');
        Schema::dropIfExists('whatsapp_web_groups');
    }
}
