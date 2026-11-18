<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::select(<<<'SQL'
SELECT MONTH(fecha) AS mes, COUNT(*) AS total
FROM hechos
WHERE unidad_org_id IN (1, 2)
  AND fecha >= '2026-01-01'
  AND fecha <= '2026-11-17 23:59:59'
  AND DAY(fecha) <= 17
GROUP BY MONTH(fecha)
ORDER BY mes
SQL);

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
