<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FomentoCulturaVialPrograma extends Model
{
    use HasFactory;

    protected $table = 'fomento_cultura_vial_programas';

    protected $fillable = [
        'actividad_subcategoria_id',
        'nombre',
        'slug',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function subcategoria()
    {
        return $this->belongsTo(ActividadSubcategoria::class, 'actividad_subcategoria_id');
    }

    public function detalles()
    {
        return $this->hasMany(FomentoCulturaVialDetalle::class, 'fomento_cultura_vial_programa_id');
    }
}
