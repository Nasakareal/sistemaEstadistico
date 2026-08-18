<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComunicacionAdjunto extends Model
{
    use HasFactory;

    protected $table = 'comunicacion_adjuntos';

    protected $fillable = [
        'comunicacion_id',
        'tipo',
        'disk',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano_bytes',
        'ancho',
        'alto',
        'orden',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'orden' => 'integer',
    ];

    public function comunicacion()
    {
        return $this->belongsTo(
            Comunicacion::class,
            'comunicacion_id'
        );
    }

    public function esImagen()
    {
        return $this->tipo === 'imagen';
    }
}
