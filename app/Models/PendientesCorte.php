<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendientesCorte extends Model
{
    protected $table = 'pendientes_cortes';

    protected $fillable = [
        'corte_fecha',
        'generado_by',
        'observaciones',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(PendientesCorteDetalle::class, 'pendientes_corte_id');
    }
}
