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
        'fomento_cultura_vial_programa_id',
        'nivel_educativo',
        'sector',
        'programa_nombre',
        'nombre_institucion',
        'domicilio',
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

    protected $casts = [
        'fomento_cultura_vial_programa_id' => 'integer',
        'ninas' => 'integer',
        'ninos' => 'integer',
        'adolescentes_mujeres' => 'integer',
        'adolescentes_hombres' => 'integer',
        'docentes_hombres' => 'integer',
        'docentes_mujeres' => 'integer',
        'hombres' => 'integer',
        'mujeres' => 'integer',
        'total_poblacion_atendida' => 'integer',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function programa()
    {
        return $this->belongsTo(FomentoCulturaVialPrograma::class, 'fomento_cultura_vial_programa_id');
    }
}
