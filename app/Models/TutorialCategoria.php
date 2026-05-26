<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialCategoria extends Model
{
    use HasFactory;

    protected $table = 'tutorial_categorias';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function tutoriales()
    {
        return $this->hasMany(Tutorial::class, 'tutorial_categoria_id');
    }
}
