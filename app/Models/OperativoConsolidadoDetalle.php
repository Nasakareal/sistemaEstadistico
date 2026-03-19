<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoConsolidadoDetalle extends Model
{
    protected $table = 'operativo_consolidado_detalles';

    protected $fillable = [
        'operativo_consolidado_id',
        'operativo_dispositivo_id',
        'operativo_dispositivo_catalogo_id',
        'fecha',
        'hora',
        'lugar',
        'cantidad',
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'estado_fuerza_participante',
        'kilometros_recorridos',
        'crps_participantes',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'kilometros_recorridos' => 'decimal:2',
    ];

    public function consolidado()
    {
        return $this->belongsTo(OperativoConsolidado::class, 'operativo_consolidado_id');
    }

    public function dispositivo()
    {
        return $this->belongsTo(OperativoDispositivo::class, 'operativo_dispositivo_id');
    }

    public function catalogo()
    {
        return $this->belongsTo(OperativoDispositivoCatalogo::class, 'operativo_dispositivo_catalogo_id');
    }
}
