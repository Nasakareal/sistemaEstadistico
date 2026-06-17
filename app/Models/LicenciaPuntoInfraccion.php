<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoInfraccion extends Model
{
    protected $table = 'licencia_punto_infracciones';

    protected $fillable = [
        'codigo',
        'nombre',
        'puntos',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'puntos' => 'integer',
        'activa' => 'boolean',
    ];

    public function movimientos()
    {
        return $this->hasMany(LicenciaPuntoMovimiento::class, 'infraccion_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }
}
