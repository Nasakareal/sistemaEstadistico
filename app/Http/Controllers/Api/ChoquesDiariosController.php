<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChoquesDiariosController extends Controller
{
    private const LEGACY_PERITOS_DATABASE = 'peritos_legacy';

    public function index(Request $request)
    {
        $fecha = $request->get('fecha', now('America/Mexico_City')->toDateString());
        return $this->respuestaPorFecha($fecha);
    }

    public function hoy()
    {
        return $this->respuestaPorFecha(now('America/Mexico_City')->toDateString());
    }

    public function porFecha($fecha)
    {
        return $this->respuestaPorFecha($fecha);
    }

    public function rango(Request $request)
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        if (!$desde || !$hasta) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar desde y hasta en formato YYYY-MM-DD.'
            ], 422);
        }

        $hechos = $this->queryHechos()
            ->whereBetween('h.fecha', [$desde, $hasta])
            ->orderBy('h.fecha')
            ->orderBy('h.hora')
            ->get();

        return response()->json([
            'success' => true,
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => $hechos->count(),
            'choques' => $hechos->map(fn($h) => $this->formatearHecho($h))->values()
        ]);
    }

    public function show($hecho)
    {
        $registro = $this->queryHechos()
            ->where('h.id', $hecho)
            ->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Hecho no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'choque' => $this->formatearHecho($registro)
        ]);
    }

    public function eliminadosPorFecha($fecha)
    {
        return response()->json([
            'success' => true,
            'fecha' => $fecha,
            'total' => 0,
            'eliminados' => []
        ]);
    }

    public function eliminadosRango(Request $request)
    {
        return response()->json([
            'success' => true,
            'desde' => $request->get('desde'),
            'hasta' => $request->get('hasta'),
            'total' => 0,
            'eliminados' => []
        ]);
    }

    private function respuestaPorFecha($fecha)
    {
        $hechos = $this->queryHechos()
            ->whereDate('h.fecha', $fecha)
            ->orderBy('h.hora')
            ->get();

        return response()->json([
            'success' => true,
            'fecha' => $fecha,
            'total' => $hechos->count(),
            'choques' => $hechos->map(fn($h) => $this->formatearHecho($h))->values()
        ]);
    }

    private function queryHechos()
    {
        $query = DB::table('hechos as h')
            ->leftJoin('users as creator', 'creator.id', '=', 'h.created_by')
            ->where(function ($query) {
                $query->whereRaw('COALESCE(h.unidad_org_id, creator.unidad_id) IS NULL')
                    ->orWhereRaw('COALESCE(h.unidad_org_id, creator.unidad_id) <> ?', [2])
                    ->orWhere('h.captura_completa', 1)
                    ->orWhere(function ($completaPorConteo) {
                        $completaPorConteo
                            ->whereColumn('h.vehiculos_capturados', '>=', 'h.vehiculos_esperados')
                            ->whereColumn('h.conductores_capturados', '>=', 'h.conductores_esperados')
                            ->whereColumn('h.lesionados_capturados', '>=', 'h.lesionados_esperados');
                    });
            });

        if ($this->legacyAccidentestDisponible()) {
            if ($this->legacyMapDisponible()) {
                $query->leftJoin('legacy_peritos_import_hechos as legacy_map', 'legacy_map.new_hecho_id', '=', 'h.id');
            }

            $legacyId = $this->legacyMapDisponible()
                ? DB::raw('COALESCE(legacy_map.old_hecho_id, h.id)')
                : 'h.id';

            $query->leftJoin(self::LEGACY_PERITOS_DATABASE . '.accidentest as legacy_accidente', function ($join) use ($legacyId) {
                $join->on('legacy_accidente.id_accidentes', '=', $legacyId)
                    ->where('h.fuente_ubicacion', '=', 'legacy_peritos');
            });
        }

        $selects = [
                'h.id',
                'h.folio_c5i',
                'h.fecha',
                'h.hora',
                'h.calle',
                'h.colonia',
                'h.entre_calles',
                'h.municipio',
                'h.lat',
                'h.lng',
                'h.tipo_hecho',
                'h.superficie_via',
                'h.tiempo',
                'h.clima',
                'h.condiciones',
                'h.causas',
                'h.oficio_mp'
        ];

        if ($this->legacyAccidentestDisponible()) {
            $selects[] = 'legacy_accidente.coordenadas as legacy_coordenadas';
            $selects[] = DB::raw('ST_Y(legacy_accidente.punto) as legacy_lat_punto');
            $selects[] = DB::raw('ST_X(legacy_accidente.punto) as legacy_lng_punto');
        } else {
            $selects[] = DB::raw('NULL as legacy_coordenadas');
            $selects[] = DB::raw('NULL as legacy_lat_punto');
            $selects[] = DB::raw('NULL as legacy_lng_punto');
        }

        return $query->select($selects);
    }

    private function formatearHecho($hecho)
    {
        $vehiculos = $this->vehiculosDelHecho($hecho->id);
        $conductores = $this->conductoresDelHecho($hecho->id);
        $lesionados = $this->lesionadosDelHecho($hecho->id);

        $vehiculoResponsable = $vehiculos->first();
        $presuntoResponsable = $conductores->first();

        return [
            'numero' => $hecho->id,
            'folio_c5i' => $hecho->folio_c5i,
            'dia_incidente' => $hecho->fecha,
            'hora_ocurrencia' => $hecho->hora,

            'lugar_ocurrencia' => $this->lugar($hecho),
            'colonia' => $this->valorLimpio($hecho->colonia),
            'municipio' => $this->valorLimpio($hecho->municipio),

            'tipo_incidente_vial' => $hecho->tipo_hecho,
            'circunstancias_incidente_vial' => $hecho->causas,
            'superficie_ocurrencia' => $hecho->superficie_via,
            'condiciones_climaticas' => $hecho->clima,
            'condiciones_via' => $hecho->condiciones,

            'coordenadas_geograficas' => $this->coordenadasGeograficas($hecho),

            'numero_vehiculos_involucrados' => $vehiculos->count(),
            'clasificacion_vehiculos' => $vehiculos->pluck('tipo')->filter()->unique()->values(),

            'marca_vehiculo_ocasiona_siniestro' => $vehiculoResponsable->marca ?? null,
            'color_vehiculo_ocasiona_siniestro' => $vehiculoResponsable->color ?? null,

            'numero_personas_involucradas' => $conductores->count() + $lesionados->count(),

            'lugar_remite_presunto_responsable' => $hecho->oficio_mp ? 'MINISTERIO PÚBLICO' : null,
            'edad_presunto_responsable' => $presuntoResponsable->edad ?? null,
            'sexo_presunto_responsable' => $presuntoResponsable->sexo ?? null,
            'resultados_toxicologia' => $this->toxicologia($presuntoResponsable),

            'numero_personas_lesionadas' => $lesionados->where('tipo_lesion', '!=', 'Fallecido')->count(),
            'numero_personas_fallecidas' => $lesionados->where('tipo_lesion', 'Fallecido')->count(),

            'personas_lesionadas_fallecidas' => $lesionados->map(fn($l) => [
                'lugar_remision' => $l->hospital,
                'edad' => $l->edad,
                'sexo' => $l->sexo,
                'tipo' => $l->tipo_lesion
            ])->values()
        ];
    }

    private function vehiculosDelHecho($hechoId)
    {
        return DB::table('hecho_vehiculo as hv')
            ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
            ->where('hv.hecho_id', $hechoId)
            ->select('v.marca', 'v.tipo', 'v.color')
            ->get();
    }

    private function conductoresDelHecho($hechoId)
    {
        return DB::table('hecho_vehiculo as hv')
            ->join('vehiculo_conductor as vc', 'vc.vehiculo_id', '=', 'hv.vehiculo_id')
            ->join('conductores as c', 'c.id', '=', 'vc.conductor_id')
            ->where('hv.hecho_id', $hechoId)
            ->select('c.edad', 'c.sexo', 'c.certificado_alcoholemia', 'c.aliento_etilico')
            ->get();
    }

    private function lesionadosDelHecho($hechoId)
    {
        return DB::table('lesionados')
            ->where('hecho_id', $hechoId)
            ->select('edad', 'sexo', 'tipo_lesion', 'hospital')
            ->get();
    }

    private function lugar($hecho)
    {
        $entre = $this->valorLimpio($hecho->entre_calles);

        return collect([
            $this->valorLimpio($hecho->calle),
            $entre ? 'entre ' . $entre : null
        ])->filter()->implode(', ');
    }

    private function valorLimpio($valor)
    {
        $valor = trim((string)$valor);

        if ($valor === '' || in_array(strtoupper($valor), ['NA','N/A','NO APLICA','NULL','S/D','SD'])) {
            return null;
        }

        return $valor;
    }

    private function toxicologia($c)
    {
        if (!$c) return null;

        if ((int)$c->aliento_etilico === 1) return 'ALIENTO ETÍLICO';
        if ((int)$c->certificado_alcoholemia === 1) return 'CON CERTIFICADO';

        return 'SIN DATOS';
    }

    private function coordenadasGeograficas($hecho): array
    {
        $coordenadas = $this->normalizarParCoordenadas($hecho->lat ?? null, $hecho->lng ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        $coordenadas = $this->normalizarParCoordenadas($hecho->legacy_lat_punto ?? null, $hecho->legacy_lng_punto ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        $coordenadas = $this->parsearCoordenadasLegacy($hecho->legacy_coordenadas ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        return ['lat' => null, 'lng' => null];
    }

    private function parsearCoordenadasLegacy($valor): ?array
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $valor, $matches)) {
            return null;
        }

        return $this->normalizarParCoordenadas($matches[1], $matches[2]);
    }

    private function normalizarParCoordenadas($lat, $lng): ?array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) {
            return null;
        }

        if ($this->latitudValida($lat) && $this->longitudValida($lng)) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        if ($this->longitudValida($lat) && $this->latitudValida($lng)) {
            return ['lat' => $lng, 'lng' => $lat];
        }

        return null;
    }

    private function latitudValida(float $valor): bool
    {
        return $valor >= -90 && $valor <= 90;
    }

    private function longitudValida(float $valor): bool
    {
        return $valor >= -180 && $valor <= 180;
    }

    private function legacyAccidentestDisponible(): bool
    {
        static $disponible = null;

        if ($disponible !== null) {
            return $disponible;
        }

        return $disponible = $this->tablaDisponible(self::LEGACY_PERITOS_DATABASE, 'accidentest');
    }

    private function legacyMapDisponible(): bool
    {
        static $disponible = null;

        if ($disponible !== null) {
            return $disponible;
        }

        return $disponible = $this->tablaDisponible(DB::getDatabaseName(), 'legacy_peritos_import_hechos');
    }

    private function tablaDisponible(string $database, string $tabla): bool
    {
        $resultado = DB::selectOne(
            'SELECT 1 AS existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1',
            [$database, $tabla]
        );

        return $resultado !== null;
    }
}
