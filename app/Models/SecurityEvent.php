<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'occurred_at',
        'first_seen_at',
        'last_seen_at',
        'bucket_at',
        'occurrences',
        'severity',
        'category',
        'event_code',
        'description',
        'ip_address',
        'user_id',
        'method',
        'path',
        'route_name',
        'status_code',
        'request_id',
        'user_agent',
        'metadata',
        'fingerprint',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'bucket_at' => 'datetime',
        'metadata' => 'array',
        'occurrences' => 'integer',
        'status_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
