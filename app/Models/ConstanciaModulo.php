<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConstanciaModulo extends Model
{
    protected $table = 'constancia_modulos';

    protected $fillable = [
        'nombre',
        'tipo',
        'municipio',
        'delegacion_id',
        'unidad_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function constancias()
    {
        return $this->hasMany(ConstanciaManejo::class, 'modulo_id');
    }

    public function folios()
    {
        return $this->hasMany(ConstanciaFolio::class, 'modulo_id');
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'constancia_modulo_id');
    }

    public function scopePermitidosPara($query, ?User $usuario)
    {
        $query->where('activo', true);

        if (!$usuario) {
            return $query->whereRaw('1 = 0');
        }

        if ($usuario->isSuperadmin()) {
            return $query;
        }

        if ((int) ($usuario->unidad_id ?? 0) === 1) {
            $moduloId = (int) ($usuario->constancia_modulo_id ?? 0);

            return $moduloId > 0
                ? $query->where('tipo', 'SINIESTROS')->whereKey($moduloId)
                : $query->whereRaw('1 = 0');
        }

        if ((int) ($usuario->unidad_id ?? 0) !== 2) {
            return $query->whereRaw('1 = 0');
        }

        $delegacionIds = [(int) ($usuario->delegacion_id ?? 0)];

        try {
            $delegacionIds = array_merge(
                $delegacionIds,
                DB::table('delegacion_user')
                    ->where('user_id', $usuario->id)
                    ->pluck('delegacion_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        } catch (\Throwable $e) {
            // La delegacion principal basta en instalaciones sin pivote sincronizado.
        }

        $delegacionIds = array_values(array_unique(array_filter($delegacionIds)));

        return count($delegacionIds) > 0
            ? $query->where('tipo', 'DELEGACION')->whereIn('delegacion_id', $delegacionIds)
            : $query->whereRaw('1 = 0');
    }
}
