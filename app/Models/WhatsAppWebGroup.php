<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebGroup extends Model
{
    protected $table = 'whatsapp_web_groups';

    protected $fillable = [
        'whatsapp_id',
        'name',
        'participant_count',
        'last_seen_at',
    ];

    protected $casts = [
        'participant_count' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsAppWebMessage::class, 'whatsapp_web_group_id');
    }
}
