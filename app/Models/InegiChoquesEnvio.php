<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InegiChoquesEnvio extends Model
{
    use HasFactory;

    protected $table = 'inegi_choques_envios';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'intentos',
        'destinatarios',
        'archivo_nombre',
        'archivo_sha256',
        'total_registros',
        'enviado_at',
        'ultimo_error',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'intentos' => 'integer',
        'destinatarios' => 'array',
        'total_registros' => 'integer',
        'enviado_at' => 'datetime',
    ];

    public function hechos()
    {
        return $this->belongsToMany(Hechos::class, 'inegi_choques_envio_hechos', 'envio_id', 'hecho_id');
    }
}
