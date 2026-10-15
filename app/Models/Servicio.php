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
        'realizo_maniobras',
        'maniobras_descripcion',
        'observaciones_grua',
        'actualizado_por_grua_usuario_id',
        'actualizado_por_grua_at',
        'created_at',
    ];

    protected $casts = [
        'realizo_maniobras' => 'boolean',
        'actualizado_por_grua_at' => 'datetime',
    ];

    public function grua()
    {
        return $this->belongsTo(Grua::class, 'grua_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function actualizadoPorGruaUsuario()
    {
        return $this->belongsTo(GruaUsuario::class, 'actualizado_por_grua_usuario_id');
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
