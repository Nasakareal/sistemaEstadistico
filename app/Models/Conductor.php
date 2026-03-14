<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';

    protected $fillable = [
        'client_uuid',
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
        'ocupacion',
        'sexo',
        'tipo_licencia'
    ];

    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'vehiculo_conductor', 'conductor_id', 'vehiculo_id')
                    ->withTimestamps();
    }
}
