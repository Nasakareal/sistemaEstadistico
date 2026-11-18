<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$out = [];

$out['corte'] = DB::selectOne(
    "SELECT MIN(fecha) min_fecha, MAX(fecha) max_fecha, COUNT(*) total
     FROM hechos
     WHERE unidad_org_id IN (1,2)"
);

$out['hechos_mes_unidad'] = DB::select(
    "SELECT YEAR(fecha) anio, MONTH(fecha) mes, unidad_org_id, COUNT(*) hechos
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2025-01-01' AND '2026-11-17'
     GROUP BY YEAR(fecha), MONTH(fecha), unidad_org_id
     ORDER BY anio, mes, unidad_org_id"
);

$out['hechos_mes_total'] = DB::select(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') mes, COUNT(*) hechos,
            SUM(unidad_org_id = 1) siniestros,
            SUM(unidad_org_id = 2) delegaciones,
            SUM(situacion = 'Resuelto') resueltos,
            SUM(situacion = 'Pendiente') pendientes,
            SUM(situacion = 'Turnado') turnados
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2025-01-01' AND '2026-11-17'
     GROUP BY DATE_FORMAT(fecha, '%Y-%m')
     ORDER BY mes"
);

$out['comparativo_periodo'] = DB::select(
    "SELECT YEAR(fecha) anio, unidad_org_id, COUNT(*) hechos,
            SUM(situacion = 'Resuelto') resueltos,
            SUM(situacion = 'Pendiente') pendientes,
            SUM(situacion = 'Turnado') turnados
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2025-01-01' AND '2026-10-31'
       AND MONTH(fecha) <= 10
     GROUP BY YEAR(fecha), unidad_org_id
     ORDER BY anio, unidad_org_id"
);

$out['lesiones_mes'] = DB::select(
    "SELECT DATE_FORMAT(h.fecha, '%Y-%m') mes,
            COUNT(l.id) lesionados,
            SUM(UPPER(l.tipo_lesion) = 'FALLECIDO') fallecidos,
            SUM(UPPER(l.tipo_lesion) = 'GRAVE') graves,
            SUM(l.hospitalizado = 1) hospitalizados
     FROM hechos h
     LEFT JOIN lesionados l ON l.hecho_id = h.id
     WHERE h.unidad_org_id IN (1,2)
       AND h.fecha BETWEEN '2025-01-01' AND '2026-10-31'
     GROUP BY DATE_FORMAT(h.fecha, '%Y-%m')
     ORDER BY mes"
);

$out['tipos_yoy'] = DB::select(
    "SELECT tipo_hecho,
            SUM(YEAR(fecha) = 2025) total_2025,
            SUM(YEAR(fecha) = 2026) total_2026
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2025-01-01' AND '2026-10-31'
       AND MONTH(fecha) <= 10
     GROUP BY tipo_hecho
     ORDER BY total_2026 DESC"
);

$out['semanas_2026'] = DB::select(
    "SELECT YEARWEEK(fecha, 3) semana_iso, MIN(fecha) inicio, MAX(fecha) fin,
            COUNT(*) hechos, SUM(unidad_org_id = 1) siniestros,
            SUM(unidad_org_id = 2) delegaciones
     FROM hechos
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2026-01-01' AND '2026-11-17'
     GROUP BY YEARWEEK(fecha, 3)
     ORDER BY semana_iso"
);

$out['actividades_mes'] = DB::select(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') mes, unidad_org_id,
            COUNT(*) actividades,
            SUM(COALESCE(personas_alcanzadas,0)) personas_alcanzadas
     FROM actividades
     WHERE unidad_org_id IN (1,2)
       AND fecha BETWEEN '2026-01-01' AND '2026-11-17'
     GROUP BY DATE_FORMAT(fecha, '%Y-%m'), unidad_org_id
     ORDER BY mes, unidad_org_id"
);

$out['actividades_top'] = DB::select(
    "SELECT a.unidad_org_id,
            COALESCE(ac.nombre, 'SIN CATEGORIA') categoria,
            COALESCE(asu.nombre, 'SIN SUBCATEGORIA') subcategoria,
            COUNT(*) registros,
            SUM(COALESCE(a.personas_alcanzadas,0)) personas_alcanzadas
     FROM actividades a
     LEFT JOIN actividad_categorias ac ON ac.id = a.actividad_categoria_id
     LEFT JOIN actividad_subcategorias asu ON asu.id = a.actividad_subcategoria_id
     WHERE a.unidad_org_id IN (1,2)
       AND a.fecha BETWEEN '2026-01-01' AND '2026-10-31'
     GROUP BY a.unidad_org_id, ac.nombre, asu.nombre
     ORDER BY registros DESC
     LIMIT 30"
);

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
