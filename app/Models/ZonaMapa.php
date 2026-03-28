<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaMapa extends Model
{
    use HasFactory;

    protected $table = 'zonas_mapa';

    protected $fillable = [
        'nombre',
        'tipo',
        'geojson',
        'color',
        'activa',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'geojson' => 'array',
        'activa' => 'boolean',
    ];
}
