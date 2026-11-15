<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('conduce_legalidad_vehiculos as v')
    ->join('conduce_legalidad_capturas as c', 'c.id', '=', 'v.captura_id')
    ->join('conduce_legalidad_operativos as o', 'o.id', '=', 'c.operativo_id')
    ->whereNull('o.deleted_at')
    ->whereDate('o.fecha', '>', '2026-11-07')
    ->select([
        'v.id as vehiculo_id',
        'c.id as captura_id',
        'o.id as operativo_id',
        'o.fecha as operativo_fecha',
        'o.hora_inicio as operativo_hora_inicio',
        'o.lugar as operativo_lugar',
        'o.numero as operativo_numero',
        'o.colonia as operativo_colonia',
        'o.municipio as operativo_municipio',
        'c.fecha as captura_fecha',
        'c.hora as captura_hora',
        'v.marca',
        'v.linea',
        'v.modelo',
        'v.tipo',
        'v.color',
        'v.placas',
        'v.estado_placas',
        'v.serie',
        'v.numero_inventario',
        'v.tipo_servicio',
        'v.motivo_retencion',
    ])
    ->orderBy('o.fecha')
    ->orderBy('o.hora_inicio')
    ->orderBy('o.id')
    ->orderBy('c.hora')
    ->orderBy('v.id')
    ->get();

$byDate = $rows->groupBy('operativo_fecha')->map->count();
$byOperation = $rows->groupBy('operativo_id')->map(function ($items) {
    $first = $items->first();
    return [
        'fecha' => $first->operativo_fecha,
        'hora_inicio' => $first->operativo_hora_inicio,
        'lugar' => $first->operativo_lugar,
        'total' => $items->count(),
        'min_vehiculo_id' => $items->min('vehiculo_id'),
        'max_vehiculo_id' => $items->max('vehiculo_id'),
    ];
});

echo json_encode([
    'total' => $rows->count(),
    'by_date' => $byDate,
    'by_operation' => $byOperation,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
