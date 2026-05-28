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
        'categoria',
        'descuenta',
        'requiere_documento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'descuenta' => 'boolean',
        'requiere_documento' => 'boolean',
    ];

    public function incidencias()
    {
        return $this->hasMany(PersonalIncidencia::class, 'incidencia_tipo_id');
    }
}
