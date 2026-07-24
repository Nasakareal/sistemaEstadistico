<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlcoholimetriaReporteMensual extends Model
{
    use HasFactory;

    protected $table = 'alcoholimetria_reportes_mensuales';

    protected $fillable = [
        'mes',
        'estado',
        'intentos',
        'destinatarios',
        'archivo_nombre',
        'archivo_sha256',
        'resumen',
        'enviado_at',
        'ultimo_error',
    ];

    protected $casts = [
        'mes' => 'date',
        'intentos' => 'integer',
        'destinatarios' => 'array',
        'resumen' => 'array',
        'enviado_at' => 'datetime',
    ];
}
