<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemaforoNodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_id',
        'ruta',
        'nombre',
        'ubicacion',
        'vialidad_principal',
        'vialidad_transversal',
        'latitud',
        'longitud',
        'configuracion',
        'plan_activo',
        'horario_inicio',
        'horario_fin',
        'horario_estado',
        'estado_operativo',
        'ultimo_contacto_at',
        'activo',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'configuracion' => 'array',
        'ultimo_contacto_at' => 'datetime',
        'activo' => 'boolean',
    ];
}
