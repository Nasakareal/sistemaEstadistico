<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$out = [];

$out['estado_mes_unidad'] = DB::select(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') mes, unidad_org_id, COUNT(*) hechos,
            SUM(situacion = 'Resuelto') resueltos,
            SUM(situacion = 'Pendiente') pendientes,
            SUM(situacion = 'Turnado') turnados
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY DATE_FORMAT(fecha, '%Y-%m'), unidad_org_id
     ORDER BY mes, unidad_org_id"
);

$out['tipos_unidad'] = DB::select(
    "SELECT unidad_org_id, tipo_hecho, COUNT(*) total
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY unidad_org_id, tipo_hecho
     ORDER BY unidad_org_id, total DESC"
);

$out['actividades_top_u1'] = DB::select(
    "SELECT COALESCE(ac.nombre, 'SIN CATEGORIA') categoria,
            COALESCE(asu.nombre, 'SIN SUBCATEGORIA') subcategoria,
            COUNT(*) registros
     FROM actividades a
     LEFT JOIN actividad_categorias ac ON ac.id = a.actividad_categoria_id
     LEFT JOIN actividad_subcategorias asu ON asu.id = a.actividad_subcategoria_id
     WHERE a.unidad_org_id = 1
       AND a.fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY ac.nombre, asu.nombre
     ORDER BY registros DESC
     LIMIT 8"
);

$out['actividades_top_u2'] = DB::select(
    "SELECT COALESCE(ac.nombre, 'SIN CATEGORIA') categoria,
            COALESCE(asu.nombre, 'SIN SUBCATEGORIA') subcategoria,
            COUNT(*) registros
     FROM actividades a
     LEFT JOIN actividad_categorias ac ON ac.id = a.actividad_categoria_id
     LEFT JOIN actividad_subcategorias asu ON asu.id = a.actividad_subcategoria_id
     WHERE a.unidad_org_id = 2
       AND a.fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY ac.nombre, asu.nombre
     ORDER BY registros DESC
     LIMIT 8"
);

$out['lesiones_unidad'] = DB::select(
    "SELECT h.unidad_org_id,
            COUNT(l.id) lesionados,
            SUM(UPPER(l.tipo_lesion) = 'FALLECIDO') fallecidos,
            SUM(UPPER(l.tipo_lesion) = 'GRAVE') graves,
            SUM(l.hospitalizado = 1) hospitalizados
     FROM hechos h
     LEFT JOIN lesionados l ON l.hecho_id = h.id
     WHERE h.unidad_org_id IN (1,2)
       AND h.fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY h.unidad_org_id
     ORDER BY h.unidad_org_id"
);

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
