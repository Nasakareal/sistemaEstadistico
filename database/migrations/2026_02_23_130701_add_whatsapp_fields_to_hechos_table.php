<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->timestamp('whatsapp_sent_at')->nullable()->after('updated_at');
            $table->string('whatsapp_chat_id', 64)->nullable()->after('whatsapp_sent_at');
            $table->string('whatsapp_message_id', 128)->nullable()->after('whatsapp_chat_id');
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_sent_at', 'whatsapp_chat_id', 'whatsapp_message_id']);
        });
    }
};
