<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuestaDisposicionObjeto extends Model
{
    use HasFactory;

    protected $table = 'puestas_disposicion_objetos';

    protected $fillable = [
        'puesta_disposicion_id',
        'tipo_objeto',
        'descripcion',
        'cantidad',
        'unidad_medida',
        'cadena_custodia',
        'observaciones',
    ];

    protected $casts = [
        'puesta_disposicion_id' => 'integer',
        'cantidad' => 'decimal:2',
    ];

    public function puestaDisposicion()
    {
        return $this->belongsTo(PuestaDisposicion::class, 'puesta_disposicion_id');
    }
}
