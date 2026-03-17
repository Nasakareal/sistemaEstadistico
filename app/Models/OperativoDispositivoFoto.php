<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivoFoto extends Model
{
    protected $table = 'operativo_dispositivo_fotos';

    protected $fillable = [
        'operativo_dispositivo_id',
        'ruta',
        'nombre_original',
        'mime_type',
        'peso',
        'observaciones',
        'created_by',
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
