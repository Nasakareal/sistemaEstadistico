<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destacamento extends Model
{
    use HasFactory;

    protected $table = 'destacamentos';

    protected $fillable = [
        'unidad_id',
        'clave',
        'nombre',
        'municipio',
        'lat',
        'lng',
        'direccion',
        'telefono',
        'responsable',
        'referencia',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'destacamento_id');
    }

    public function operativos()
    {
        return $this->hasMany(Operativo::class, 'destacamento_id');
    }

    public function redApoyos()
    {
        return $this->hasMany(DestacamentoRedApoyo::class, 'destacamento_id');
    }
}
