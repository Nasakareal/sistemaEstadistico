<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dictamen extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_dictamen',
        'anio',
        'nombre_policia',
        'nombre_mp',
        'area',
        'archivo_dictamen',
    ];

    protected $table = 'dictamens';
}
