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
        return $this->hasMany(User::class);
    }

    public function coordinadores()
    {
        return $this->belongsToMany(User::class, 'unidad_user');
    }

    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'unidad_org_id');
    }

    public function patrullas()
    {
        return $this->hasMany(Patrulla::class);
    }
}
