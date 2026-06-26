<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadPersona extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_personas';

    protected $fillable = [
        'captura_id',
        'nombre',
        'telefono',
        'domicilio',
        'sexo',
        'ocupacion',
        'edad',
        'tipo_licencia',
        'estado_licencia',
        'numero_licencia',
        'vigencia_licencia',
        'permanente',
        'raw_licencia_qr',
        'observaciones',
    ];

    protected $casts = [
        'edad' => 'integer',
        'vigencia_licencia' => 'date',
        'permanente' => 'boolean',
    ];

    public function captura()
    {
        return $this->belongsTo(ConduceLegalidadCaptura::class, 'captura_id');
    }
}
