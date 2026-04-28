<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaFolio extends Model
{
    protected $table = 'constancia_folios';

    protected $fillable = [
        'prefijo',
        'numero',
        'folio',
        'origen',
        'modulo_id',
        'delegacion_id',
        'constancia_id',
        'estatus',
    ];

    public function modulo()
    {
        return $this->belongsTo(ConstanciaModulo::class, 'modulo_id');
    }

    public function constancia()
    {
        return $this->belongsTo(ConstanciaManejo::class, 'constancia_id');
    }
}
