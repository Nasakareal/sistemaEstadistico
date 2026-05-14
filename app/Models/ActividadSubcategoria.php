<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadSubcategoria extends Model
{
    protected $table = 'actividad_subcategorias';

    protected $fillable = [
        'actividad_categoria_id',
        'unidad_id',
        'nombre',
        'slug',
        'activo',
    ];

    public function categoria()
    {
        return $this->belongsTo(ActividadCategoria::class, 'actividad_categoria_id');
    }
}
