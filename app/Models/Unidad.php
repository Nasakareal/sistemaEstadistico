<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'nombre',
        'slug',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'unidad_id');
    }

    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'unidad_org_id');
    }

    public function patrullas()
    {
        return $this->hasMany(Patrulla::class, 'unidad_id');
    }

    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'unidad_org_id');
    }

    public function operativosCatalogo()
    {
        return $this->hasMany(OperativoCatalogo::class, 'unidad_id');
    }

    public function operativos()
    {
        return $this->hasMany(Operativo::class, 'unidad_org_id');
    }

    public function gruas()
    {
        return $this->belongsToMany(Grua::class, 'unidad_grua', 'unidad_id', 'grua_id')
            ->withTimestamps();
    }
}
