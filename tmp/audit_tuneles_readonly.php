<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('hechos as h')
    ->leftJoin('lesionados as l', 'l.hecho_id', '=', 'h.id')
    ->select([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.municipio', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.colision_camino', 'h.situacion',
    ])
    ->selectRaw("SUM(CASE WHEN UPPER(COALESCE(l.tipo_lesion,'')) LIKE '%FALLECID%' THEN 1 ELSE 0 END) AS fallecidos")
    ->selectRaw("SUM(CASE WHEN l.id IS NOT NULL AND UPPER(COALESCE(l.tipo_lesion,'')) NOT LIKE '%FALLECID%' THEN 1 ELSE 0 END) AS lesionados")
    ->where(function ($q) {
        foreach (['h.calle', 'h.colonia', 'h.entre_calles', 'h.ubicacion_formateada', 'h.colision_camino'] as $field) {
            $q->orWhereRaw("UPPER(COALESCE($field,'')) LIKE '%TUNEL%'")
              ->orWhereRaw("UPPER(COALESCE($field,'')) LIKE '%TÚNEL%'")
              ->orWhereRaw("UPPER(COALESCE($field,'')) LIKE '%CAMELINAS%'")
              ->orWhereRaw("UPPER(COALESCE($field,'')) LIKE '%RAMAL%'");
        }
    })
    ->groupBy([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.municipio', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.colision_camino', 'h.situacion',
    ])
    ->orderBy('h.fecha')->orderBy('h.hora')->get();

echo json_encode([
    'min_fecha' => DB::table('hechos')->min('fecha'),
    'max_fecha' => DB::table('hechos')->max('fecha'),
    'total_hechos' => DB::table('hechos')->count(),
    'candidates' => $rows,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
