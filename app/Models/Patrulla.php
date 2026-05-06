<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patrulla extends Model
{
    use HasFactory;

    protected $table = 'patrullas';

    protected $fillable = [
        'numero_economico',
        'unidad_id',
        'turno_id',
        'activa',
        'tipo',
        'marca',
        'linea',
        'modelo',
        'placas',
        'serie',
        'color',
        'no_motor',
        'observaciones',
        'foto',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'unidad_id' => 'integer',
        'turno_id' => 'integer',
        'modelo' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }

    public function turno()
    {
        return $this->belongsTo(\App\Models\Turno::class, 'turno_id');
    }

    public function usuarios()
    {
        return $this->hasMany(\App\Models\User::class, 'patrulla_id');
    }

    public function personal()
    {
        return $this->hasMany(\App\Models\Personal::class, 'patrulla_id');
    }

    public function kilometrajes()
    {
        return $this->hasMany(\App\Models\PatrullaKilometraje::class, 'patrulla_id');
    }

    public function fotos()
    {
        return $this->hasMany(\App\Models\PatrullaFoto::class, 'patrulla_id');
    }

    public function getDescripcionVehiculoAttribute()
    {
        return trim("{$this->marca} {$this->linea} {$this->modelo}");
    }

    public function getEtiquetaCompletaAttribute()
    {
        return trim("{$this->numero_economico} - {$this->descripcion_vehiculo}");
    }

    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }
}
