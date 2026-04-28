<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaModulo extends Model
{
    protected $table = 'constancia_modulos';

    protected $fillable = [
        'nombre',
        'tipo',
        'municipio',
        'delegacion_id',
        'unidad_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function constancias()
    {
        return $this->hasMany(ConstanciaManejo::class, 'modulo_id');
    }

    public function folios()
    {
        return $this->hasMany(ConstanciaFolio::class, 'modulo_id');
    }
}
