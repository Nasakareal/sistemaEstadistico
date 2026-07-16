<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddC5iRecommendationToWhatsappWebMessages extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->decimal('incident_lat', 10, 7)->nullable()->after('sent_at');
            $table->decimal('incident_lng', 10, 7)->nullable()->after('incident_lat');
            $table->unsignedBigInteger('recommended_patrulla_id')->nullable()->after('incident_lng');
            $table->decimal('recommendation_distance_km', 10, 3)->nullable()->after('recommended_patrulla_id');
            $table->string('recommendation_status', 32)->nullable()->after('recommendation_distance_km');
            $table->longText('recommendation_meta')->nullable()->after('recommendation_status');
            $table->timestamp('recommendation_processed_at')->nullable()->after('recommendation_meta');

            $table->foreign('recommended_patrulla_id', 'wa_web_messages_recommended_patrulla_fk')
                ->references('id')
                ->on('patrullas')
                ->nullOnDelete();
            $table->index('recommendation_status', 'wa_web_messages_recommendation_status_idx');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_web_messages', function (Blueprint $table) {
            $table->dropForeign('wa_web_messages_recommended_patrulla_fk');
            $table->dropIndex('wa_web_messages_recommendation_status_idx');
            $table->dropColumn([
                'incident_lat',
                'incident_lng',
                'recommended_patrulla_id',
                'recommendation_distance_km',
                'recommendation_status',
                'recommendation_meta',
                'recommendation_processed_at',
            ]);
        });
    }
}

