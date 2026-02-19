<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WazeAlert extends Model
{
    protected $table = 'waze_alerts';

    protected $fillable = [
        'uuid',
        'waze_id',
        'type',
        'subtype',
        'country',
        'city',
        'street',
        'lat',
        'lng',
        'pub_millis',
        'published_at',
        'notified',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
        'published_at' => 'datetime',
        'notified' => 'boolean',
    ];
}
