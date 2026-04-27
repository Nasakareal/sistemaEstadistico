<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturaVialParticipante extends Model
{
    use HasFactory;

    protected $table = 'cultura_vial_participantes';

    protected $fillable = [
        'sala_id',
        'nombre',
        'join_token',
        'mejor_puntaje',
        'intentos',
        'ultimo_intento_at',
    ];

    protected $casts = [
        'mejor_puntaje' => 'integer',
        'intentos' => 'integer',
        'ultimo_intento_at' => 'datetime',
    ];

    public function sala()
    {
        return $this->belongsTo(CulturaVialSala::class, 'sala_id');
    }

    public function intentos()
    {
        return $this->hasMany(CulturaVialIntento::class, 'participante_id');
    }
}
