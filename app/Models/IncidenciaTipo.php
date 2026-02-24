<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidenciaTipo extends Model
{
    use HasFactory;

    protected $table = 'incidencia_tipos';

    protected $fillable = [
        'nombre',
        'clave',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function incidencias()
    {
        return $this->hasMany(PersonalIncidencia::class, 'incidencia_tipo_id');
    }
}
