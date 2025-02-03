<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'grua_id',
        'tipo_vehiculo',
        'aseguradora',
        'descripcion',
        'foto_vehiculo',
    ];

    public function grua()
    {
        return $this->belongsTo(Grua::class);
    }
}
