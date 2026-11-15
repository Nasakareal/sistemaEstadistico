<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$base = DB::table('conduce_legalidad_vehiculos as v')
    ->join('conduce_legalidad_capturas as c', 'c.id', '=', 'v.captura_id')
    ->join('conduce_legalidad_operativos as o', 'o.id', '=', 'c.operativo_id')
    ->whereNull('o.deleted_at');

$legacyWindow = (clone $base)
    ->whereDate('c.fecha', '>=', '2026-10-08')
    ->where(function ($q) {
        $q->whereDate('c.fecha', '<', '2026-11-07')
          ->orWhere(function ($q2) {
              $q2->whereDate('c.fecha', '2026-11-07')->whereTime('c.hora', '<=', '18:00:00');
          });
    });

$newRows = (clone $base)
    ->where(function ($q) {
        $q->whereDate('c.fecha', '>', '2026-11-07')
          ->orWhere(function ($q2) {
              $q2->whereDate('c.fecha', '2026-11-07')->whereTime('c.hora', '>', '18:00:00');
          });
    });

$select = [
    'v.id as vehiculo_id', 'v.captura_id', 'c.fecha', 'c.hora',
    'o.id as operativo_id', 'o.nombre as operativo_nombre', 'o.lugar as operativo_lugar',
    'o.numero as operativo_numero', 'o.colonia as operativo_colonia', 'o.municipio as operativo_municipio',
    'c.lugar as captura_lugar', 'c.municipio as captura_municipio',
    'v.marca', 'v.linea', 'v.modelo', 'v.tipo_general', 'v.tipo', 'v.color',
    'v.placas', 'v.estado_placas', 'v.serie', 'v.numero_inventario', 'v.tipo_servicio',
    'v.licencia_punto_infraccion_id as vehiculo_infraccion_id', 'v.infraccion_codigo as vehiculo_codigo',
    'v.fundamento_legal as vehiculo_fundamento', 'v.motivo_retencion',
    'c.licencia_punto_infraccion_id as captura_infraccion_id', 'c.infraccion_codigo as captura_codigo',
    'c.fundamento_legal as captura_fundamento',
];

$samples = (clone $legacyWindow)->select($select)->orderBy('c.fecha')->orderBy('c.hora')->orderBy('v.id')->limit(3)->get();
$lastSamples = (clone $legacyWindow)->select($select)->orderByDesc('c.fecha')->orderByDesc('c.hora')->orderByDesc('v.id')->limit(3)->get();
$newSamples = (clone $newRows)->select($select)->orderBy('c.fecha')->orderBy('c.hora')->orderBy('v.id')->limit(10)->get();

$captureIds = $samples->merge($lastSamples)->merge($newSamples)->pluck('captura_id')->unique()->values();
$fundamentos = DB::table('conduce_legalidad_captura_fundamentos as f')
    ->leftJoin('licencia_punto_infracciones as i', 'i.id', '=', 'f.licencia_punto_infraccion_id')
    ->whereIn('f.captura_id', $captureIds)
    ->select('f.captura_id', 'f.orden', 'f.infraccion_codigo', 'f.fundamento_legal', 'i.codigo', 'i.nombre', 'i.articulo', 'i.fraccion', 'i.inciso')
    ->orderBy('f.captura_id')->orderBy('f.orden')->orderBy('f.id')->get()->groupBy('captura_id');

echo json_encode([
    'legacy_window_count' => $legacyWindow->count(),
    'new_count' => $newRows->count(),
    'new_by_date' => (clone $newRows)->selectRaw('c.fecha, COUNT(*) total')->groupBy('c.fecha')->orderBy('c.fecha')->get(),
    'first_samples' => $samples,
    'last_samples' => $lastSamples,
    'new_samples' => $newSamples,
    'fundamentos' => $fundamentos,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
