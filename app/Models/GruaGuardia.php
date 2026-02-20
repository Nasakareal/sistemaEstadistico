<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GruaGuardia extends Model
{
    protected $table = 'grua_guardias';

    protected $fillable = [
        'grua_id','week_start','week_end','activo','notas'
    ];

    public function grua()
    {
        return $this->belongsTo(\App\Models\Grua::class);
    }
}
