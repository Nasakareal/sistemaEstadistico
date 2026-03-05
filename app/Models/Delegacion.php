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
        'delegacion_padre_id',
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

    public function padre()
    {
        return $this->belongsTo(self::class, 'delegacion_padre_id');
    }

    public function hijas()
    {
        return $this->hasMany(self::class, 'delegacion_padre_id');
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
