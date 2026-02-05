<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendientesCorteDetalle extends Model
{
    protected $table = 'pendientes_corte_detalles';

    protected $fillable = [
        'pendientes_corte_id',
        'hecho_id',
        'situacion_en_corte',
    ];
}
