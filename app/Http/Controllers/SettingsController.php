<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Muestra el listado de configuraciones.
     */
    public function index()
    {
        $settings = [];
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Muestra el laboratorio inicial para reconstruir hechos de tránsito en 2D.
     */
    public function reconstructorTransito()
    {
        return view('admin.settings.reconstructor_transito.index');
    }

    /**
     * Muestra el prototipo comercial anonimizado para aseguradoras.
     */
    public function aseguradorasPreview(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasRole('Superadmin'), 403);

        $rows = DB::table('servicios as s')
            ->leftJoin('vehiculos as v', 'v.id', '=', 's.vehiculo_id')
            ->select([
                's.id',
                's.grua_id',
                's.vehiculo_id',
                's.created_at',
                DB::raw("COALESCE(NULLIF(TRIM(s.aseguradora), ''), NULLIF(TRIM(v.aseguradora), '')) as aseguradora"),
            ])
            ->orderBy('s.created_at')
            ->get();

        $sinAseguradora = ['', 'SIN SEGURO', 'NO', 'N/A', 'NA', 'NINGUNO', 'NULL', 'S/D', 'SD', 'SIN DATO'];
        $normalizarAseguradora = static function ($valor) {
            return mb_strtoupper(trim((string) $valor), 'UTF-8');
        };
        $conAseguradora = $rows->filter(function ($row) use ($sinAseguradora, $normalizarAseguradora) {
            return !in_array($normalizarAseguradora($row->aseguradora), $sinAseguradora, true);
        });

        $fechaInicio = $rows->isNotEmpty() ? Carbon::parse($rows->first()->created_at) : null;
        $fechaCorte = $rows->isNotEmpty() ? Carbon::parse($rows->last()->created_at) : null;
        $meses = collect();
        if ($fechaInicio && $fechaCorte) {
            foreach (CarbonPeriod::create($fechaInicio->copy()->startOfMonth(), '1 month', $fechaCorte->copy()->startOfMonth()) as $mes) {
                $clave = $mes->format('Y-m');
                $delMes = $rows->filter(fn ($row) => Carbon::parse($row->created_at)->format('Y-m') === $clave);
                $aseguradosDelMes = $delMes->filter(function ($row) use ($sinAseguradora, $normalizarAseguradora) {
                    return !in_array($normalizarAseguradora($row->aseguradora), $sinAseguradora, true);
                });

                $meses->push([
                    'label' => mb_strtoupper($mes->locale('es')->translatedFormat('M'), 'UTF-8'),
                    'total' => $delMes->count(),
                    'asegurados' => $aseguradosDelMes->count(),
                ]);
            }
        }

        $maximoMensual = max(1, (int) $meses->max('total'));
        $meses = $meses->map(function ($mes) use ($maximoMensual) {
            $mes['altura_total'] = max(3, (int) round(($mes['total'] / $maximoMensual) * 100));
            $mes['altura_asegurados'] = $mes['total'] > 0
                ? max(2, (int) round(($mes['asegurados'] / $maximoMensual) * 100))
                : 0;
            return $mes;
        });

        $aseguradoras = $conAseguradora
            ->groupBy(fn ($row) => $normalizarAseguradora($row->aseguradora))
            ->map(fn ($grupo, $nombre) => ['nombre' => $nombre, 'total' => $grupo->count()])
            ->sortByDesc('total')
            ->values();

        $total = $rows->count();
        $totalAsegurados = $conAseguradora->count();
        $stats = [
            'total' => $total,
            'asegurados' => $totalAsegurados,
            'sin_aseguradora' => $total - $totalAsegurados,
            'porcentaje_asegurados' => $total > 0 ? round(($totalAsegurados / $total) * 100, 1) : 0,
            'vehiculos_vinculados' => $rows->whereNotNull('vehiculo_id')->count(),
            'porcentaje_vinculados' => $total > 0 ? round(($rows->whereNotNull('vehiculo_id')->count() / $total) * 100, 1) : 0,
            'gruas' => $rows->whereNotNull('grua_id')->pluck('grua_id')->unique()->count(),
            'aseguradoras' => $aseguradoras->count(),
            'fecha_inicio' => $fechaInicio,
            'fecha_corte' => $fechaCorte,
            'meses' => $meses,
            'top_aseguradoras' => $aseguradoras->take(5),
        ];

        $legacyStats = $this->aseguradorasLegacyStats();

        return view('admin.settings.aseguradoras_preview.index', compact('stats', 'legacyStats'));
    }

    private function aseguradorasLegacyStats(): ?array
    {
        $disponible = DB::table('information_schema.tables')
            ->where('table_schema', 'peritos_legacy')
            ->whereIn('table_name', ['accidentest', 'vehiculos'])
            ->distinct()
            ->count('table_name') === 2;

        if (!$disponible) {
            return null;
        }

        $hechos = DB::table('peritos_legacy.accidentest')
            ->whereRaw('COALESCE(borrado, 0) = 0');
        $vehiculos = DB::table('peritos_legacy.vehiculos');
        $gruaUtilizada = static function ($query) {
            $query->whereNotNull('grua')
                ->whereRaw("TRIM(grua) <> ''")
                ->whereRaw("UPPER(TRIM(grua)) NOT LIKE '%NO SE UTIL%'")
                ->whereNotIn(DB::raw('UPPER(TRIM(grua))'), ['N/A', 'NA', 'NO', 'NINGUNO', 'NULL', 'S/D', 'SD', 'SIN DATO']);
        };

        $totalHechos = (clone $hechos)->count();
        $georreferenciados = (clone $hechos)
            ->whereNotNull('coordenadas')
            ->whereRaw("TRIM(coordenadas) <> ''")
            ->count();
        $porAnio = (clone $hechos)
            ->selectRaw('YEAR(fecha) as anio, COUNT(*) as total')
            ->whereBetween('fecha', ['2016-01-01', '2025-12-31'])
            ->groupByRaw('YEAR(fecha)')
            ->orderBy('anio')
            ->get();
        $maximoAnual = max(1, (int) $porAnio->max('total'));

        return [
            'hechos' => $totalHechos,
            'vehiculos' => (clone $vehiculos)->count(),
            'asegurados' => (clone $vehiculos)->where('asegurado', 1)->count(),
            'con_grua' => (clone $vehiculos)->where($gruaUtilizada)->count(),
            'asegurados_con_grua' => (clone $vehiculos)->where('asegurado', 1)->where($gruaUtilizada)->count(),
            'georreferenciados' => $georreferenciados,
            'porcentaje_georreferenciado' => $totalHechos > 0 ? round(($georreferenciados / $totalHechos) * 100, 2) : 0,
            'fecha_inicio' => Carbon::parse((clone $hechos)->whereNotNull('fecha')->where('fecha', '<>', '0000-00-00')->min('fecha')),
            'fecha_corte' => Carbon::parse((clone $hechos)->whereNotNull('fecha')->where('fecha', '<>', '0000-00-00')->max('fecha')),
            'por_anio' => $porAnio->map(fn ($row) => [
                'anio' => (int) $row->anio,
                'total' => (int) $row->total,
                'altura' => max(3, (int) round(((int) $row->total / $maximoAnual) * 100)),
            ]),
        ];
    }
}
