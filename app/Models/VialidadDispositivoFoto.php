<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VialidadDispositivoFoto extends Model
{
    use HasFactory;

    protected $table = 'vialidad_dispositivo_fotos';

    protected $fillable = [
        'vialidad_dispositivo_id',
        'created_by',
        'ruta',
        'nombre_original',
        'orden',
        'portada',
        'included_in_share',
        'lat',
        'lng',
    ];

    protected $casts = [
        'orden' => 'integer',
        'portada' => 'boolean',
        'included_in_share' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(VialidadDispositivo::class, 'vialidad_dispositivo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
