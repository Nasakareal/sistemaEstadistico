<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadCategoria extends Model
{
    protected $table = 'actividad_categorias';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    public function subcategorias()
    {
        return $this->hasMany(ActividadSubcategoria::class, 'actividad_categoria_id');
    }
}
