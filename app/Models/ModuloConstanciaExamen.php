<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuloConstanciaExamen extends Model
{
    use HasFactory;

    protected $table = 'modulo_constancia_examenes';

    protected $fillable = [
        'modulo_examen_diario_id',
        'user_id',
        'fecha',
        'modulo_nombre',
        'folios_desde',
        'folios_hasta',
        'rango_folios',
        'cantidad_constancias',
        'servicio_publico',
        'automovilista',
        'chofer',
        'motociclista',
        'permiso',
        'hombres',
        'mujeres',
        'aprobados',
        'reprobados',
        'informado_por',
        'tipo_movimiento',
        'observaciones',
        'pdf_path',
        'pdf_nombre',
        'fecha_generacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_generacion' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examenDiario()
    {
        return $this->belongsTo(ModuloExamenDiario::class, 'modulo_examen_diario_id');
    }

    public function detalles()
    {
        return $this->hasMany(ModuloConstanciaExamenDetalle::class, 'modulo_constancia_examen_id');
    }
}
