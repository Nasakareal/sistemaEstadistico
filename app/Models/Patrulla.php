<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patrulla extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_economico',
        'unidad_id',
        'turno_id',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Unidad a la que pertenece la patrulla
    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    // Turno de la patrulla
    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    // Usuarios asignados a esta patrulla
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }
}
