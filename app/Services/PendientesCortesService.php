<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use App\Support\HechoAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PendientesCortesService
{
    public const UNIDAD_SINIESTROS_ID = 1;
    public const UNIDAD_DELEGACIONES_ID = 2;

    public function cortesQuery($usuario, int $unidadId, bool $incluirCortesVacios = false): Builder
    {
        $query = PendientesCorte::query();

        if ($incluirCortesVacios) {
            return $query;
        }

        return $query->whereHas('detalles.hecho', function (Builder $query) use ($usuario, $unidadId) {
            $this->applyHechosUnidadVisibleScope($query, $usuario, $unidadId);
        });
    }

    public function paginateCortes($usuario, int $unidadId, int $perPage = 30, bool $incluirCortesVacios = false)
    {
        return $this->cortesQuery($usuario, $unidadId, $incluirCortesVacios)
            ->orderByDesc('corte_fecha')
            ->paginate($perPage);
    }

    public function detalle(PendientesCorte $corte, $usuario, int $unidadId, bool $incluirCortesVacios = false): array
    {
        $baseQuery = $this->cortesQuery($usuario, $unidadId, $incluirCortesVacios);

        if (!(clone $baseQuery)->whereKey($corte->id)->exists()) {
            return ['visible' => false];
        }

        $prev = (clone $baseQuery)
            ->where('corte_fecha', '<', $corte->corte_fecha)
            ->orderByDesc('corte_fecha')
            ->first();

        $idsPrev = $prev ? $this->idsDeCorte($prev) : [];
        $idsNow = $this->idsDeCorte($corte);
        $idsAll = array_values(array_unique(array_merge($idsPrev, $idsNow)));
        $hechosAll = $this->hechosPorIds($idsAll, $usuario, $unidadId);

        $idsPrevVisibles = $this->idsVisibles($idsPrev, $hechosAll);
        $idsNowVisibles = $this->idsVisibles($idsNow, $hechosAll);

        $resueltos = [];
        $turnados = [];
        $siguen = [];
        $otros = [];

        foreach ($idsPrevVisibles as $id) {
            $h = $hechosAll->get($id);

            if ($h->situacion === 'RESUELTO') {
                $resueltos[] = $h;
            } elseif ($h->situacion === 'TURNADO') {
                $turnados[] = $h;
            } elseif ($h->situacion === 'PENDIENTE') {
                $siguen[] = $h;
            } else {
                $otros[] = $h;
            }
        }

        $setPrev = array_fill_keys($idsPrevVisibles, true);

        $nuevos = collect($idsNowVisibles)
            ->filter(fn ($id) => !isset($setPrev[$id]))
            ->map(fn ($id) => $hechosAll->get($id))
            ->filter()
            ->values();

        return [
            'visible' => true,
            'prev' => $prev,
            'totales' => [
                'previos' => count($idsPrevVisibles),
                'resueltos' => count($resueltos),
                'turnados' => count($turnados),
                'siguen_pendiente' => count($siguen),
                'otros' => count($otros),
                'nuevos_pendientes' => $nuevos->count(),
            ],
            'resueltos' => $resueltos,
            'turnados' => $turnados,
            'siguen' => $siguen,
            'otros' => $otros,
            'nuevos' => $nuevos,
        ];
    }

    public function applyHechosUnidadScope(Builder $query, int $unidadId): void
    {
        $this->applyHechosUnidadesScope($query, [$unidadId]);
    }

    public function applyHechosUnidadesScope(Builder $query, array $unidadIds): void
    {
        $unidadIds = collect($unidadIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($unidadIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function (Builder $scope) use ($unidadIds) {
            $scope->whereIn('unidad_org_id', $unidadIds)
                ->orWhere(function (Builder $legacy) use ($unidadIds) {
                    $legacy->whereNull('unidad_org_id')
                        ->whereHas('creator', function (Builder $creator) use ($unidadIds) {
                            $creator->whereIn('unidad_id', $unidadIds);
                        });
                });
        });
    }

    private function applyHechosUnidadVisibleScope(Builder $query, $usuario, int $unidadId): void
    {
        $this->applyHechosUnidadScope($query, $unidadId);
        HechoAccess::applyVisibilityScope($query, $usuario);
    }

    private function idsDeCorte(PendientesCorte $corte): array
    {
        return PendientesCorteDetalle::where('pendientes_corte_id', $corte->id)
            ->pluck('hecho_id')
            ->unique()
            ->values()
            ->all();
    }

    private function hechosPorIds(array $ids, $usuario, int $unidadId): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $query = Hechos::query()
            ->whereIn('id', $ids)
            ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion']);

        $this->applyHechosUnidadVisibleScope($query, $usuario, $unidadId);

        return $query->get()->keyBy('id');
    }

    private function idsVisibles(array $ids, Collection $hechos): array
    {
        return collect($ids)
            ->filter(fn ($id) => $hechos->has($id))
            ->values()
            ->all();
    }
}
