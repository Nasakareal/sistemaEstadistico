<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaExamen extends Model
{
    protected $table = 'constancia_examenes';

    protected $fillable = [
        'constancia_id',
        'modalidad',
        'calificacion',
        'total_preguntas',
        'aciertos',
        'errores',
        'resultado',
        'capturado_por',
        'fecha_examen',
        'observaciones',
    ];

    protected $casts = [
        'calificacion' => 'decimal:2',
        'fecha_examen' => 'datetime',
    ];

    public function constancia()
    {
        return $this->belongsTo(ConstanciaManejo::class, 'constancia_id');
    }

    public function respuestas()
    {
        return $this->hasMany(ConstanciaExamenRespuesta::class, 'constancia_examen_id');
    }

    public function capturador()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
