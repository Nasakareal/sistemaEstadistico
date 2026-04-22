<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivoPersona extends Model
{
    protected $table = 'operativo_dispositivo_persona';

    protected $fillable = [
        'operativo_dispositivo_id',
        'nombre',
        'tipo_participacion',
        'curp',
        'telefono',
        'domicilio',
        'sexo',
        'ocupacion',
        'edad',
        'tipo_licencia',
        'estado_licencia',
        'vigencia_licencia',
        'numero_licencia',
        'permanente',
        'cinturon',
        'antecedentes',
        'certificado_lesiones',
        'certificado_alcoholemia',
        'aliento_etilico',
        'observaciones',
    ];

    protected $casts = [
        'edad' => 'integer',
        'vigencia_licencia' => 'date',
        'permanente' => 'boolean',
        'cinturon' => 'boolean',
        'antecedentes' => 'boolean',
        'certificado_lesiones' => 'boolean',
        'certificado_alcoholemia' => 'boolean',
        'aliento_etilico' => 'boolean',
    ];

    public function operativo()
    {
        return $this->belongsTo(OperativoDispositivo::class, 'operativo_dispositivo_id');
    }
}
