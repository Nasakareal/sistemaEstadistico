<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuestaDisposicionFoto extends Model
{
    protected $table = 'puestas_disposicion_fotos';

    protected $fillable = [
        'puesta_disposicion_id',
        'ruta',
        'orden',
        'created_by',
    ];

    protected $casts = [
        'puesta_disposicion_id' => 'integer',
        'orden' => 'integer',
        'created_by' => 'integer',
    ];

    public function puestaDisposicion()
    {
        return $this->belongsTo(PuestaDisposicion::class, 'puesta_disposicion_id');
    }
}
