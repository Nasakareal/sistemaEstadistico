<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'marca',
        'modelo',
        'tipo',
        'linea',
        'color',
        'placas',
        'estado_placas',
        'serie',
        'capacidad_personas',
        'tipo_servicio',
        'tarjeta_circulacion_nombre',
        'grua',
        'corralon',
    ];

    public function hechos()
    {
        return $this->belongsToMany(Hechos::class, 'hecho_vehiculo', 'vehiculo_id', 'hecho_id')
                    ->withTimestamps();
    }

    public function conductores()
    {
        return $this->belongsToMany(Conductor::class, 'vehiculo_conductor', 'vehiculo_id', 'conductor_id')
                    ->withTimestamps();
    }
}
