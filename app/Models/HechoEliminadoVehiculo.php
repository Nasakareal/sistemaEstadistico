<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HechoEliminadoVehiculo extends Model
{
    protected $table = 'hechos_eliminados_vehiculos';

    protected $fillable = [
        'hecho_eliminado_id',
        'vehiculo_id_original',
        'datos_vehiculo',
        'datos_pivote',
        'conductores',
        'servicios',
    ];

    protected $casts = [
        'datos_vehiculo' => 'array',
        'datos_pivote' => 'array',
        'conductores' => 'array',
        'servicios' => 'array',
    ];

    public function hechoEliminado()
    {
        return $this->belongsTo(HechoEliminado::class, 'hecho_eliminado_id');
    }
}
