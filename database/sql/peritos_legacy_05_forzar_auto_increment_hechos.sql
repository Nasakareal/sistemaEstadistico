/*
  Fuerza el AUTO_INCREMENT de hechos despues de reordenar IDs legacy.

  Usar cuando peritos_legacy_04_reordenar_ids_hechos.sql ya termino bien,
  pero SHOW TABLE STATUS sigue mostrando un Auto_increment alto.
*/

USE sistemaestadistico;

SET @next_hecho_id := (
    SELECT COALESCE(MAX(id), 0) + 1
    FROM hechos
);

SELECT
    'ANTES_FORZAR_AUTO_INCREMENT' AS seccion,
    COUNT(*) AS hechos_totales,
    MIN(id) AS id_min,
    MAX(id) AS id_max,
    @next_hecho_id AS auto_increment_deseado
FROM hechos;

SET @sql_auto_increment := CONCAT('ALTER TABLE hechos AUTO_INCREMENT = ', @next_hecho_id);
PREPARE stmt_auto_increment FROM @sql_auto_increment;
EXECUTE stmt_auto_increment;
DEALLOCATE PREPARE stmt_auto_increment;

SET @auto_increment_actual := (
    SELECT AUTO_INCREMENT
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'hechos'
);

SET @sql_rebuild_auto_increment := IF(
    @auto_increment_actual = @next_hecho_id,
    'SELECT ''AUTO_INCREMENT_SIMPLE_OK'' AS seccion',
    CONCAT('ALTER TABLE hechos ENGINE=InnoDB, AUTO_INCREMENT = ', @next_hecho_id)
);

PREPARE stmt_rebuild_auto_increment FROM @sql_rebuild_auto_increment;
EXECUTE stmt_rebuild_auto_increment;
DEALLOCATE PREPARE stmt_rebuild_auto_increment;

SELECT
    'DESPUES_FORZAR_AUTO_INCREMENT' AS seccion,
    COUNT(*) AS hechos_totales,
    MIN(id) AS id_min,
    MAX(id) AS id_max,
    @next_hecho_id AS auto_increment_deseado,
    (
        SELECT AUTO_INCREMENT
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'hechos'
    ) AS auto_increment_actual
FROM hechos;

SHOW TABLE STATUS LIKE 'hechos';
