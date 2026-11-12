<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$operationIds = [79, 80, 81, 82, 83, 84, 91];

$operations = DB::table('conduce_legalidad_operativos')
    ->whereIn('id', $operationIds)
    ->whereNull('deleted_at')
    ->get([
        'id', 'fecha', 'hora_inicio', 'hora_cierre', 'municipio', 'lugar',
        'numero', 'created_at',
    ]);

$vehicles = DB::table('conduce_legalidad_capturas as c')
    ->join('conduce_legalidad_vehiculos as v', 'v.captura_id', '=', 'c.id')
    ->whereIn('c.operativo_id', $operationIds)
    ->get([
        'c.id as captura_id', 'c.operativo_id', 'c.fecha', 'c.hora',
        'c.created_at as captura_created_at',
        'v.marca', 'v.linea', 'v.modelo', 'v.tipo_general', 'v.tipo',
        'v.color', 'v.placas', 'v.aseguradora', 'v.numero_inventario',
        'v.corralon',
    ]);

echo json_encode([
    'operations' => $operations,
    'vehicles' => $vehicles,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
