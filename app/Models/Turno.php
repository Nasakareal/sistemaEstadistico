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
        'tipo_rol',
        'ciclo_inicio',
        'trabajo_horas',
        'descanso_horas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ciclo_inicio' => 'datetime',
        'trabajo_horas' => 'integer',
        'descanso_horas' => 'integer',
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
