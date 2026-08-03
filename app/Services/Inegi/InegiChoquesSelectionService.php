<?php

namespace App\Services\Inegi;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InegiChoquesSelectionService
{
    public const UNIDAD_DELEGACIONES_ID = 2;

    /**
     * Aplica exactamente la regla usada por el Excel que se envía al INEGI.
     */
    public function aplicarFiltroIncluidos(Builder $query, string $hechosAlias = 'h', string $creatorAlias = 'creator'): Builder
    {
        $origen = "COALESCE({$hechosAlias}.unidad_org_id, {$creatorAlias}.unidad_id)";

        return $query->where(function (Builder $incluidos) use ($hechosAlias, $origen) {
            $incluidos
                ->whereRaw("{$origen} IS NULL")
                ->orWhereRaw("{$origen} <> ?", [self::UNIDAD_DELEGACIONES_ID])
                ->orWhere("{$hechosAlias}.captura_completa", 1)
                ->orWhere(function (Builder $completaPorConteo) use ($hechosAlias) {
                    $completaPorConteo
                        ->whereColumn("{$hechosAlias}.vehiculos_capturados", '>=', "{$hechosAlias}.vehiculos_esperados")
                        ->whereColumn("{$hechosAlias}.conductores_capturados", '>=', "{$hechosAlias}.conductores_esperados")
                        ->whereColumn("{$hechosAlias}.lesionados_capturados", '>=', "{$hechosAlias}.lesionados_esperados");
                });
        });
    }

    public function queryDelegacionesIncluidas(Carbon $desde, Carbon $hasta): Builder
    {
        $query = $this->queryDetalleDelegaciones($desde, $hasta);

        return $query->where(function (Builder $incluidos) {
            $incluidos
                ->where('h.captura_completa', 1)
                ->orWhere(function (Builder $completaPorConteo) {
                    $completaPorConteo
                        ->whereColumn('h.vehiculos_capturados', '>=', 'h.vehiculos_esperados')
                        ->whereColumn('h.conductores_capturados', '>=', 'h.conductores_esperados')
                        ->whereColumn('h.lesionados_capturados', '>=', 'h.lesionados_esperados');
                });
        });
    }

    public function queryDelegacionesPendientes(Carbon $desde, Carbon $hasta): Builder
    {
        return $this->queryDetalleDelegaciones($desde, $hasta)
            ->whereRaw('COALESCE(h.captura_completa, 0) <> 1')
            ->where(function (Builder $pendientes) {
                $pendientes
                    ->whereRaw('COALESCE(h.vehiculos_capturados, 0) < COALESCE(h.vehiculos_esperados, 0)')
                    ->orWhereRaw('COALESCE(h.conductores_capturados, 0) < COALESCE(h.conductores_esperados, 0)')
                    ->orWhereRaw('COALESCE(h.lesionados_capturados, 0) < COALESCE(h.lesionados_esperados, 0)');
            });
    }

    public function queryDelegacionesManifestadas(array $hechoIds): Builder
    {
        $query = $this->queryDetalleDelegacionesSinRango();

        if (empty($hechoIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('h.id', array_values(array_unique(array_map('intval', $hechoIds))));
    }

    private function queryDetalleDelegaciones(Carbon $desde, Carbon $hasta): Builder
    {
        return $this->queryDetalleDelegacionesSinRango()
            ->whereDate('h.fecha', '>=', $desde->toDateString())
            ->whereDate('h.fecha', '<=', $hasta->toDateString());
    }

    private function queryDetalleDelegacionesSinRango(): Builder
    {
        return DB::table('hechos as h')
            ->leftJoin('users as creator_inegi', 'creator_inegi.id', '=', 'h.created_by')
            ->leftJoin('delegaciones as delegacion_inegi', 'delegacion_inegi.id', '=', 'h.delegacion_id')
            ->leftJoin('delegaciones as regional_inegi', 'regional_inegi.id', '=', 'delegacion_inegi.delegacion_padre_id')
            ->whereRaw(
                'COALESCE(h.unidad_org_id, creator_inegi.unidad_id) = ?',
                [self::UNIDAD_DELEGACIONES_ID]
            )
            ->select([
                'h.id',
                'h.folio_c5i',
                'h.fecha',
                'h.hora',
                'h.tipo_hecho',
                'h.municipio',
                'h.calle',
                'h.colonia',
                'h.delegacion_id',
                'h.captura_completa',
                'h.vehiculos_esperados',
                'h.vehiculos_capturados',
                'h.conductores_esperados',
                'h.conductores_capturados',
                'h.lesionados_esperados',
                'h.lesionados_capturados',
                DB::raw("COALESCE(regional_inegi.nombre, delegacion_inegi.nombre, 'SIN REGIONAL') as regional_nombre"),
                DB::raw("COALESCE(delegacion_inegi.nombre, 'SIN DELEGACION ASIGNADA') as delegacion_nombre"),
            ]);
    }
}
