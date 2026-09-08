<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaleaEncuesta extends Model
{
    protected $table = 'calea_encuestas';
    protected $guarded = [];
    protected $casts = ['activa' => 'boolean', 'permite_externos' => 'boolean', 'disponible_desde' => 'datetime', 'disponible_hasta' => 'datetime'];

    public function preguntas() { return $this->hasMany(CaleaEncuestaPregunta::class, 'encuesta_id')->orderBy('orden'); }
    public function asignaciones() { return $this->hasMany(CaleaEncuestaAsignacion::class, 'encuesta_id'); }
    public function intentos() { return $this->hasMany(CaleaEncuestaIntento::class, 'encuesta_id'); }
    public function creador() { return $this->belongsTo(User::class, 'creada_por'); }
}
