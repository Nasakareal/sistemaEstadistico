<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WazeAlertRead extends Model
{
    protected $table = 'waze_alert_reads';

    protected $fillable = [
        'user_id',
        'waze_alert_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
