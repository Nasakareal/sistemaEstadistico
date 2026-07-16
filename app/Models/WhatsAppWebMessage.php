<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebMessage extends Model
{
    protected $table = 'whatsapp_web_messages';

    protected $fillable = [
        'whatsapp_web_group_id',
        'whatsapp_message_id',
        'author_whatsapp_id',
        'body',
        'message_type',
        'has_media',
        'sent_at',
    ];

    protected $casts = [
        'has_media' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(WhatsAppWebGroup::class, 'whatsapp_web_group_id');
    }
}
