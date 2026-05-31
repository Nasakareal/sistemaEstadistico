<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappSendGuardsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_send_guards', function (Blueprint $table) {
            $table->id();
            $table->string('context', 64);
            $table->string('period_key', 64);
            $table->string('recipient', 32);
            $table->string('status', 20)->default('sending');
            $table->string('message_id')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['context', 'period_key', 'recipient'], 'whatsapp_send_guards_unique');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_send_guards');
    }
}
