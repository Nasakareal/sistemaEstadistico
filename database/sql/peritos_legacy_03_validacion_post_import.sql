/*
  Validacion posterior a la importacion limpia de Peritos legacy.
  Este script NO modifica datos.
*/

USE sistemaestadistico;

SELECT 'RESUMEN_MAPEOS' AS seccion;

SELECT 'hechos_mapeados' AS metric, COUNT(*) AS total
FROM legacy_peritos_import_hechos
WHERE new_hecho_id IS NOT NULL
UNION ALL
SELECT 'folios_originales_duplicados_resueltos_con_folio_LEG', COUNT(*)
FROM legacy_peritos_import_hechos
WHERE folio_es_duplicado = 1
  AND folio_importado LIKE 'LEG%'
UNION ALL
SELECT 'folios_con_conflicto_preexistente_resueltos', COUNT(*)
FROM legacy_peritos_import_hechos
WHERE folio_conflicta_con_actual = 1
UNION ALL
SELECT 'vehiculos_old_mapeados', COUNT(*)
FROM legacy_peritos_import_vehiculos
WHERE new_vehiculo_id IS NOT NULL
UNION ALL
SELECT 'vehiculos_unicos_importados', COUNT(DISTINCT new_vehiculo_id)
FROM legacy_peritos_import_vehiculos
WHERE new_vehiculo_id IS NOT NULL
UNION ALL
SELECT 'vehiculos_deduplicados_por_serie', COUNT(*)
FROM legacy_peritos_import_vehiculos
WHERE duplicate_reason = 'serie'
UNION ALL
SELECT 'vehiculos_deduplicados_por_placas', COUNT(*)
FROM legacy_peritos_import_vehiculos
WHERE duplicate_reason = 'placas'
UNION ALL
SELECT 'conductores_unicos_importados', COUNT(*)
FROM legacy_peritos_import_conductores
WHERE new_conductor_id IS NOT NULL
UNION ALL
SELECT 'lesionados_importados', COUNT(*)
FROM legacy_peritos_import_lesionados
WHERE new_lesionado_id IS NOT NULL;

SELECT 'DUPLICADOS_REMANENTES_EN_IMPORTACION' AS seccion;

SELECT 'folios_importados_repetidos' AS metric, COUNT(*) AS total
FROM (
    SELECT folio_c5i
    FROM hechos h
    JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = h.id
    WHERE folio_c5i IS NOT NULL
    GROUP BY folio_c5i
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'placas_repetidas_mismo_hecho_importado', COUNT(*)
FROM (
    SELECT hv.hecho_id, v.placas
    FROM hecho_vehiculo hv
    JOIN vehiculos v ON v.id = hv.vehiculo_id
    JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = hv.hecho_id
    WHERE NULLIF(TRIM(COALESCE(v.placas, '')), '') IS NOT NULL
    GROUP BY hv.hecho_id, v.placas
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'series_repetidas_mismo_hecho_importado', COUNT(*)
FROM (
    SELECT hv.hecho_id, v.serie
    FROM hecho_vehiculo hv
    JOIN vehiculos v ON v.id = hv.vehiculo_id
    JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = hv.hecho_id
    WHERE NULLIF(TRIM(COALESCE(v.serie, '')), '') IS NOT NULL
    GROUP BY hv.hecho_id, v.serie
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'conductores_repetidos_mismo_hecho_importado', COUNT(*)
FROM (
    SELECT hv.hecho_id, c.nombre
    FROM hecho_vehiculo hv
    JOIN vehiculo_conductor vc ON vc.vehiculo_id = hv.vehiculo_id
    JOIN conductores c ON c.id = vc.conductor_id
    JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = hv.hecho_id
    WHERE NULLIF(TRIM(COALESCE(c.nombre, '')), '') IS NOT NULL
    GROUP BY hv.hecho_id, c.nombre
    HAVING COUNT(DISTINCT c.id) > 1
) x;

SELECT 'MUESTRA_FOLIOS_AJUSTADOS' AS seccion;

SELECT
    old_hecho_id,
    new_hecho_id,
    folio_original,
    folio_importado,
    folio_es_duplicado,
    folio_conflicta_con_actual
FROM legacy_peritos_import_hechos
WHERE folio_es_duplicado = 1
   OR folio_conflicta_con_actual = 1
ORDER BY old_hecho_id
LIMIT 100;

SELECT 'MUESTRA_VEHICULOS_DEDUPLICADOS' AS seccion;

SELECT
    old_hecho_id,
    old_vehiculo_id,
    old_vehiculo_winner_id,
    new_hecho_id,
    new_vehiculo_id,
    duplicate_reason,
    placas_key,
    serie_key
FROM legacy_peritos_import_vehiculos
WHERE duplicate_reason IS NOT NULL
ORDER BY old_hecho_id, old_vehiculo_winner_id, old_vehiculo_id
LIMIT 100;

