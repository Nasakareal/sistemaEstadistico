<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SierseIntegrationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'external_event_id',
        'vehicle_source',
        'vehicle_id',
        'event_type',
        'schema_version',
        'payload_sha256',
        'payload',
        'status',
        'processing_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
