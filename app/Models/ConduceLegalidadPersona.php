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
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'domicilio',
        'sexo',
        'nacionalidad',
        'ocupacion',
        'edad',
        'edad_texto',
        'estado_civil',
        'tipo_licencia',
        'estado_licencia',
        'numero_licencia',
        'vigencia_licencia',
        'permanente',
        'raw_licencia_qr',
        'licencia_punto_infraccion_id',
        'infraccion_codigo',
        'fundamento_legal',
        'edad_aproximada',
        'complexion',
        'estatura',
        'tez',
        'cabello',
        'prenda_superior',
        'color_superior',
        'prenda_inferior',
        'color_inferior',
        'calzado',
        'color_calzado',
        'rasgos_visibles',
        'observaciones',
    ];

    protected $casts = [
        'edad' => 'integer',
        'vigencia_licencia' => 'date',
        'permanente' => 'boolean',
        'rasgos_visibles' => 'array',
    ];

    public function captura()
    {
        return $this->belongsTo(ConduceLegalidadCaptura::class, 'captura_id');
    }

    public function infraccion()
    {
        return $this->belongsTo(LicenciaPuntoInfraccion::class, 'licencia_punto_infraccion_id');
    }
}
