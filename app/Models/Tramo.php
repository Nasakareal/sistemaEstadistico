<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tramo extends Model
{
    protected $table = 'tramos';

    protected $fillable = [
        'carretera','nombre','km_inicio','km_fin','activo'
    ];

    public function gruas()
    {
        return $this->belongsToMany(\App\Models\Grua::class, 'grua_tramo')
            ->withPivot(['desde','hasta','prioridad','activo'])
            ->withTimestamps();
    }
}
