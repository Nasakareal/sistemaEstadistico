<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VialidadDispositivoDetalle extends Model
{
    use HasFactory;

    protected $table = 'vialidad_dispositivo_detalles';

    protected $fillable = [
        'vialidad_dispositivo_id',
        'orden',
        'tipo',
        'titulo',
        'contenido',
        'ubicacion',
        'hora',
    ];

    protected $casts = [
        'orden' => 'integer',
        'hora' => 'datetime:H:i:s',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(VialidadDispositivo::class, 'vialidad_dispositivo_id');
    }
}
