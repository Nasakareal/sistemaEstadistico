<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hechos extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio_c5i',
        'perito',
        'autorizacion_practico',
        'unidad',
        'hora',
        'fecha',
        'sector',
        'calle',
        'colonia',
        'entre_calles',
        'municipio',
        'tipo_hecho',
        'superficie_via',
        'tiempo',
        'clima',
        'condiciones',
        'control_transito',
        'checaron_antecedentes',
        'causas',
        'colision_camino',
        'situacion',
        'oficio_mp',
        'vehiculos_mp',
        'personas_mp',
    ];

    protected $casts = [
        'checaron_antecedentes' => 'boolean',
    ];

    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'hecho_vehiculo', 'hecho_id', 'vehiculo_id')
                    ->withTimestamps();
    }
}
