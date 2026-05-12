/*
  Elimina hechos legacy de Peritos que no tienen vehiculos vinculados.

  Alcance:
  - Solo hechos con fuente_ubicacion = 'legacy_peritos'.
  - Solo hechos sin registros en hecho_vehiculo.
  - Guarda auditoria en legacy_peritos_hechos_sin_vehiculos_eliminados.
  - Limpia new_hecho_id/new_lesionado_id en tablas legacy_peritos_import_*.

  IMPORTANTE:
  - Ejecutar con respaldo reciente.
  - No borra hechos actuales.
  - No corrige por si solo el contador interno AUTO_INCREMENT de InnoDB.
*/

USE sistemaestadistico;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_hechos_sin_vehiculos;
CREATE TEMPORARY TABLE tmp_legacy_hechos_sin_vehiculos (
    hecho_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    old_hecho_id INT NULL,
    folio_c5i VARCHAR(20) NULL,
    fecha VARCHAR(10) NULL,
    lesionados_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    KEY tmp_legacy_hechos_sin_vehiculos_old_idx (old_hecho_id)
) ENGINE=InnoDB;

INSERT INTO tmp_legacy_hechos_sin_vehiculos (
    hecho_id,
    old_hecho_id,
    folio_c5i,
    fecha,
    lesionados_count,
    created_at
)
SELECT
    h.id AS hecho_id,
    mh.old_hecho_id,
    h.folio_c5i,
    CAST(h.fecha AS CHAR) AS fecha,
    (
        SELECT COUNT(*)
        FROM lesionados l
        WHERE l.hecho_id = h.id
    ) AS lesionados_count,
    CASE
        WHEN CAST(h.created_at AS CHAR) IN ('0000-00-00 00:00:00', '') THEN NULL
        ELSE h.created_at
    END AS created_at
FROM hechos h
LEFT JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = h.id
WHERE h.fuente_ubicacion = 'legacy_peritos'
    AND NOT EXISTS (
        SELECT 1
        FROM hecho_vehiculo hv
        WHERE hv.hecho_id = h.id
    );

SELECT
    'CANDIDATOS_HECHOS_LEGACY_SIN_VEHICULOS' AS seccion,
    COUNT(*) AS total,
    MIN(hecho_id) AS id_min,
    MAX(hecho_id) AS id_max,
    SUM(lesionados_count) AS lesionados_que_se_eliminan
FROM tmp_legacy_hechos_sin_vehiculos;

SELECT
    'MUESTRA_CANDIDATOS' AS seccion,
    hecho_id,
    old_hecho_id,
    folio_c5i,
    fecha,
    lesionados_count
FROM tmp_legacy_hechos_sin_vehiculos
ORDER BY hecho_id
LIMIT 100;

CREATE TABLE IF NOT EXISTS legacy_peritos_hechos_sin_vehiculos_eliminados (
    deleted_hecho_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    old_hecho_id INT NULL,
    folio_c5i VARCHAR(20) NULL,
    fecha VARCHAR(10) NULL,
    lesionados_count INT NOT NULL DEFAULT 0,
    original_created_at DATETIME NULL,
    deleted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY legacy_peritos_hechos_sin_vehiculos_old_idx (old_hecho_id),
    KEY legacy_peritos_hechos_sin_vehiculos_fecha_idx (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE legacy_peritos_hechos_sin_vehiculos_eliminados
    MODIFY COLUMN fecha VARCHAR(10) NULL,
    MODIFY COLUMN original_created_at DATETIME NULL;

INSERT IGNORE INTO legacy_peritos_hechos_sin_vehiculos_eliminados (
    deleted_hecho_id,
    old_hecho_id,
    folio_c5i,
    fecha,
    lesionados_count,
    original_created_at
)
SELECT
    hecho_id,
    old_hecho_id,
    folio_c5i,
    fecha,
    lesionados_count,
    created_at
FROM tmp_legacy_hechos_sin_vehiculos;

UPDATE legacy_peritos_import_lesionados ml
JOIN tmp_legacy_hechos_sin_vehiculos t ON t.hecho_id = ml.new_hecho_id
SET
    ml.new_hecho_id = NULL,
    ml.new_lesionado_id = NULL;

UPDATE legacy_peritos_import_vehiculos mv
JOIN tmp_legacy_hechos_sin_vehiculos t ON t.hecho_id = mv.new_hecho_id
SET mv.new_hecho_id = NULL;

UPDATE legacy_peritos_import_hechos mh
JOIN tmp_legacy_hechos_sin_vehiculos t ON t.hecho_id = mh.new_hecho_id
SET mh.new_hecho_id = NULL;

DELETE h
FROM hechos h
JOIN tmp_legacy_hechos_sin_vehiculos t ON t.hecho_id = h.id;

SET @hechos_eliminados := ROW_COUNT();

SELECT
    'ELIMINACION_COMPLETADA' AS seccion,
    @hechos_eliminados AS hechos_eliminados;

SELECT
    'RESUMEN_POST_ELIMINACION' AS seccion,
    COUNT(*) AS hechos_legacy_restantes,
    MIN(id) AS id_min,
    MAX(id) AS id_max
FROM hechos
WHERE fuente_ubicacion = 'legacy_peritos';

SELECT
    'VERIFICACION_SIN_VEHICULOS_RESTANTES' AS seccion,
    COUNT(*) AS legacy_sin_vehiculos_restantes
FROM hechos h
WHERE h.fuente_ubicacion = 'legacy_peritos'
    AND NOT EXISTS (
        SELECT 1
        FROM hecho_vehiculo hv
        WHERE hv.hecho_id = h.id
    );

COMMIT;
