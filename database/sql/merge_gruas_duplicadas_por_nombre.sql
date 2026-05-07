/*
  Une gruas duplicadas por nombre exacto normalizado (UPPER(TRIM(nombre))).

  Ejecutar primero en una copia o respaldo reciente. Antes del COMMIT revisa:
  - tmp_grua_merge_map: duplicate_id -> master_id.
  - totales de servicios por unidad.

  Si un nombre no debe combinarse aunque se repita, agrega su nombre normalizado
  en tmp_grua_merge_excluir antes de llenar tmp_grua_merge_map.
*/

/* Asegura columnas usadas para separar historico de unidad 1 y unidad 2.
   Estas sentencias son no-op si las columnas ya existen. */
SET @sql = IF(
    (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'servicios'
          AND COLUMN_NAME = 'unidad_id'
    ) = 0,
    'ALTER TABLE servicios ADD COLUMN unidad_id BIGINT UNSIGNED NULL AFTER grua_id',
    'SELECT ''servicios.unidad_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'servicios'
          AND COLUMN_NAME = 'delegacion_id'
    ) = 0,
    'ALTER TABLE servicios ADD COLUMN delegacion_id BIGINT UNSIGNED NULL AFTER unidad_id',
    'SELECT ''servicios.delegacion_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'servicios'
          AND INDEX_NAME = 'servicios_unidad_delegacion_idx'
    ) = 0,
    'ALTER TABLE servicios ADD INDEX servicios_unidad_delegacion_idx (unidad_id, delegacion_id)',
    'SELECT ''servicios_unidad_delegacion_idx ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* Vista previa de duplicados */
SELECT
    UPPER(TRIM(g.nombre)) AS nombre_norm,
    COUNT(*) AS gruas,
    GROUP_CONCAT(g.id ORDER BY g.id) AS ids,
    GROUP_CONCAT(DISTINCT COALESCE(g.direccion, '') SEPARATOR ' | ') AS direcciones,
    COUNT(DISTINCT s.id) AS servicios
FROM gruas g
LEFT JOIN servicios s ON s.grua_id = g.id
GROUP BY UPPER(TRIM(g.nombre))
HAVING COUNT(*) > 1
ORDER BY servicios DESC, gruas DESC, nombre_norm;

CREATE TABLE IF NOT EXISTS grua_merge_backup (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    merged_at DATETIME NOT NULL,
    duplicate_id BIGINT UNSIGNED NOT NULL,
    master_id BIGINT UNSIGNED NOT NULL,
    nombre_norm VARCHAR(255) NOT NULL,
    duplicate_nombre VARCHAR(255) NULL,
    duplicate_direccion VARCHAR(255) NULL,
    duplicate_ubicacion_corralon TEXT NULL,
    duplicate_telefono VARCHAR(30) NULL,
    duplicate_email VARCHAR(255) NULL,
    duplicate_created_at TIMESTAMP NULL,
    duplicate_updated_at TIMESTAMP NULL,
    UNIQUE KEY grua_merge_backup_duplicate_id_unique (duplicate_id),
    KEY grua_merge_backup_master_id_idx (master_id),
    KEY grua_merge_backup_nombre_norm_idx (nombre_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;

CREATE TEMPORARY TABLE tmp_grua_merge_excluir (
    nombre_norm VARCHAR(255) PRIMARY KEY
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Ejemplo si quieres excluir un nombre ambiguo:
INSERT INTO tmp_grua_merge_excluir (nombre_norm) VALUES ('AUTOPISTA');
*/

CREATE TEMPORARY TABLE tmp_grua_merge_map (
    duplicate_id BIGINT UNSIGNED PRIMARY KEY,
    master_id BIGINT UNSIGNED NOT NULL,
    nombre_norm VARCHAR(255) NOT NULL,
    KEY tmp_grua_merge_master_id_idx (master_id)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_grua_merge_map (duplicate_id, master_id, nombre_norm)
SELECT
    g.id AS duplicate_id,
    masters.master_id,
    masters.nombre_norm
FROM gruas g
JOIN (
    SELECT
        UPPER(TRIM(nombre)) AS nombre_norm,
        MIN(id) AS master_id,
        COUNT(*) AS total
    FROM gruas
    GROUP BY UPPER(TRIM(nombre))
    HAVING COUNT(*) > 1
) masters ON masters.nombre_norm = UPPER(TRIM(g.nombre))
LEFT JOIN tmp_grua_merge_excluir ex ON ex.nombre_norm = masters.nombre_norm
WHERE g.id <> masters.master_id
  AND ex.nombre_norm IS NULL;

/* Revisa este resultado antes de confirmar la transaccion. */
SELECT
    m.nombre_norm,
    m.master_id,
    gm.nombre AS master_nombre,
    m.duplicate_id,
    gd.nombre AS duplicate_nombre,
    gd.direccion AS duplicate_direccion,
    (SELECT COUNT(*) FROM servicios s WHERE s.grua_id = m.duplicate_id) AS duplicate_servicios
FROM tmp_grua_merge_map m
JOIN gruas gm ON gm.id = m.master_id
JOIN gruas gd ON gd.id = m.duplicate_id
ORDER BY m.nombre_norm, m.master_id, m.duplicate_id;

INSERT IGNORE INTO grua_merge_backup (
    merged_at,
    duplicate_id,
    master_id,
    nombre_norm,
    duplicate_nombre,
    duplicate_direccion,
    duplicate_ubicacion_corralon,
    duplicate_telefono,
    duplicate_email,
    duplicate_created_at,
    duplicate_updated_at
)
SELECT
    NOW(),
    m.duplicate_id,
    m.master_id,
    m.nombre_norm,
    gd.nombre,
    gd.direccion,
    gd.ubicacion_corralon,
    gd.telefono,
    gd.email,
    gd.created_at,
    gd.updated_at
FROM tmp_grua_merge_map m
JOIN gruas gd ON gd.id = m.duplicate_id;

/* Completa contexto historico de servicios antes de mover gruas. */
UPDATE servicios s
JOIN hecho_vehiculo hv ON hv.vehiculo_id = s.vehiculo_id
JOIN hechos h ON h.id = hv.hecho_id
SET
    s.unidad_id = COALESCE(NULLIF(h.unidad_org_id, 0), s.unidad_id, 1),
    s.delegacion_id = CASE
        WHEN COALESCE(NULLIF(h.unidad_org_id, 0), s.unidad_id, 1) = 2
            THEN COALESCE(h.delegacion_id, s.delegacion_id)
        ELSE s.delegacion_id
    END,
    s.updated_at = NOW()
WHERE s.unidad_id IS NULL
   OR (COALESCE(NULLIF(h.unidad_org_id, 0), s.unidad_id, 1) = 2 AND s.delegacion_id IS NULL);

UPDATE servicios s
JOIN actividad_vehiculo av ON av.vehiculo_id = s.vehiculo_id
JOIN actividades a ON a.id = av.actividad_id
SET
    s.unidad_id = COALESCE(NULLIF(a.unidad_org_id, 0), s.unidad_id, 1),
    s.delegacion_id = COALESCE(a.delegacion_id, s.delegacion_id),
    s.updated_at = NOW()
WHERE s.unidad_id IS NULL
   OR s.delegacion_id IS NULL;

UPDATE servicios s
JOIN (
    SELECT
        grua_id,
        MIN(delegacion_id) AS delegacion_id
    FROM delegacion_grua
    GROUP BY grua_id
    HAVING COUNT(DISTINCT delegacion_id) = 1
) dg ON dg.grua_id = s.grua_id
SET
    s.unidad_id = COALESCE(s.unidad_id, 2),
    s.delegacion_id = dg.delegacion_id,
    s.updated_at = NOW()
WHERE s.unidad_id = 2
  AND s.delegacion_id IS NULL;

/* Fusiona asignaciones de unidades y delegaciones. */
INSERT IGNORE INTO unidad_grua (unidad_id, grua_id, created_at, updated_at)
SELECT DISTINCT ug.unidad_id, m.master_id, COALESCE(ug.created_at, NOW()), NOW()
FROM unidad_grua ug
JOIN tmp_grua_merge_map m ON m.duplicate_id = ug.grua_id;

DELETE ug
FROM unidad_grua ug
JOIN tmp_grua_merge_map m ON m.duplicate_id = ug.grua_id;

INSERT IGNORE INTO delegacion_grua (delegacion_id, grua_id, created_at, updated_at)
SELECT DISTINCT dg.delegacion_id, m.master_id, COALESCE(dg.created_at, NOW()), NOW()
FROM delegacion_grua dg
JOIN tmp_grua_merge_map m ON m.duplicate_id = dg.grua_id;

DELETE dg
FROM delegacion_grua dg
JOIN tmp_grua_merge_map m ON m.duplicate_id = dg.grua_id;

INSERT IGNORE INTO grua_tramo (grua_id, tramo_id, desde, hasta, prioridad, activo, created_at, updated_at)
SELECT m.master_id, gt.tramo_id, gt.desde, gt.hasta, gt.prioridad, gt.activo, COALESCE(gt.created_at, NOW()), NOW()
FROM grua_tramo gt
JOIN tmp_grua_merge_map m ON m.duplicate_id = gt.grua_id;

DELETE gt
FROM grua_tramo gt
JOIN tmp_grua_merge_map m ON m.duplicate_id = gt.grua_id;

/* Mueve todos los historicos y referencias operativas al ID canonico. */
UPDATE servicios s
JOIN tmp_grua_merge_map m ON m.duplicate_id = s.grua_id
SET s.grua_id = m.master_id, s.updated_at = NOW();

UPDATE vehiculos v
JOIN tmp_grua_merge_map m ON m.duplicate_id = v.grua_id
SET v.grua_id = m.master_id, v.updated_at = NOW();

UPDATE liberaciones_corralon lc
JOIN tmp_grua_merge_map m ON m.duplicate_id = lc.grua_id
SET lc.grua_id = m.master_id, lc.updated_at = NOW();

UPDATE grua_usuarios gu
JOIN tmp_grua_merge_map m ON m.duplicate_id = gu.grua_id
SET gu.grua_id = m.master_id, gu.updated_at = NOW();

UPDATE grua_guardias gg
JOIN tmp_grua_merge_map m ON m.duplicate_id = gg.grua_id
SET gg.grua_id = m.master_id, gg.updated_at = NOW();

UPDATE grua_guardias_sct gs
JOIN tmp_grua_merge_map m ON m.duplicate_id = gs.grua_id
SET gs.grua_id = m.master_id, gs.updated_at = NOW();

/* Conserva metadatos no vacios si el registro canonico los tenia en blanco. */
UPDATE gruas gm
JOIN (
    SELECT
        m.master_id,
        SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(g.direccion, '') ORDER BY g.id SEPARATOR '|||'), '|||', 1) AS direccion,
        SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(g.ubicacion_corralon, '') ORDER BY g.id SEPARATOR '|||'), '|||', 1) AS ubicacion_corralon,
        SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(g.telefono, '') ORDER BY g.id SEPARATOR '|||'), '|||', 1) AS telefono,
        SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(g.email, '') ORDER BY g.id SEPARATOR '|||'), '|||', 1) AS email
    FROM tmp_grua_merge_map m
    JOIN gruas g ON g.id = m.duplicate_id
    GROUP BY m.master_id
) src ON src.master_id = gm.id
SET
    gm.direccion = COALESCE(NULLIF(gm.direccion, ''), src.direccion),
    gm.ubicacion_corralon = COALESCE(NULLIF(gm.ubicacion_corralon, ''), src.ubicacion_corralon),
    gm.telefono = COALESCE(NULLIF(gm.telefono, ''), src.telefono),
    gm.email = COALESCE(NULLIF(gm.email, ''), src.email),
    gm.updated_at = NOW();

/* Elimina solo las gruas duplicadas despues de mover referencias. */
DELETE g
FROM gruas g
JOIN tmp_grua_merge_map m ON m.duplicate_id = g.id;

/* Verificacion final: deben ser 0 huerfanos. */
SELECT COUNT(*) AS servicios_sin_grua
FROM servicios s
LEFT JOIN gruas g ON g.id = s.grua_id
WHERE g.id IS NULL;

SELECT COUNT(*) AS vehiculos_sin_grua_catalogo
FROM vehiculos v
LEFT JOIN gruas g ON g.id = v.grua_id
WHERE v.grua_id IS NOT NULL
  AND g.id IS NULL;

SELECT unidad_id, delegacion_id, COUNT(*) AS servicios
FROM servicios
GROUP BY unidad_id, delegacion_id
ORDER BY unidad_id, delegacion_id;

SELECT
    UPPER(TRIM(nombre)) AS nombre_norm,
    COUNT(*) AS gruas_restantes,
    GROUP_CONCAT(id ORDER BY id) AS ids
FROM gruas
GROUP BY UPPER(TRIM(nombre))
HAVING COUNT(*) > 1
ORDER BY gruas_restantes DESC, nombre_norm;

COMMIT;
