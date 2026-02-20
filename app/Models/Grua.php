<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grua extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function hechos()
    {
        return $this->hasMany(Hecho::class);
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }

    public function tramos()
    {
        return $this->belongsToMany(\App\Models\Tramo::class, 'grua_tramo')
            ->withPivot(['desde','hasta','prioridad','activo'])
            ->withTimestamps();
    }

    public function guardias()
    {
        return $this->hasMany(\App\Models\GruaGuardia::class);
    }
}
