<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuestaDisposicionVehiculo extends Model
{
    use HasFactory;

    protected $table = 'puestas_disposicion_vehiculos';

    protected $fillable = [
        'puesta_disposicion_id',
        'vehiculo_id',
        'tipo',
        'marca',
        'submarca',
        'modelo',
        'color',
        'placas',
        'serie',
        'calidad',
        'motivo_relacion',
        'con_reporte_robo',
        'numero_reporte_robo',
        'observaciones',
    ];

    protected $casts = [
        'puesta_disposicion_id' => 'integer',
        'vehiculo_id' => 'integer',
        'con_reporte_robo' => 'boolean',
    ];

    public function puestaDisposicion()
    {
        return $this->belongsTo(PuestaDisposicion::class, 'puesta_disposicion_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
