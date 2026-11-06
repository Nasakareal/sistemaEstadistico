<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grua extends Model
{
    use HasFactory;

    protected $table = 'gruas';

    protected $fillable = [
        'nombre',
        'direccion',
        'ubicacion_corralon',
        'telefono',
        'email',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'grua_id');
    }

    public function hechos()
    {
        return $this->hasMany(Hechos::class, 'grua_id');
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'grua_id');
    }

    public function usuarios()
    {
        return $this->hasMany(GruaUsuario::class, 'grua_id');
    }

    public function liberacionesCorralon()
    {
        return $this->hasMany(LiberacionCorralon::class, 'grua_id');
    }

    public function tramos()
    {
        return $this->belongsToMany(Tramo::class, 'grua_tramo', 'grua_id', 'tramo_id')
            ->withPivot(['desde', 'hasta', 'prioridad', 'activo'])
            ->withTimestamps();
    }

    public function guardias()
    {
        return $this->hasMany(GruaGuardia::class, 'grua_id');
    }

    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'unidad_grua', 'grua_id', 'unidad_id')
            ->withPivot('ubicacion_corralon')
            ->withTimestamps();
    }

    public function delegaciones()
    {
        return $this->belongsToMany(Delegacion::class, 'delegacion_grua', 'grua_id', 'delegacion_id')
            ->withPivot('ubicacion_corralon')
            ->withTimestamps();
    }

    public function ubicacionCorralonPara(?int $unidadId, ?int $delegacionId = null): ?string
    {
        if ($unidadId === 2 && $delegacionId) {
            $delegacion = $this->relationLoaded('delegaciones')
                ? $this->delegaciones->firstWhere('id', $delegacionId)
                : $this->delegaciones()->where('delegaciones.id', $delegacionId)->first();

            $ubicacion = trim((string) optional(optional($delegacion)->pivot)->ubicacion_corralon);
            if ($ubicacion !== '') {
                return $ubicacion;
            }
        }

        $unidadConsulta = $unidadId === 5 ? 1 : $unidadId;
        if ($unidadConsulta) {
            $unidad = $this->relationLoaded('unidades')
                ? $this->unidades->firstWhere('id', $unidadConsulta)
                : $this->unidades()->where('unidades.id', $unidadConsulta)->first();

            $ubicacion = trim((string) optional(optional($unidad)->pivot)->ubicacion_corralon);
            if ($ubicacion !== '') {
                return $ubicacion;
            }
        }

        return collect([$this->ubicacion_corralon, $this->direccion])
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => $value !== '');
    }
}
