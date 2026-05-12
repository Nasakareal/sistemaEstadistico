/*
  Repara hechos actuales que quedaron sin unidad organizacional.
  Regla operativa: si el usuario creador no tiene unidad, cae por defecto en Unidad 1 (Siniestros).

  No toca registros importados desde peritos legacy.
*/

SELECT
    'ANTES_HECHOS_ACTUALES_SIN_UNIDAD' AS seccion,
    COUNT(*) AS total
FROM hechos h
WHERE h.unidad_org_id IS NULL
  AND (h.fuente_ubicacion IS NULL OR h.fuente_ubicacion <> 'legacy_peritos');

UPDATE hechos h
LEFT JOIN users u ON u.id = h.created_by
SET h.unidad_org_id = COALESCE(NULLIF(u.unidad_id, 0), 1)
WHERE h.unidad_org_id IS NULL
  AND (h.fuente_ubicacion IS NULL OR h.fuente_ubicacion <> 'legacy_peritos');

SELECT
    'DESPUES_HECHOS_ACTUALES_SIN_UNIDAD' AS seccion,
    COUNT(*) AS total
FROM hechos h
WHERE h.unidad_org_id IS NULL
  AND (h.fuente_ubicacion IS NULL OR h.fuente_ubicacion <> 'legacy_peritos');

SELECT
    'MUESTRA_HECHOS_ACTUALES_CORREGIDOS' AS seccion,
    h.id,
    h.folio_c5i,
    h.fecha,
    h.created_by,
    u.name AS creador,
    u.unidad_id AS unidad_creador,
    h.unidad_org_id
FROM hechos h
LEFT JOIN users u ON u.id = h.created_by
WHERE h.unidad_org_id = COALESCE(NULLIF(u.unidad_id, 0), 1)
  AND (h.fuente_ubicacion IS NULL OR h.fuente_ubicacion <> 'legacy_peritos')
ORDER BY h.id DESC
LIMIT 20;
