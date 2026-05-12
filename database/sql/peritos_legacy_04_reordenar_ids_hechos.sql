/*
  Reordena los IDs de hechos despues de importar Peritos legacy.

  Objetivo:
  - Los hechos importados desde peritos_legacy vuelven a usar old_hecho_id.
  - Los hechos actuales creados despues del import se compactan al siguiente ID libre real.
  - Se actualizan automaticamente las columnas hecho_id y new_hecho_id del esquema actual.
  - Se recalcula AUTO_INCREMENT de hechos.

  Requisitos:
  - Haber ejecutado peritos_legacy_02_importar_limpio.sql.
  - Tener respaldo completo antes de correr esto en produccion.
  - Evitar capturas nuevas mientras corre este script.
*/

USE sistemaestadistico;

DELIMITER $$

DROP PROCEDURE IF EXISTS legacy_peritos_reordenar_hechos_ids $$

CREATE PROCEDURE legacy_peritos_reordenar_hechos_ids()
BEGIN
    DECLARE v_done TINYINT DEFAULT 0;
    DECLARE v_table_name VARCHAR(128);
    DECLARE v_column_name VARCHAR(128);
    DECLARE v_legacy_count BIGINT DEFAULT 0;
    DECLARE v_legacy_min_new BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_legacy_max_new BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_max_actual_pre BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_max_legacy_old BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_base_final BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_target_conflicts BIGINT DEFAULT 0;
    DECLARE v_invalid_refs BIGINT DEFAULT 0;
    DECLARE v_legacy_wrong BIGINT DEFAULT 0;
    DECLARE v_temp_base BIGINT UNSIGNED DEFAULT 0;
    DECLARE v_next_auto_increment BIGINT UNSIGNED DEFAULT 0;

    DECLARE cur_hecho_ref_cols CURSOR FOR
        SELECT c.TABLE_NAME, c.COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS c
        JOIN INFORMATION_SCHEMA.TABLES t
            ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
            AND t.TABLE_NAME = c.TABLE_NAME
        WHERE c.TABLE_SCHEMA = DATABASE()
            AND t.TABLE_TYPE = 'BASE TABLE'
            AND c.COLUMN_NAME IN ('hecho_id', 'new_hecho_id')
        ORDER BY c.TABLE_NAME, c.COLUMN_NAME;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET FOREIGN_KEY_CHECKS = 1;
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT
        COUNT(*),
        COALESCE(MIN(new_hecho_id), 0),
        COALESCE(MAX(new_hecho_id), 0)
    INTO v_legacy_count, v_legacy_min_new, v_legacy_max_new
    FROM legacy_peritos_import_hechos
    WHERE new_hecho_id IS NOT NULL;

    IF v_legacy_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No hay mapeo legacy_peritos_import_hechos con new_hecho_id.';
    END IF;

    DROP TEMPORARY TABLE IF EXISTS tmp_hecho_id_reorden;
    CREATE TEMPORARY TABLE tmp_hecho_id_reorden (
        old_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        new_id BIGINT UNSIGNED NOT NULL UNIQUE,
        temp_id BIGINT UNSIGNED NULL UNIQUE,
        motivo VARCHAR(40) NOT NULL
    ) ENGINE=InnoDB;

    INSERT INTO tmp_hecho_id_reorden (old_id, new_id, motivo)
    SELECT
        mh.new_hecho_id AS old_id,
        mh.old_hecho_id AS new_id,
        'legacy_old_id' AS motivo
    FROM legacy_peritos_import_hechos mh
    JOIN hechos h ON h.id = mh.new_hecho_id
    WHERE h.fuente_ubicacion = 'legacy_peritos'
        AND mh.new_hecho_id <> mh.old_hecho_id;

    SELECT COUNT(*)
    INTO v_target_conflicts
    FROM tmp_hecho_id_reorden m
    JOIN hechos h ON h.id = m.new_id
    LEFT JOIN tmp_hecho_id_reorden moving ON moving.old_id = h.id
    WHERE moving.old_id IS NULL;

    IF v_target_conflicts > 0 THEN
        SELECT
            'CONFLICTOS_TARGET_ID_OCUPADO' AS seccion,
            m.old_id AS id_actual_legacy,
            m.new_id AS id_legacy_deseado,
            h.folio_c5i,
            h.fecha,
            h.fuente_ubicacion
        FROM tmp_hecho_id_reorden m
        JOIN hechos h ON h.id = m.new_id
        LEFT JOIN tmp_hecho_id_reorden moving ON moving.old_id = h.id
        WHERE moving.old_id IS NULL
        ORDER BY m.new_id
        LIMIT 50;

        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hay hechos actuales ocupando IDs antiguos; revisar CONFLICTOS_TARGET_ID_OCUPADO.';
    END IF;

    SELECT COALESCE(MAX(id), 0)
    INTO v_max_actual_pre
    FROM hechos
    WHERE COALESCE(fuente_ubicacion, '') <> 'legacy_peritos'
        AND id < v_legacy_min_new;

    SELECT COALESCE(MAX(old_hecho_id), 0)
    INTO v_max_legacy_old
    FROM legacy_peritos_import_hechos
    WHERE new_hecho_id IS NOT NULL;

    SET v_base_final = GREATEST(v_max_actual_pre, v_max_legacy_old);

    INSERT INTO tmp_hecho_id_reorden (old_id, new_id, motivo)
    SELECT
        post_import.id AS old_id,
        v_base_final + ROW_NUMBER() OVER (ORDER BY post_import.created_at, post_import.id) AS new_id,
        'actual_post_import' AS motivo
    FROM hechos post_import
    WHERE COALESCE(post_import.fuente_ubicacion, '') <> 'legacy_peritos'
        AND post_import.id >= v_legacy_min_new;

    SELECT COALESCE(MAX(id), 0) + 1000000
    INTO v_temp_base
    FROM hechos;

    UPDATE tmp_hecho_id_reorden
    SET temp_id = old_id + v_temp_base;

    SELECT
        'PLAN_REORDEN_IDS_HECHOS' AS seccion,
        v_legacy_count AS hechos_legacy_mapeados,
        v_legacy_min_new AS legacy_id_actual_min,
        v_legacy_max_new AS legacy_id_actual_max,
        v_max_actual_pre AS max_id_actual_antes_del_import,
        v_max_legacy_old AS max_old_hecho_id_legacy,
        v_base_final AS base_para_actuales_post_import;

    SELECT
        motivo,
        COUNT(*) AS filas_a_mover,
        MIN(old_id) AS id_actual_min,
        MAX(old_id) AS id_actual_max,
        MIN(new_id) AS id_final_min,
        MAX(new_id) AS id_final_max
    FROM tmp_hecho_id_reorden
    GROUP BY motivo
    ORDER BY motivo;

    START TRANSACTION;
    SET FOREIGN_KEY_CHECKS = 0;

    SET v_done = 0;
    OPEN cur_hecho_ref_cols;
    ref_phase_1: LOOP
        FETCH cur_hecho_ref_cols INTO v_table_name, v_column_name;

        IF v_done = 1 THEN
            LEAVE ref_phase_1;
        END IF;

        SET @legacy_reorden_sql = CONCAT(
            'UPDATE `', REPLACE(v_table_name, '`', '``'), '` t ',
            'JOIN tmp_hecho_id_reorden m ON t.`', REPLACE(v_column_name, '`', '``'), '` = m.old_id ',
            'SET t.`', REPLACE(v_column_name, '`', '``'), '` = m.temp_id'
        );
        PREPARE legacy_reorden_stmt FROM @legacy_reorden_sql;
        EXECUTE legacy_reorden_stmt;
        DEALLOCATE PREPARE legacy_reorden_stmt;
    END LOOP;
    CLOSE cur_hecho_ref_cols;

    UPDATE hechos h
    JOIN tmp_hecho_id_reorden m ON h.id = m.old_id
    SET h.id = m.temp_id;

    SET v_done = 0;
    OPEN cur_hecho_ref_cols;
    ref_phase_2: LOOP
        FETCH cur_hecho_ref_cols INTO v_table_name, v_column_name;

        IF v_done = 1 THEN
            LEAVE ref_phase_2;
        END IF;

        SET @legacy_reorden_sql = CONCAT(
            'UPDATE `', REPLACE(v_table_name, '`', '``'), '` t ',
            'JOIN tmp_hecho_id_reorden m ON t.`', REPLACE(v_column_name, '`', '``'), '` = m.temp_id ',
            'SET t.`', REPLACE(v_column_name, '`', '``'), '` = m.new_id'
        );
        PREPARE legacy_reorden_stmt FROM @legacy_reorden_sql;
        EXECUTE legacy_reorden_stmt;
        DEALLOCATE PREPARE legacy_reorden_stmt;
    END LOOP;
    CLOSE cur_hecho_ref_cols;

    UPDATE hechos h
    JOIN tmp_hecho_id_reorden m ON h.id = m.temp_id
    SET h.id = m.new_id;

    DROP TEMPORARY TABLE IF EXISTS tmp_hecho_id_invalid_refs;
    CREATE TEMPORARY TABLE tmp_hecho_id_invalid_refs (
        tabla VARCHAR(128) NOT NULL,
        columna VARCHAR(128) NOT NULL,
        total BIGINT NOT NULL
    ) ENGINE=InnoDB;

    SET v_done = 0;
    OPEN cur_hecho_ref_cols;
    validate_refs: LOOP
        FETCH cur_hecho_ref_cols INTO v_table_name, v_column_name;

        IF v_done = 1 THEN
            LEAVE validate_refs;
        END IF;

        SET @legacy_reorden_sql = CONCAT(
            'INSERT INTO tmp_hecho_id_invalid_refs (tabla, columna, total) ',
            'SELECT ', QUOTE(v_table_name), ', ', QUOTE(v_column_name), ', COUNT(*) ',
            'FROM `', REPLACE(v_table_name, '`', '``'), '` t ',
            'LEFT JOIN hechos h ON h.id = t.`', REPLACE(v_column_name, '`', '``'), '` ',
            'WHERE t.`', REPLACE(v_column_name, '`', '``'), '` IS NOT NULL AND h.id IS NULL'
        );
        PREPARE legacy_reorden_stmt FROM @legacy_reorden_sql;
        EXECUTE legacy_reorden_stmt;
        DEALLOCATE PREPARE legacy_reorden_stmt;
    END LOOP;
    CLOSE cur_hecho_ref_cols;

    SELECT COUNT(*)
    INTO v_invalid_refs
    FROM tmp_hecho_id_invalid_refs
    WHERE total > 0;

    IF v_invalid_refs > 0 THEN
        SELECT 'REFERENCIAS_ROTAS' AS seccion, tabla, columna, total
        FROM tmp_hecho_id_invalid_refs
        WHERE total > 0
        ORDER BY tabla, columna;

        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La validacion encontro referencias hecho_id/new_hecho_id rotas.';
    END IF;

    SELECT COUNT(*)
    INTO v_legacy_wrong
    FROM legacy_peritos_import_hechos mh
    JOIN hechos h ON h.id = mh.new_hecho_id
    WHERE h.fuente_ubicacion = 'legacy_peritos'
        AND mh.new_hecho_id <> mh.old_hecho_id;

    IF v_legacy_wrong > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quedaron hechos legacy sin regresar a old_hecho_id.';
    END IF;

    SET FOREIGN_KEY_CHECKS = 1;
    COMMIT;

    SELECT COALESCE(MAX(id), 0) + 1
    INTO v_next_auto_increment
    FROM hechos;

    SET @legacy_reorden_sql = CONCAT('ALTER TABLE hechos AUTO_INCREMENT = ', v_next_auto_increment);
    PREPARE legacy_reorden_stmt FROM @legacy_reorden_sql;
    EXECUTE legacy_reorden_stmt;
    DEALLOCATE PREPARE legacy_reorden_stmt;

    SELECT
        'REORDEN_COMPLETADO' AS seccion,
        COUNT(*) AS hechos_totales,
        MIN(id) AS id_min,
        MAX(id) AS id_max,
        v_next_auto_increment AS siguiente_auto_increment
    FROM hechos;

    SELECT
        'LEGACY_REORDENADO' AS seccion,
        COUNT(*) AS hechos_legacy,
        MIN(h.id) AS id_min,
        MAX(h.id) AS id_max
    FROM hechos h
    WHERE h.fuente_ubicacion = 'legacy_peritos';

    SELECT
        'ACTUALES' AS seccion,
        COUNT(*) AS hechos_actuales,
        MIN(h.id) AS id_min,
        MAX(h.id) AS id_max
    FROM hechos h
    WHERE COALESCE(h.fuente_ubicacion, '') <> 'legacy_peritos';
END $$

CALL legacy_peritos_reordenar_hechos_ids() $$

DROP PROCEDURE IF EXISTS legacy_peritos_reordenar_hechos_ids $$

DELIMITER ;

SHOW TABLE STATUS LIKE 'hechos';
