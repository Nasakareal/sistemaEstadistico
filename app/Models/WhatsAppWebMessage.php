<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebMessage extends Model
{
    protected $table = 'whatsapp_web_messages';

    protected $fillable = [
        'whatsapp_web_group_id',
        'whatsapp_message_id',
        'quoted_whatsapp_message_id',
        'author_whatsapp_id',
        'body',
        'message_type',
        'has_media',
        'media_mime_type',
        'media_filename',
        'transcription_text',
        'transcription_status',
        'transcription_meta',
        'transcription_processed_at',
        'sent_at',
        'incident_lat',
        'incident_lng',
        'recommended_patrulla_id',
        'recommendation_distance_km',
        'recommendation_status',
        'recommendation_meta',
        'recommendation_processed_at',
    ];

    protected $casts = [
        'has_media' => 'boolean',
        'transcription_meta' => 'array',
        'transcription_processed_at' => 'datetime',
        'sent_at' => 'datetime',
        'incident_lat' => 'float',
        'incident_lng' => 'float',
        'recommended_patrulla_id' => 'integer',
        'recommendation_distance_km' => 'float',
        'recommendation_meta' => 'array',
        'recommendation_processed_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(WhatsAppWebGroup::class, 'whatsapp_web_group_id');
    }

    public function recommendedPatrulla()
    {
        return $this->belongsTo(Patrulla::class, 'recommended_patrulla_id');
    }

    public function serviceResponse()
    {
        return $this->hasOne(C5iServiceResponse::class, 'incident_message_id');
    }
}
