<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VialidadDispositivoCatalogo extends Model
{
    use HasFactory;

    protected $table = 'vialidad_dispositivo_catalogos';

    protected $fillable = [
        'unidad_id',
        'nombre',
        'slug',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function dispositivos()
    {
        return $this->hasMany(VialidadDispositivo::class, 'vialidad_dispositivo_catalogo_id');
    }
}
