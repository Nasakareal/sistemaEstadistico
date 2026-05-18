<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoTipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'clave',
        'nombre',
        'requiere_vigencia',
        'dias_vigencia',
        'sensible',
        'activo',
    ];

    protected $casts = [
        'requiere_vigencia' => 'boolean',
        'dias_vigencia' => 'integer',
        'sensible' => 'boolean',
        'activo' => 'boolean',
    ];
}
