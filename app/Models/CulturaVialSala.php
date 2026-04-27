<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturaVialSala extends Model
{
    use HasFactory;

    protected $table = 'cultura_vial_salas';

    protected $fillable = [
        'codigo',
        'nombre',
        'juego_slug',
        'estado',
        'instructor_id',
        'cerrada_at',
    ];

    protected $casts = [
        'cerrada_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function participantes()
    {
        return $this->hasMany(CulturaVialParticipante::class, 'sala_id');
    }

    public function intentos()
    {
        return $this->hasMany(CulturaVialIntento::class, 'sala_id');
    }

    public function getAbiertaAttribute(): bool
    {
        return $this->estado === 'abierta';
    }
}
