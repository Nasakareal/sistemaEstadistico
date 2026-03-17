<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivoCatalogo extends Model
{
    protected $table = 'operativo_dispositivo_catalogos';

    protected $fillable = [
        'unidad_id',
        'nombre',
        'slug',
        'activo',
        'orden',
    ];

    public function dispositivos()
    {
        return $this->hasMany(OperativoDispositivo::class, 'operativo_dispositivo_catalogo_id');
    }
}
