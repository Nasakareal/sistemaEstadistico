<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Turno extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Usuarios asignados a este turno
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    // Patrullas que operan en este turno
    public function patrullas()
    {
        return $this->hasMany(Patrulla::class);
    }
}
