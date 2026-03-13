<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuestaDisposicionPersona extends Model
{
    use HasFactory;

    protected $table = 'puestas_disposicion_personas';

    protected $fillable = [
        'puesta_disposicion_id',
        'nombre_completo',
        'alias',
        'edad',
        'sexo',
        'fecha_nacimiento',
        'curp',
        'rfc',
        'domicilio',
        'calidad',
        'delito_o_motivo',
        'orden_aprehension',
        'mandamiento_judicial',
        'observaciones',
    ];

    protected $casts = [
        'puesta_disposicion_id' => 'integer',
        'edad' => 'integer',
        'fecha_nacimiento' => 'date',
        'orden_aprehension' => 'boolean',
    ];

    public function puestaDisposicion()
    {
        return $this->belongsTo(PuestaDisposicion::class, 'puesta_disposicion_id');
    }
}
