<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteCiudadanoSiniestro extends Model
{
    protected $table = 'reportes_ciudadanos_siniestros';

    protected $fillable = [
        'folio',
        'telefono_reportante',
        'ubicacion',
        'latitud',
        'longitud',
        'tipo_siniestro',
        'vehiculos',
        'lesionados',
        'riesgos_observaciones',
        'estatus',
        'reportado_at',
        'notificado_at',
        'resultados_notificacion',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'reportado_at' => 'datetime',
        'notificado_at' => 'datetime',
        'resultados_notificacion' => 'array',
    ];
}
