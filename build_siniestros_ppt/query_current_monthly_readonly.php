<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::select(
    "SELECT MONTH(fecha) mes,
            SUM(unidad_org_id = 1) unidad_1,
            SUM(unidad_org_id = 2) unidad_2,
            COUNT(*) total
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2026-01-01' AND '2026-11-17'
     GROUP BY MONTH(fecha)
     ORDER BY mes"
);

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
