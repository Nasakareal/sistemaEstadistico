<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GruaGuardiaSct extends Model
{
    protected $table = 'grua_guardias_sct';

    protected $fillable = [
        'grua_id',
        'tramo_id',
        'dia_inicio',
        'dia_fin',
        'prioridad',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dia_inicio' => 'integer',
        'dia_fin' => 'integer',
        'prioridad' => 'integer',
        'grua_id' => 'integer',
        'tramo_id' => 'integer',
    ];

    public function grua()
    {
        return $this->belongsTo(\App\Models\Grua::class, 'grua_id');
    }

    public function tramo()
    {
        return $this->belongsTo(\App\Models\Tramo::class, 'tramo_id');
    }
}
