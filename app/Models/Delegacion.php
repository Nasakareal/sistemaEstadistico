<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delegacion extends Model
{
    use HasFactory;

    protected $table = 'delegaciones';

    protected $fillable = [
        'clave',
        'nombre',
        'municipio',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /* =====================================================
     | RELACIONES
     ===================================================== */

    /**
     * Usuarios asignados a esta delegación (pivote)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'delegacion_user')
            ->withPivot(['principal'])
            ->withTimestamps();
    }

    /**
     * Hechos asociados a esta delegación
     */
    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'delegacion_id');
    }

    /* =====================================================
     | HELPERS
     ===================================================== */

    public function getNombreConClaveAttribute(): string
    {
        if (!empty($this->clave)) {
            return "{$this->nombre} ({$this->clave})";
        }

        return (string) $this->nombre;
    }
}
