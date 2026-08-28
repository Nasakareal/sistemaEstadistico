<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadPersona extends Model
{
    use HasFactory;

    protected $table = 'actividad_personas';

    protected $fillable = [
        'actividad_id',
        'vehiculo_id',
        'tipo_participacion',
        'nombre',
        'telefono',
        'domicilio',
        'sexo',
        'nacionalidad',
        'ocupacion',
        'edad',
        'observaciones',
    ];

    protected $casts = [
        'edad' => 'integer',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
