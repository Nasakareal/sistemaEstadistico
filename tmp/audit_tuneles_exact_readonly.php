<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$loc = "UPPER(CONCAT_WS(' | ', COALESCE(h.calle,''), COALESCE(h.colonia,''), COALESCE(h.entre_calles,''), COALESCE(h.ubicacion_formateada,''), COALESCE(h.colision_camino,'')))";

$rows = DB::table('hechos as h')
    ->leftJoin('lesionados as l', 'l.hecho_id', '=', 'h.id')
    ->leftJoin('hecho_vehiculo as hv', 'hv.hecho_id', '=', 'h.id')
    ->leftJoin('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
    ->whereDate('h.fecha', '>=', '2018-10-16')
    ->where('h.municipio', 'like', '%MORELIA%')
    ->where(function ($q) use ($loc) {
        $q->whereRaw("$loc LIKE '%TUNEL%'")
          ->orWhereRaw("$loc LIKE '%TÚNEL%'")
          ->orWhere(function ($q2) use ($loc) {
              $q2->whereRaw("$loc LIKE '%RAMAL%'")
                 ->whereRaw("$loc LIKE '%CAMELINAS%'");
          })
          ->orWhere(function ($q2) use ($loc) {
              $q2->whereRaw("$loc LIKE '%LIBRAMIENTO SUR%'")
                 ->whereRaw("$loc LIKE '%CAMELINAS%'");
          });
    })
    ->select([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->selectRaw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(l.tipo_lesion,'')) LIKE '%FALLECID%' THEN l.id END) AS fallecidos")
    ->selectRaw("COUNT(DISTINCT CASE WHEN l.id IS NOT NULL AND UPPER(COALESCE(l.tipo_lesion,'')) NOT LIKE '%FALLECID%' THEN l.id END) AS lesionados")
    ->selectRaw('COUNT(DISTINCT v.id) AS vehiculos')
    ->selectRaw('SUM(DISTINCT COALESCE(v.monto_danos,0)) AS danos_vehiculos')
    ->groupBy([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->orderBy('h.fecha')->orderBy('h.hora')->get();

foreach ($rows as $r) {
    echo implode("\t", [
        $r->id, $r->folio_c5i, $r->fecha, $r->hora,
        $r->calle, $r->colonia, $r->entre_calles, $r->ubicacion_formateada,
        $r->lat, $r->lng, $r->tipo_hecho, $r->causas,
        $r->fallecidos, $r->lesionados, $r->vehiculos,
        $r->danos_patrimoniales, $r->monto_danos_patrimoniales, $r->danos_vehiculos,
        $r->situacion,
    ]), PHP_EOL;
}

fwrite(STDERR, 'COUNT=' . $rows->count() . PHP_EOL);
