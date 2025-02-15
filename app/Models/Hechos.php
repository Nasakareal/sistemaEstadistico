<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'danos_patrimoniales',
        'propiedades_afectadas',
        'monto_danos_patrimoniales',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'checaron_antecedentes' => 'boolean',
    ];

    public function vehiculos()
    {
        return $this->belongsToMany(Vehiculo::class, 'hecho_vehiculo', 'hecho_id', 'vehiculo_id')
                    ->withTimestamps();
    }

    public function lesionados()
    {
        return $this->hasMany(Lesionado::class, 'hecho_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
