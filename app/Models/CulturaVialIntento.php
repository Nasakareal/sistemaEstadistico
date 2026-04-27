<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturaVialIntento extends Model
{
    use HasFactory;

    protected $table = 'cultura_vial_intentos';

    protected $fillable = [
        'sala_id',
        'participante_id',
        'juego_slug',
        'puntaje',
        'aciertos',
        'errores',
        'duracion_segundos',
        'decisiones_json',
        'terminado_at',
    ];

    protected $casts = [
        'puntaje' => 'integer',
        'aciertos' => 'integer',
        'errores' => 'integer',
        'duracion_segundos' => 'integer',
        'decisiones_json' => 'array',
        'terminado_at' => 'datetime',
    ];

    public function sala()
    {
        return $this->belongsTo(CulturaVialSala::class, 'sala_id');
    }

    public function participante()
    {
        return $this->belongsTo(CulturaVialParticipante::class, 'participante_id');
    }
}
