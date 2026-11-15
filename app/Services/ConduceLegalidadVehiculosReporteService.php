<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConduceLegalidadVehiculosReporteService
{
    private const MUNICIPIO_SQL = "COALESCE(NULLIF(TRIM(c.municipio), ''), NULLIF(TRIM(o.municipio), ''))";
    private const FECHA_SQL = 'COALESCE(c.fecha, o.fecha)';

    public function query(Request $request): Builder
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $request);

        return $query->select([
            'v.id',
            'v.captura_id',
            'c.operativo_id',
            DB::raw(self::FECHA_SQL . ' as fecha_efectiva'),
            'o.fecha as fecha_operativo',
            'c.fecha as fecha_captura',
            'o.nombre as operativo_nombre',
            'o.lugar as operativo_lugar',
            'o.numero as operativo_numero',
            'o.colonia as operativo_colonia',
            'o.municipio as operativo_municipio',
            'c.lugar as captura_lugar',
            'c.municipio as captura_municipio',
            'c.infraccion_codigo as captura_infraccion_codigo',
            'c.fundamento_legal as captura_fundamento_legal',
            'v.marca',
            'v.modelo',
            'v.tipo_general',
            'v.tipo',
            'v.linea',
            'v.color',
            'v.placas',
            'v.estado_placas',
            'v.serie',
            'v.numero_inventario',
            'v.tipo_servicio',
            'v.infraccion_codigo as vehiculo_infraccion_codigo',
            'v.fundamento_legal as vehiculo_fundamento_legal',
            'v.retencion_vehiculo',
            'v.motivo_retencion',
        ]);
    }

    public function resumen(Request $request): array
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $request);

        $municipios = (clone $query)
            ->whereRaw(self::MUNICIPIO_SQL . " IS NOT NULL")
            ->selectRaw(self::MUNICIPIO_SQL . ' as municipio')
            ->distinct()
            ->get()
            ->count();

        return [
            'vehiculos' => (clone $query)->count('v.id'),
            'operativos' => (clone $query)->distinct()->count('o.id'),
            'municipios' => $municipios,
            'retenidos' => (clone $query)->where('v.retencion_vehiculo', true)->count('v.id'),
        ];
    }

    public function opciones(): array
    {
        $query = $this->baseQuery();

        return [
            'municipios' => $this->distinctValues(clone $query, self::MUNICIPIO_SQL),
            'marcas' => $this->distinctValues(clone $query, 'v.marca'),
            'tipos' => $this->distinctValues(clone $query, "COALESCE(NULLIF(TRIM(v.tipo), ''), NULLIF(TRIM(v.tipo_general), ''))"),
            'colores' => $this->distinctValues(clone $query, 'v.color'),
        ];
    }

    public function enriquecer(Collection $rows): Collection
    {
        $capturaIds = $rows->pluck('captura_id')->filter()->unique()->values();
        $motivosCaptura = $this->motivosCaptura($capturaIds);
        $motivosPersonas = $this->motivosPersonas($capturaIds);

        return $rows->map(function ($row) use ($motivosCaptura, $motivosPersonas) {
            $motivos = [];

            foreach ($motivosCaptura->get((int) $row->captura_id, collect()) as $motivo) {
                $motivos[] = $this->textoMotivo($motivo->infraccion_codigo, $motivo->fundamento_legal);
            }

            $motivos[] = $this->textoMotivo($row->captura_infraccion_codigo, $row->captura_fundamento_legal);
            $motivos[] = $this->textoMotivo($row->vehiculo_infraccion_codigo, $row->vehiculo_fundamento_legal);
            $motivos[] = trim((string) $row->motivo_retencion);

            foreach ($motivosPersonas->get((int) $row->captura_id, collect()) as $motivo) {
                $motivos[] = $this->textoMotivo($motivo->infraccion_codigo, $motivo->fundamento_legal);
            }

            $row->fecha_reporte = $row->fecha_efectiva;
            $row->lugar_reporte = $this->lugar($row);
            $row->datos_vehiculo = $this->datosVehiculo($row);
            $row->motivo_reporte = $this->combinarMotivos($motivos);

            return $row;
        });
    }

    public function datosVehiculo(object $row): string
    {
        $tipo = trim((string) ($row->tipo ?? '')) ?: trim((string) ($row->tipo_general ?? ''));
        $campos = [
            'Marca' => $row->marca ?? null,
            'Línea' => $row->linea ?? null,
            'Modelo' => $row->modelo ?? null,
            'Tipo' => $tipo,
            'Color' => $row->color ?? null,
            'Placas' => $row->placas ?? null,
            'Estado placas' => $row->estado_placas ?? null,
            'Serie' => $row->serie ?? null,
            'No. inventario' => $row->numero_inventario ?? null,
            'Servicio' => $row->tipo_servicio ?? null,
        ];

        return collect($campos)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value, $label) => $label . ': ' . trim((string) $value))
            ->implode('; ');
    }

    public function combinarMotivos(array $motivos): string
    {
        $unicos = [];

        foreach ($motivos as $motivo) {
            $motivo = preg_replace('/\s+/u', ' ', trim((string) $motivo));
            if ($motivo === '') {
                continue;
            }

            $key = mb_strtolower($motivo, 'UTF-8');
            if (!array_key_exists($key, $unicos)) {
                $unicos[$key] = $motivo;
            }
        }

        return $unicos ? implode('; ', array_values($unicos)) : 'Sin motivo especificado';
    }

    private function baseQuery(): Builder
    {
        return DB::table('conduce_legalidad_vehiculos as v')
            ->join('conduce_legalidad_capturas as c', 'c.id', '=', 'v.captura_id')
            ->join('conduce_legalidad_operativos as o', 'o.id', '=', 'c.operativo_id')
            ->where('o.tipo_operativo', 'conduce_legalidad')
            ->whereNull('o.deleted_at');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('desde')) {
            $query->whereRaw(self::FECHA_SQL . ' >= ?', [$request->query('desde')]);
        }

        if ($request->filled('hasta')) {
            $query->whereRaw(self::FECHA_SQL . ' <= ?', [$request->query('hasta')]);
        }

        $exactos = [
            'municipio' => self::MUNICIPIO_SQL,
            'marca' => 'v.marca',
            'tipo' => "COALESCE(NULLIF(TRIM(v.tipo), ''), NULLIF(TRIM(v.tipo_general), ''))",
            'color' => 'v.color',
        ];

        foreach ($exactos as $campo => $sql) {
            if ($request->filled($campo)) {
                $query->whereRaw("UPPER({$sql}) = UPPER(?)", [trim((string) $request->query($campo))]);
            }
        }

        foreach (['linea', 'modelo', 'placas', 'serie', 'inventario'] as $campo) {
            if (!$request->filled($campo)) {
                continue;
            }

            $columna = $campo === 'inventario' ? 'v.numero_inventario' : 'v.' . $campo;
            $query->where($columna, 'like', '%' . trim((string) $request->query($campo)) . '%');
        }

        if ($request->query('retenido') === '1') {
            $query->where('v.retencion_vehiculo', true);
        } elseif ($request->query('retenido') === '0') {
            $query->where('v.retencion_vehiculo', false);
        }

        if ($request->filled('motivo')) {
            $this->applyMotivoFilter($query, trim((string) $request->query('motivo')));
        }

        if ($request->filled('q')) {
            $like = '%' . trim((string) $request->query('q')) . '%';
            $query->where(function (Builder $where) use ($like) {
                $where->where('o.nombre', 'like', $like)
                    ->orWhere('o.lugar', 'like', $like)
                    ->orWhere('o.colonia', 'like', $like)
                    ->orWhere('o.municipio', 'like', $like)
                    ->orWhere('c.lugar', 'like', $like)
                    ->orWhere('c.municipio', 'like', $like)
                    ->orWhere('v.marca', 'like', $like)
                    ->orWhere('v.linea', 'like', $like)
                    ->orWhere('v.modelo', 'like', $like)
                    ->orWhere('v.placas', 'like', $like)
                    ->orWhere('v.serie', 'like', $like)
                    ->orWhere('v.numero_inventario', 'like', $like);
            });
        }
    }

    private function applyMotivoFilter(Builder $query, string $motivo): void
    {
        $like = '%' . $motivo . '%';

        $query->where(function (Builder $where) use ($like) {
            $where->where('c.fundamento_legal', 'like', $like)
                ->orWhere('c.infraccion_codigo', 'like', $like)
                ->orWhere('v.fundamento_legal', 'like', $like)
                ->orWhere('v.infraccion_codigo', 'like', $like)
                ->orWhere('v.motivo_retencion', 'like', $like)
                ->orWhereExists(function ($subquery) use ($like) {
                    $subquery->selectRaw('1')
                        ->from('conduce_legalidad_captura_fundamentos as f')
                        ->whereColumn('f.captura_id', 'c.id')
                        ->where(function ($motivos) use ($like) {
                            $motivos->where('f.fundamento_legal', 'like', $like)
                                ->orWhere('f.infraccion_codigo', 'like', $like);
                        });
                })
                ->orWhereExists(function ($subquery) use ($like) {
                    $subquery->selectRaw('1')
                        ->from('conduce_legalidad_personas as p')
                        ->whereColumn('p.captura_id', 'c.id')
                        ->where(function ($motivos) use ($like) {
                            $motivos->where('p.fundamento_legal', 'like', $like)
                                ->orWhere('p.infraccion_codigo', 'like', $like);
                        });
                });
        });
    }

    private function distinctValues(Builder $query, string $expression): Collection
    {
        return $query
            ->whereRaw("{$expression} IS NOT NULL")
            ->whereRaw("TRIM({$expression}) <> ''")
            ->selectRaw("{$expression} as valor")
            ->distinct()
            ->orderBy('valor')
            ->pluck('valor');
    }

    private function motivosCaptura(Collection $capturaIds): Collection
    {
        if ($capturaIds->isEmpty() || !Schema::hasTable('conduce_legalidad_captura_fundamentos')) {
            return collect();
        }

        return DB::table('conduce_legalidad_captura_fundamentos')
            ->whereIn('captura_id', $capturaIds)
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['captura_id', 'infraccion_codigo', 'fundamento_legal'])
            ->groupBy('captura_id');
    }

    private function motivosPersonas(Collection $capturaIds): Collection
    {
        if ($capturaIds->isEmpty()) {
            return collect();
        }

        return DB::table('conduce_legalidad_personas')
            ->whereIn('captura_id', $capturaIds)
            ->where(function ($query) {
                $query->whereNotNull('fundamento_legal')->orWhereNotNull('infraccion_codigo');
            })
            ->orderBy('id')
            ->get(['captura_id', 'infraccion_codigo', 'fundamento_legal'])
            ->groupBy('captura_id');
    }

    private function textoMotivo($codigo, $fundamento): string
    {
        return trim((string) $fundamento) ?: trim((string) $codigo);
    }

    private function lugar(object $row): string
    {
        $lugar = trim((string) ($row->captura_lugar ?? '')) ?: trim((string) ($row->operativo_lugar ?? ''));
        $numero = trim((string) ($row->operativo_numero ?? ''));
        $colonia = trim((string) ($row->operativo_colonia ?? ''));
        $municipio = trim((string) ($row->captura_municipio ?? '')) ?: trim((string) ($row->operativo_municipio ?? ''));

        if ($numero !== '' && $lugar !== '' && stripos($lugar, $numero) === false) {
            $lugar .= ' ' . $numero;
        }

        $partes = array_filter([
            $lugar,
            $colonia !== '' ? 'Col. ' . $colonia : null,
            $municipio,
        ], fn ($value) => trim((string) $value) !== '');

        return $partes ? implode(', ', $partes) : 'Ubicación no especificada';
    }
}
