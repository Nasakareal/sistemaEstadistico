<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grua extends Model
{
    use HasFactory;

    protected $table = 'gruas';

    protected $fillable = [
        'nombre',
        'direccion',
        'ubicacion_corralon',
        'telefono',
        'email',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'grua_id');
    }

    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'grua_id');
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'grua_id');
    }

    public function usuarios()
    {
        return $this->hasMany(GruaUsuario::class, 'grua_id');
    }

    public function liberacionesCorralon()
    {
        return $this->hasMany(LiberacionCorralon::class, 'grua_id');
    }

    public function tramos()
    {
        return $this->belongsToMany(Tramo::class, 'grua_tramo', 'grua_id', 'tramo_id')
            ->withPivot(['desde', 'hasta', 'prioridad', 'activo'])
            ->withTimestamps();
    }

    public function guardias()
    {
        return $this->hasMany(GruaGuardia::class, 'grua_id');
    }

    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'unidad_grua', 'grua_id', 'unidad_id')
            ->withTimestamps();
    }

    public function delegaciones()
    {
        return $this->belongsToMany(Delegacion::class, 'delegacion_grua', 'grua_id', 'delegacion_id')
            ->withTimestamps();
    }
}
