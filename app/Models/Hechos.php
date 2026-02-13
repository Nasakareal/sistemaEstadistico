<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hechos extends Model
{
    use HasFactory;

    protected $table = 'hechos';

    protected $fillable = [
        'folio_c5i',
        'perito',
        'autorizacion_practico',
        'unidad',
        'unidad_org_id',
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
        'foto_lugar',
        'foto_situacion',
        'delegacion_id',

        'lat',
        'lng',
        'calidad_geo',
        'nota_geo',
        'fuente_ubicacion',
        'ubicacion_formateada',
        'place_id',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'checaron_antecedentes' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function vehiculos(): BelongsToMany
    {
        return $this->belongsToMany(Vehiculo::class, 'hecho_vehiculo', 'hecho_id', 'vehiculo_id')
            ->withTimestamps();
    }

    public function lesionados(): HasMany
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

    public function unidadOrganizacional(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_org_id');
    }

    public function delegacion(): BelongsTo
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function dictamen(): HasOne
    {
        return $this->hasOne(Dictamen::class, 'hecho_id');
    }
}
