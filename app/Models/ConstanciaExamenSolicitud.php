<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaExamenSolicitud extends Model
{
    protected $table = 'constancia_examen_solicitudes';

    protected $fillable = [
        'folio_examen',
        'token',
        'modulo_id',
        'delegacion_id',
        'user_id',
        'constancia_id',
        'nombre_solicitante',
        'sexo',
        'curp',
        'telefono',
        'tipo_licencia',
        'modalidad',
        'estatus',
        'calificacion',
        'total_preguntas',
        'aciertos',
        'errores',
        'fecha_examen',
        'token_expira',
        'observaciones',
    ];

    protected $casts = [
        'calificacion' => 'decimal:2',
        'fecha_examen' => 'datetime',
        'token_expira' => 'datetime',
    ];

    public function getFolioAttribute(): string
    {
        return $this->folio_examen;
    }

    public function modulo()
    {
        return $this->belongsTo(ConstanciaModulo::class, 'modulo_id');
    }

    public function constancia()
    {
        return $this->belongsTo(ConstanciaManejo::class, 'constancia_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
