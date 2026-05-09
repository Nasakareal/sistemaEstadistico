<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendientesCorteDetalle extends Model
{
    protected $table = 'pendientes_corte_detalles';

    protected $fillable = [
        'pendientes_corte_id',
        'hecho_id',
        'situacion_en_corte',
    ];

    public function corte(): BelongsTo
    {
        return $this->belongsTo(PendientesCorte::class, 'pendientes_corte_id');
    }

    public function hecho(): BelongsTo
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }
}
