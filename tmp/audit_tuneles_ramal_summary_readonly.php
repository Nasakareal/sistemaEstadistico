<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$loc = "UPPER(CONCAT_WS(' | ', COALESCE(h.calle,''), COALESCE(h.colonia,''), COALESCE(h.entre_calles,''), COALESCE(h.ubicacion_formateada,'')))";
$tunnel = "($loc LIKE '%TUNEL%' OR $loc LIKE '%TÚNEL%' OR $loc LIKE '%TUENL%' OR $loc LIKE '%TINEL%' OR $loc LIKE '%TUNESL%')";
$area = "($loc LIKE '%RAMAL%' OR $loc LIKE '%CAMELINAS%' OR $loc LIKE '%ALTOZANO%' OR $loc LIKE '%OCOLUSEN%' OR $loc LIKE '%OCOLUCEN%' OR $loc LIKE '%FLORESTA%' OR $loc LIKE '%ROTARISMO%' OR $loc LIKE '%MONTAÑA MONARCA%' OR $loc LIKE '%MONTANA MONARCA%')";
$exclude = "($loc NOT LIKE '%SALIDA A CHARO%' AND $loc NOT LIKE '%SALIDA CHARO%' AND $loc NOT LIKE '%SALIDA A QUIROGA%' AND $loc NOT LIKE '%SALIDA QUIROGA%' AND $loc NOT LIKE '%SALIDA SALAMANCA%' AND $loc NOT LIKE '%MERCADO DE ABASTOS%' AND $loc NOT LIKE '%PERIFERICO REVOLUCION%' AND $loc NOT LIKE '%PERIFÉRICO REVOLUCIÓN%' AND $loc NOT LIKE '%MADERO ORIENTE%')";

$rows = DB::table('hechos as h')
    ->leftJoin('lesionados as l', 'l.hecho_id', '=', 'h.id')
    ->whereDate('h.fecha', '>=', '2018-10-16')
    ->where('h.municipio', 'like', '%MORELIA%')
    ->whereRaw($tunnel)->whereRaw($area)->whereRaw($exclude)
    ->select([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->selectRaw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(l.tipo_lesion,'')) LIKE '%FALLECID%' THEN l.id END) AS fallecidos")
    ->selectRaw("COUNT(DISTINCT CASE WHEN l.id IS NOT NULL AND UPPER(COALESCE(l.tipo_lesion,'')) NOT LIKE '%FALLECID%' THEN l.id END) AS lesionados")
    ->groupBy([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->orderBy('h.fecha')->orderBy('h.hora')->get();

$ids = $rows->pluck('id');
$vehicleDamage = DB::table('hecho_vehiculo as hv')
    ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
    ->whereIn('hv.hecho_id', $ids)
    ->groupBy('hv.hecho_id')
    ->selectRaw('hv.hecho_id, SUM(COALESCE(v.monto_danos,0)) AS total')
    ->pluck('total', 'hecho_id');

$normFolio = static function ($folio) {
    return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $folio));
};

$result = [
    'criteria' => 'Menciones explícitas de túnel + contexto Ramal Camelinas, desde apertura 2018-10-16; excluye otros túneles de Morelia.',
    'total' => $rows->count(),
    'fallecidos' => (int) $rows->sum('fallecidos'),
    'lesionados' => (int) $rows->sum('lesionados'),
    'solo_danos_materiales' => $rows->filter(fn ($r) => (int) $r->fallecidos === 0 && (int) $r->lesionados === 0)->count(),
    'monto_vehiculos_registrado' => (float) $vehicleDamage->sum(),
    'monto_patrimonial_registrado' => (float) $rows->sum(fn ($r) => is_numeric($r->monto_danos_patrimoniales) ? $r->monto_danos_patrimoniales : 0),
    'por_anio' => $rows->groupBy(fn ($r) => substr($r->fecha, 0, 4))->map(fn ($g) => [
        'hechos' => $g->count(),
        'fallecidos' => (int) $g->sum('fallecidos'),
        'lesionados' => (int) $g->sum('lesionados'),
        'solo_danos' => $g->filter(fn ($r) => (int) $r->fallecidos === 0 && (int) $r->lesionados === 0)->count(),
    ]),
    'causas' => $rows->groupBy(fn ($r) => trim((string) $r->causas) ?: 'SIN DATO')->map->count()->sortDesc(),
    'tipos' => $rows->groupBy(fn ($r) => trim((string) $r->tipo_hecho) ?: 'SIN DATO')->map->count()->sortDesc(),
    'mismo_dia_hora' => $rows->groupBy(fn ($r) => $r->fecha . ' ' . $r->hora)->filter(fn ($g) => $g->count() > 1)->map(fn ($g) => $g->pluck('id')),
    'folio_repetido' => $rows->filter(fn ($r) => $normFolio($r->folio_c5i) !== '')->groupBy(fn ($r) => $normFolio($r->folio_c5i))->filter(fn ($g) => $g->count() > 1)->map(fn ($g) => $g->pluck('id')),
    'rows' => $rows->map(fn ($r) => [
        'id' => $r->id, 'folio' => $r->folio_c5i, 'fecha' => $r->fecha, 'hora' => $r->hora,
        'lugar' => implode(' | ', array_filter([$r->calle, $r->colonia, $r->entre_calles, $r->ubicacion_formateada])),
        'tipo' => $r->tipo_hecho, 'causa' => $r->causas,
        'fallecidos' => (int) $r->fallecidos, 'lesionados' => (int) $r->lesionados,
        'danos_vehiculos' => (float) ($vehicleDamage[$r->id] ?? 0),
    ]),
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
