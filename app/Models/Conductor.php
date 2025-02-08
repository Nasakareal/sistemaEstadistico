<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';

    protected $fillable = [
        'nombre',
        'edad',
        'domicilio',
        'cinturon',
        'antecedentes',
        'certificado_lesiones',
        'certificado_alcoholemia',
        'aliento_etilico',
        'estado_licencia',
        'vigencia_licencia',
        'numero_licencia',
        'tipo_licencia'
    ];

    // Relación muchos a muchos con Vehiculos
    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'vehiculo_conductor', 'conductor_id', 'vehiculo_id')
                    ->withTimestamps();
    }
}
