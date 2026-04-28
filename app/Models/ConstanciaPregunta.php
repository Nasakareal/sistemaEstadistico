<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaPregunta extends Model
{
    protected $table = 'constancia_preguntas';

    protected $fillable = [
        'pregunta',
        'tipo_licencia',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function respuestas()
    {
        return $this->hasMany(ConstanciaRespuesta::class, 'pregunta_id');
    }

    public function respuestasExamen()
    {
        return $this->hasMany(ConstanciaExamenRespuesta::class, 'pregunta_id');
    }
}
