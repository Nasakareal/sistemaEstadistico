<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChoquesDiariosController extends Controller
{
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
        return DB::table('hechos as h')
            ->select(
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
            );
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

            'coordenadas_geograficas' => [
                'lat' => $hecho->lat ? (float)$hecho->lat : null,
                'lng' => $hecho->lng ? (float)$hecho->lng : null
            ],

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
}
