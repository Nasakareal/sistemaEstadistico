<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = [474, 476, 21202, 21224, 56455, 56457];
$hechos = DB::table('hechos')->whereIn('id', $ids)->orderBy('fecha')->orderBy('id')->get();
$vehicles = DB::table('hecho_vehiculo as hv')->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
    ->whereIn('hv.hecho_id', $ids)->select('hv.hecho_id', 'v.*')->orderBy('hv.hecho_id')->get()->groupBy('hecho_id');
$victims = DB::table('lesionados')->whereIn('hecho_id', $ids)->orderBy('hecho_id')->get()->groupBy('hecho_id');

foreach ($hechos as $h) {
    echo json_encode([
        'hecho' => $h,
        'vehiculos' => $vehicles[$h->id] ?? [],
        'victimas' => $victims[$h->id] ?? [],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
}
