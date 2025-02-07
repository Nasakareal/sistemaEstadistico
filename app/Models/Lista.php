<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lista extends Model
{
    use HasFactory;

    protected $fillable = [
        'area',
        'total_asistentes',
        'foto_1',
        'foto_2',
        'observaciones',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
