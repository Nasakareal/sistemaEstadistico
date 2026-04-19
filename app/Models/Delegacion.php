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

    public function users()
    {
        return $this->belongsToMany(User::class, 'delegacion_user')
            ->withPivot(['principal'])
            ->withTimestamps();
    }

    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'delegacion_id');
    }

    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'delegacion_id');
    }

    public function padre()
    {
        return $this->belongsTo(self::class, 'delegacion_padre_id');
    }

    public function hijas()
    {
        return $this->hasMany(self::class, 'delegacion_padre_id');
    }

    public function getNombreConClaveAttribute(): string
    {
        if (!empty($this->clave)) {
            return "{$this->nombre} ({$this->clave})";
        }

        return (string) $this->nombre;
    }

    public function operativos()
    {
        return $this->hasMany(Operativo::class, 'delegacion_id');
    }

    public function gruas()
    {
        return $this->belongsToMany(Grua::class, 'delegacion_grua', 'delegacion_id', 'grua_id')
            ->withTimestamps();
    }
}
