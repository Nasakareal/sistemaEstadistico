<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendientesCorte extends Model
{
    protected $table = 'pendientes_cortes';

    protected $fillable = [
        'corte_fecha',
        'generado_by',
        'observaciones',
    ];
}
