<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoepraPuntosLicenciasReportService
{
    public function generar(): array
    {
        $porAnio = DB::table('hechos')
            ->selectRaw('YEAR(fecha) AS anio, COUNT(*) AS total')
            ->whereNotNull('fecha')
            ->groupByRaw('YEAR(fecha)')
            ->orderBy('anio')
            ->get();

        $porMes = DB::table('hechos')
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS total")
            ->whereNotNull('fecha')
            ->groupByRaw("DATE_FORMAT(fecha, '%Y-%m')")
            ->orderBy('mes')
            ->get();

        $legacyDisponible = (bool) DB::table('information_schema.tables')
            ->where('table_schema', 'peritos_legacy')
            ->where('table_name', 'accidentest')
            ->exists();

        $legacyPorAnio = collect();
        $legacyPorMes = collect();
        $legacyRango = null;

        if ($legacyDisponible) {
            $legacyRango = DB::table('peritos_legacy.accidentest')
                ->selectRaw('MIN(fecha) AS min_fecha, MAX(fecha) AS max_fecha, COUNT(*) AS total')
                ->whereRaw('COALESCE(borrado, 0) = 0')
                ->whereNotNull('fecha')
                ->where('fecha', '<>', '0000-00-00')
                ->first();

            $legacyPorAnio = DB::table('peritos_legacy.accidentest')
                ->selectRaw('YEAR(fecha) AS anio, COUNT(*) AS total')
                ->whereRaw('COALESCE(borrado, 0) = 0')
                ->whereNotNull('fecha')
                ->where('fecha', '<>', '0000-00-00')
                ->groupByRaw('YEAR(fecha)')
                ->orderBy('anio')
                ->get();

            $legacyPorMes = DB::table('peritos_legacy.accidentest')
                ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS total")
                ->whereRaw('COALESCE(borrado, 0) = 0')
                ->whereNotNull('fecha')
                ->where('fecha', '<>', '0000-00-00')
                ->groupByRaw("DATE_FORMAT(fecha, '%Y-%m')")
                ->orderBy('mes')
                ->get();
        }

        $serieHistoricaAnual = collect($legacyPorAnio)
            ->filter(fn ($row) => (int) $row->anio <= 2025)
            ->map(fn ($row) => ['anio' => (int) $row->anio, 'total' => (int) $row->total, 'fuente' => 'peritos_legacy'])
            ->merge(
                collect($porAnio)->map(fn ($row) => ['anio' => (int) $row->anio, 'total' => (int) $row->total, 'fuente' => 'hechos'])
            )
            ->groupBy('anio')
            ->map(function ($items, $anio) {
                return [
                    'anio' => (int) $anio,
                    'total' => (int) collect($items)->sum('total'),
                    'fuente' => collect($items)->pluck('fuente')->implode('+'),
                ];
            })
            ->sortBy('anio')
            ->values();

        $serieHistoricaMensual = collect($legacyPorMes)
            ->filter(fn ($row) => strcmp((string) $row->mes, '2026-01') < 0)
            ->map(fn ($row) => ['mes' => (string) $row->mes, 'total' => (int) $row->total, 'fuente' => 'peritos_legacy'])
            ->merge(
                collect($porMes)->map(fn ($row) => ['mes' => (string) $row->mes, 'total' => (int) $row->total, 'fuente' => 'hechos'])
            )
            ->groupBy('mes')
            ->map(function ($items, $mes) {
                return [
                    'mes' => (string) $mes,
                    'total' => (int) collect($items)->sum('total'),
                    'fuente' => collect($items)->pluck('fuente')->implode('+'),
                ];
            })
            ->sortBy('mes')
            ->values();

        $porTipo2024 = DB::table('hechos')
            ->selectRaw('UPPER(TRIM(tipo_hecho)) AS tipo, COUNT(*) AS total')
            ->whereBetween('fecha', ['2024-01-01', '2026-12-31'])
            ->groupByRaw('UPPER(TRIM(tipo_hecho))')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $porMunicipio2024 = DB::table('hechos')
            ->selectRaw("COALESCE(NULLIF(UPPER(TRIM(municipio)), ''), 'SIN MUNICIPIO') AS municipio, COUNT(*) AS total")
            ->whereBetween('fecha', ['2024-01-01', '2026-12-31'])
            ->groupByRaw("COALESCE(NULLIF(UPPER(TRIM(municipio)), ''), 'SIN MUNICIPIO')")
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $licenciasConductores = [
            'total' => (int) DB::table('conductores')->count(),
            'con_numero' => $this->countFilled('conductores', 'numero_licencia'),
            'con_tipo' => $this->countFilled('conductores', 'tipo_licencia'),
            'con_estado' => $this->countFilled('conductores', 'estado_licencia'),
            'con_vigencia' => $this->hasColumn('conductores', 'vigencia_licencia')
                ? (int) DB::table('conductores')->whereNotNull('vigencia_licencia')->count()
                : null,
        ];

        $licenciasVehiculos = [
            'total' => (int) DB::table('vehiculos')->count(),
            'con_numero' => $this->countFilled('vehiculos', 'numero_licencia'),
            'con_tipo' => $this->countFilled('vehiculos', 'tipo_licencia'),
            'con_estado' => $this->countFilled('vehiculos', 'estado_licencia'),
            'con_vigencia' => $this->hasColumn('vehiculos', 'vigencia_licencia')
                ? (int) DB::table('vehiculos')->whereNotNull('vigencia_licencia')->count()
                : null,
        ];

        $infraestructura = [
            'constancias_manejo' => $this->hasTable('constancias_manejo') ? (int) DB::table('constancias_manejo')->count() : null,
            'constancia_examen_solicitudes' => $this->hasTable('constancia_examen_solicitudes') ? (int) DB::table('constancia_examen_solicitudes')->count() : null,
            'constancia_preguntas' => $this->hasTable('constancia_preguntas') ? (int) DB::table('constancia_preguntas')->count() : null,
            'hechos' => (int) DB::table('hechos')->count(),
            'conductores' => (int) DB::table('conductores')->count(),
            'vehiculos' => (int) DB::table('vehiculos')->count(),
        ];

        $rango = DB::table('hechos')
            ->selectRaw('MIN(fecha) AS min_fecha, MAX(fecha) AS max_fecha, COUNT(*) AS total')
            ->first();

        return [
            'generado' => Carbon::now('America/Mexico_City')->toIso8601String(),
            'rango' => $rango,
            'por_anio' => $porAnio,
            'por_mes' => $porMes,
            'legacy_disponible' => $legacyDisponible,
            'legacy_rango' => $legacyRango,
            'legacy_por_anio' => $legacyPorAnio,
            'legacy_por_mes' => $legacyPorMes,
            'serie_historica_anual' => $serieHistoricaAnual,
            'serie_historica_mensual' => $serieHistoricaMensual,
            'por_tipo_2024_2026' => $porTipo2024,
            'por_municipio_2024_2026' => $porMunicipio2024,
            'licencias_conductores' => $licenciasConductores,
            'licencias_vehiculos' => $licenciasVehiculos,
            'infraestructura' => $infraestructura,
        ];
    }

    private function countFilled(string $table, string $column): ?int
    {
        if (!$this->hasColumn($table, $column)) {
            return null;
        }

        return (int) DB::table($table)
            ->whereNotNull($column)
            ->whereRaw("TRIM($column) <> ''")
            ->count();
    }

    private function hasTable(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
