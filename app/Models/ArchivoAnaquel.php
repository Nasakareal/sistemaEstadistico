<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoAnaquel extends Model
{
    protected $table = 'archivo_anaqueles';

    protected $fillable = [
        'area_id',
        'codigo',
        'nombre',
        'total_filas',
        'total_columnas',
        'plano_x',
        'plano_y',
        'plano_ancho',
        'plano_alto',
        'rotacion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'total_filas' => 'integer',
        'total_columnas' => 'integer',
        'rotacion' => 'integer',
    ];

    public function area()
    {
        return $this->belongsTo(ArchivoArea::class, 'area_id');
    }

    public function bloques()
    {
        return $this->hasMany(ArchivoBloque::class, 'anaquel_id');
    }
}
