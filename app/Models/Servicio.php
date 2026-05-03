<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'grua_id',
        'vehiculo_id',
        'unidad_id',
        'delegacion_id',
        'tipo_vehiculo',
        'aseguradora',
        'descripcion',
        'foto_vehiculo',
        'created_at',
    ];

    public function grua()
    {
        return $this->belongsTo(Grua::class, 'grua_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function scopeConOrigenVinculado($query)
    {
        return $query->whereHas('vehiculo', function ($vehiculo) {
            $vehiculo->where(function ($origen) {
                $origen->whereHas('hechos')
                    ->orWhereHas('actividades')
                    ->orWhereHas('operativoDispositivos')
                    ->orWhereHas('puestasDisposicionVehiculos');
            });
        });
    }
}
