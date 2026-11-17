<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoArea extends Model
{
    protected $table = 'archivo_areas';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'descripcion',
        'plano_ancho',
        'plano_alto',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'plano_ancho' => 'decimal:2',
        'plano_alto' => 'decimal:2',
    ];

    public function anaqueles()
    {
        return $this->hasMany(ArchivoAnaquel::class, 'area_id');
    }

    public function librosActuales()
    {
        return $this->hasMany(ArchivoLibro::class, 'area_actual_id');
    }
}
