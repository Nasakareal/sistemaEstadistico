<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivoFoto extends Model
{
    protected $table = 'operativo_dispositivo_fotos';

    protected $fillable = [
        'client_uuid',
        'operativo_dispositivo_id',
        'ruta',
        'nombre_original',
        'mime_type',
        'peso',
        'observaciones',
        'sync_status',
        'sync_error',
        'synced_at',
        'orden',
        'es_portada',
        'caption',
        'lat',
        'lng',
        'tomada_en',
        'incluida_en_compartido',
        'created_by',
    ];

    protected $casts = [
        'peso' => 'integer',
        'orden' => 'integer',
        'es_portada' => 'boolean',
        'incluida_en_compartido' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'tomada_en' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(OperativoDispositivo::class, 'operativo_dispositivo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
