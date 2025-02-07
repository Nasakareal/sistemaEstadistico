<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oficio extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_oficio',
        'descripcion',
        'pdf_path',
        'fotos',
    ];

    protected $casts = [
        'fotos' => 'array',
    ];
}
