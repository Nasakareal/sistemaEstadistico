<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuloConstanciaExamenDetalle extends Model
{
    use HasFactory;

    protected $table = 'modulo_constancia_examenes_detalles';

    protected $fillable = [
        'modulo_constancia_examen_id',
        'folio',
        'tipo_licencia',
        'estatus',
        'observaciones',
    ];

    public function constancia()
    {
        return $this->belongsTo(ModuloConstanciaExamen::class, 'modulo_constancia_examen_id');
    }
}
