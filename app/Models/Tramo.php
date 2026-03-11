<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tramo extends Model
{
    protected $table = 'tramos';

    protected $fillable = [
        'carretera',
        'nombre',
        'km_inicio',
        'lat_inicio',
        'lng_inicio',
        'km_fin',
        'lat_fin',
        'lng_fin',
        'polyline',
        'puntos_json',
        'geom',
        'bbox',
        'activo',
    ];

    protected $casts = [
        'km_inicio' => 'float',
        'km_fin' => 'float',
        'lat_inicio' => 'float',
        'lng_inicio' => 'float',
        'lat_fin' => 'float',
        'lng_fin' => 'float',
        'puntos_json' => 'array',
        'activo' => 'integer',
    ];

    public function gruas()
    {
        return $this->belongsToMany(\App\Models\Grua::class, 'grua_tramo')
            ->withPivot(['desde', 'hasta', 'prioridad', 'activo'])
            ->withTimestamps();
    }

    public function guardiasSct()
    {
        return $this->hasMany(\App\Models\GruaGuardiaSct::class, 'tramo_id');
    }
}
