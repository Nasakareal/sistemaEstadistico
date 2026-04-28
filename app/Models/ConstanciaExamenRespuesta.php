<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaExamenRespuesta extends Model
{
    protected $table = 'constancia_examen_respuestas';

    protected $fillable = [
        'constancia_examen_id',
        'pregunta_id',
        'respuesta_id',
        'es_correcta',
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
    ];

    public function examen()
    {
        return $this->belongsTo(ConstanciaExamen::class, 'constancia_examen_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(ConstanciaPregunta::class, 'pregunta_id');
    }

    public function respuesta()
    {
        return $this->belongsTo(ConstanciaRespuesta::class, 'respuesta_id');
    }
}
