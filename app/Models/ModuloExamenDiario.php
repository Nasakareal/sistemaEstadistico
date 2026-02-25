<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuloExamenDiario extends Model
{
    use HasFactory;

    protected $table = 'modulo_examenes_diarios';

    protected $fillable = [
        'fecha',
        'modulo_nombre',
        'servicio_publico',
        'automovilista',
        'chofer',
        'motociclista',
        'permiso',
        'total',
        'hombres',
        'mujeres',
        'aprobados',
        'reprobados',
        'folios',
        'informado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'servicio_publico' => 'integer',
        'automovilista' => 'integer',
        'chofer' => 'integer',
        'motociclista' => 'integer',
        'permiso' => 'integer',
        'total' => 'integer',
        'hombres' => 'integer',
        'mujeres' => 'integer',
        'aprobados' => 'integer',
        'reprobados' => 'integer',
    ];
}
