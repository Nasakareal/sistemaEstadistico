<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAudioTranscriptionToC5iResponseTime extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->string('media_mime_type', 100)->nullable()->after('has_media');
            $table->string('media_filename')->nullable()->after('media_mime_type');
            $table->text('transcription_text')->nullable()->after('media_filename');
            $table->string('transcription_status', 32)->nullable()->after('transcription_text');
            $table->longText('transcription_meta')->nullable()->after('transcription_status');
            $table->timestamp('transcription_processed_at')->nullable()->after('transcription_meta');
        });

        Schema::table('c5i_service_responses', function (Blueprint $table) {
            $table->string('arrival_source', 32)->nullable()->after('arrival_reported_at');
        });
    }

    public function down()
    {
        Schema::table('c5i_service_responses', function (Blueprint $table) {
            $table->dropColumn('arrival_source');
        });

        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->dropColumn([
                'media_mime_type',
                'media_filename',
                'transcription_text',
                'transcription_status',
                'transcription_meta',
                'transcription_processed_at',
            ]);
        });
    }
}
