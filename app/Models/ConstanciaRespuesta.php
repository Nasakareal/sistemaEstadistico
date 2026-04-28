<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaRespuesta extends Model
{
    protected $table = 'constancia_respuestas';

    protected $fillable = [
        'pregunta_id',
        'respuesta',
        'es_correcta',
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
    ];

    public function pregunta()
    {
        return $this->belongsTo(ConstanciaPregunta::class, 'pregunta_id');
    }

    public function respuestasExamen()
    {
        return $this->hasMany(ConstanciaExamenRespuesta::class, 'respuesta_id');
    }
}
