<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperativoCatalogo extends Model
{
    use HasFactory;

    protected $table = 'operativo_catalogos';

    protected $fillable = [
        'unidad_id',
        'nombre',
        'slug',
        'tipo',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function operativos()
    {
        return $this->hasMany(Operativo::class, 'operativo_catalogo_id');
    }
}
