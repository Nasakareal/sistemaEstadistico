<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FomentoCulturaVialDetalle extends Model
{
    use HasFactory;

    protected $table = 'fomento_cultura_vial_detalles';

    protected $fillable = [
        'actividad_id',
        'nivel_educativo',
        'sector',
        'ninas',
        'ninos',
        'adolescentes_mujeres',
        'adolescentes_hombres',
        'docentes_hombres',
        'docentes_mujeres',
        'hombres',
        'mujeres',
        'total_poblacion_atendida',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }
}
