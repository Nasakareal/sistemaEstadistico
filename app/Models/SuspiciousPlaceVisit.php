<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuspiciousPlaceVisit extends Model
{
    protected $fillable = [
        'active_key',
        'client_visit_id',
        'user_id',
        'patrulla_id',
        'place_key',
        'place_name',
        'entered_at',
        'last_inside_at',
        'last_location_at',
        'dwell_alerted_at',
        'exited_at',
        'exit_alerted_at',
        'client_entry_received_at',
        'client_exit_received_at',
        'duration_seconds',
        'last_distance_meters',
        'status',
        'entry_notification_status',
        'exit_notification_status',
        'notification_meta',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'last_inside_at' => 'datetime',
        'last_location_at' => 'datetime',
        'dwell_alerted_at' => 'datetime',
        'exited_at' => 'datetime',
        'exit_alerted_at' => 'datetime',
        'client_entry_received_at' => 'datetime',
        'client_exit_received_at' => 'datetime',
        'duration_seconds' => 'integer',
        'last_distance_meters' => 'float',
        'notification_meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class);
    }
}
