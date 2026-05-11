/*
  Importacion limpia del respaldo antiguo de Peritos.

  IMPORTANTE:
  - Ejecutar primero en una copia/staging.
  - El dump viejo debe estar importado en una base separada llamada peritos_legacy.
  - Ejecutar dentro de la base actual del sistema, normalmente sistemaestadistico.
  - NO migra usuarios antiguos. Los hechos importados se asignan a @usuario_importacion_id.
  - Es re-ejecutable: usa client_uuid deterministico y tablas de mapeo legacy_peritos_import_*.
*/

USE sistemaestadistico;

SET @usuario_importacion_id := 1;
SET @unidad_siniestros_id := 1;
SET @ahora_importacion := NOW();
SET @fecha_importacion_default := DATE('1900-01-01');
SET @legacy_notificaciones_silenciadas_at := TIMESTAMP('2099-01-01 00:00:00');
SET @legacy_sql_mode_original := @@SESSION.sql_mode;
SET SESSION sql_mode = TRIM(BOTH ',' FROM REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(',', @@SESSION.sql_mode, ','), ',NO_ZERO_DATE,', ','), ',NO_ZERO_IN_DATE,', ','), ',,', ','), ',,', ','));

CREATE TABLE IF NOT EXISTS legacy_peritos_import_hechos (
    old_hecho_id INT NOT NULL PRIMARY KEY,
    new_hecho_id BIGINT UNSIGNED NULL UNIQUE,
    folio_original VARCHAR(50) NULL,
    folio_norm VARCHAR(50) NULL,
    folio_importado VARCHAR(20) NULL,
    folio_es_duplicado TINYINT(1) NOT NULL DEFAULT 0,
    folio_conflicta_con_actual TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY legacy_peritos_import_hechos_folio_idx (folio_importado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_peritos_import_vehiculos (
    old_vehiculo_id INT NOT NULL PRIMARY KEY,
    old_hecho_id INT NOT NULL,
    old_vehiculo_winner_id INT NOT NULL,
    new_vehiculo_id BIGINT UNSIGNED NULL,
    new_hecho_id BIGINT UNSIGNED NULL,
    duplicate_reason VARCHAR(20) NULL,
    placas_key VARCHAR(30) NULL,
    serie_key VARCHAR(40) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY legacy_peritos_import_vehiculos_winner_idx (old_vehiculo_winner_id),
    KEY legacy_peritos_import_vehiculos_new_idx (new_vehiculo_id),
    KEY legacy_peritos_import_vehiculos_new_hecho_idx (new_hecho_id, new_vehiculo_id),
    KEY legacy_peritos_import_vehiculos_hecho_idx (old_hecho_id, new_hecho_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_peritos_import_conductores (
    old_hecho_id INT NOT NULL,
    conductor_key VARCHAR(255) NOT NULL,
    representative_old_vehiculo_id INT NOT NULL,
    new_conductor_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (old_hecho_id, conductor_key),
    KEY legacy_peritos_import_conductores_new_idx (new_conductor_id),
    KEY legacy_peritos_import_conductores_rep_idx (representative_old_vehiculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_peritos_import_lesionados (
    old_persona_id INT NOT NULL PRIMARY KEY,
    old_hecho_id INT NOT NULL,
    new_hecho_id BIGINT UNSIGNED NULL,
    new_lesionado_id BIGINT UNSIGNED NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY legacy_peritos_import_lesionados_hecho_idx (old_hecho_id, new_hecho_id),
    KEY legacy_peritos_import_lesionados_new_hecho_idx (new_hecho_id, new_lesionado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_hechos;

CREATE TEMPORARY TABLE tmp_legacy_hechos AS
SELECT
    a.id_accidentes AS old_hecho_id,
    a.*,
    UPPER(TRIM(COALESCE(a.folioc5, ''))) AS folio_norm,
    UPPER(REGEXP_REPLACE(COALESCE(a.folioc5, ''), '[^A-Za-z0-9]', '')) AS folio_placeholder_key,
    CASE
        WHEN UPPER(TRIM(COALESCE(a.folioc5, ''))) IN ('', '0', '00', '000', 'S', 'S/D', 'SD', 'S/N', 'SN', 'S/F', 'SF', 'SIN', 'SIN DATOS', 'SIN DATO', 'SIN FOLIO', 'SIN NUMERO', 'SIN NÚMERO', 'N/A', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', '-', '--', 'C5', 'C5I') THEN 1
        WHEN UPPER(REGEXP_REPLACE(COALESCE(a.folioc5, ''), '[^A-Za-z0-9]', '')) IN ('', '0', '00', '000', 'S', 'SD', 'SN', 'SF', 'SIN', 'SINDATOS', 'SINDATO', 'SINFOLIO', 'SINNUMERO', 'SINNMERO', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', 'C5', 'C5I') THEN 1
        ELSE 0
    END AS folio_es_placeholder,
    ROW_NUMBER() OVER (
        PARTITION BY UPPER(TRIM(COALESCE(a.folioc5, '')))
        ORDER BY a.id_accidentes
    ) AS folio_rank
FROM peritos_legacy.accidentest a
WHERE COALESCE(a.borrado, 0) = 0;

INSERT IGNORE INTO legacy_peritos_import_hechos (
    old_hecho_id,
    folio_original,
    folio_norm,
    folio_importado,
    folio_es_duplicado,
    folio_conflicta_con_actual
)
SELECT
    th.old_hecho_id,
    NULLIF(TRIM(th.folioc5), ''),
    NULLIF(th.folio_norm, ''),
    CASE
        WHEN th.folio_es_placeholder = 1 THEN NULL
        WHEN th.folio_rank = 1
            AND NOT EXISTS (
                SELECT 1
                FROM hechos h
                WHERE h.folio_c5i = LEFT(th.folio_norm, 20)
            )
            THEN LEFT(th.folio_norm, 20)
        ELSE CONCAT('LEG', th.old_hecho_id)
    END AS folio_importado,
    CASE WHEN th.folio_es_placeholder = 0 AND th.folio_rank > 1 THEN 1 ELSE 0 END AS folio_es_duplicado,
    CASE
        WHEN th.folio_es_placeholder = 0
            AND EXISTS (
                SELECT 1
                FROM hechos h
                WHERE h.folio_c5i = LEFT(th.folio_norm, 20)
            )
            THEN 1
        ELSE 0
    END AS folio_conflicta_con_actual
FROM tmp_legacy_hechos th;

INSERT IGNORE INTO hechos (
    client_uuid,
    folio_c5i,
    perito,
    autorizacion_practico,
    unidad,
    unidad_org_id,
    hora,
    fecha,
    sector,
    calle,
    calle_norm,
    colonia,
    entre_calles,
    municipio,
    tipo_hecho,
    superficie_via,
    tiempo,
    clima,
    condiciones,
    control_transito,
    checaron_antecedentes,
    causas,
    responsable,
    colision_camino,
    situacion,
    oficio_mp,
    vehiculos_mp,
    personas_mp,
    danos_patrimoniales,
    propiedades_afectadas,
    monto_danos_patrimoniales,
    lat,
    lng,
    calidad_geo,
    nota_geo,
    fuente_ubicacion,
    ubicacion_formateada,
    created_by,
    updated_by,
    created_at,
    updated_at,
    notificado_48_at,
    notificado_72_at,
    ultimo_recordatorio_72_at
)
SELECT
    LOWER(CONCAT(
        SUBSTR(MD5(CONCAT('peritos-hecho-', th.old_hecho_id)), 1, 8), '-',
        SUBSTR(MD5(CONCAT('peritos-hecho-', th.old_hecho_id)), 9, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-hecho-', th.old_hecho_id)), 13, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-hecho-', th.old_hecho_id)), 17, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-hecho-', th.old_hecho_id)), 21, 12)
    )) AS client_uuid,
    mh.folio_importado,
    LEFT(COALESCE(NULLIF(TRIM(th.perito), ''), 'SIN DATO'), 255),
    LEFT(NULLIF(TRIM(th.npractico), ''), 255),
    LEFT(COALESCE(NULLIF(TRIM(th.unidad), ''), 'SIN DATO'), 50),
    @unidad_siniestros_id,
    CASE
        WHEN th.hora REGEXP '^[0-9]{1,2}:[0-9]{2}' THEN CAST(CONCAT(LEFT(th.hora, 5), ':00') AS TIME)
        WHEN th.hora REGEXP '^[0-9]{4}$' THEN STR_TO_DATE(th.hora, '%H%i')
        ELSE '00:00:00'
    END,
    CASE
        WHEN th.fecha IS NULL OR th.fecha = '0000-00-00' THEN @fecha_importacion_default
        ELSE th.fecha
    END,
    LEFT(COALESCE(NULLIF(TRIM(CAST(th.sector AS CHAR)), ''), '0'), 100),
    LEFT(COALESCE(NULLIF(TRIM(th.callea), ''), 'SIN DATO'), 255),
    LEFT(UPPER(COALESCE(NULLIF(TRIM(th.callea), ''), 'SIN DATO')), 255),
    LEFT(COALESCE(NULLIF(TRIM(th.colonia), ''), 'SIN DATO'), 255),
    LEFT(NULLIF(TRIM(th.calleb), ''), 255),
    LEFT(COALESCE(NULLIF(TRIM(th.ciudad), ''), 'SIN DATO'), 100),
    CASE th.tipo_incidente_id_incidente
        WHEN 2 THEN 'COLISIÓN CON PEATÓN'
        WHEN 3 THEN 'VOLCADURA'
        WHEN 4 THEN 'SALIDA DE SUPERFICIE DE RODAMIENTO'
        WHEN 5 THEN 'SUBIDA AL CAMELLÓN'
        WHEN 6 THEN 'CAIDA ACUATICA DE VEHÍCULO'
        WHEN 8 THEN 'CAIDA DE MOTOCICLETA'
        WHEN 10 THEN 'COLISIÓN CONTRA OBJETO FIJO'
        WHEN 12 THEN 'EXPLOSIÓN'
        WHEN 13 THEN 'INCENDIO'
        WHEN 15 THEN 'DESBARRANCAMIENTO'
        WHEN 17 THEN 'COLISIÓN POR ALCANCE'
        WHEN 18 THEN 'COLISIÓN POR NO RESPETAR SEMÁFORO'
        WHEN 19 THEN 'COLISIÓN POR CAMBIO DE CARRIL'
        WHEN 20 THEN 'COLISIÓN POR INVASIÓN DE CARRIL'
        WHEN 21 THEN 'COLISIÓN POR CORTE DE CIRCULACIÓN'
        WHEN 22 THEN 'COLISIÓN POR MANIOBRA DE REVERSA'
        WHEN 23 THEN 'COLISIÓN CON PEATÓN'
        WHEN 24 THEN 'COLISIÓN CON PEATÓN'
        ELSE 'Otro'
    END,
    LEFT(COALESCE(NULLIF(TRIM(th.superficie_via), ''), 'SIN DATO'), 50),
    CASE UPPER(TRIM(COALESCE(th.tiempo, '')))
        WHEN 'DIA' THEN 'Día'
        WHEN 'DÍA' THEN 'Día'
        WHEN 'NOCHE' THEN 'Noche'
        WHEN 'AMANECER' THEN 'Amanecer'
        WHEN 'ATARDECER' THEN 'Atardecer'
        ELSE 'Día'
    END,
    CASE UPPER(TRIM(COALESCE(th.clima, '')))
        WHEN 'BUENO' THEN 'Bueno'
        WHEN 'NUBLADO' THEN 'Nublado'
        WHEN 'LLUVIA' THEN 'Lluvioso'
        WHEN 'LLOVIZNA' THEN 'Lluvioso'
        WHEN 'MALO' THEN 'Malo'
        ELSE 'Bueno'
    END,
    CASE UPPER(TRIM(COALESCE(th.condiciones, '')))
        WHEN 'B' THEN 'Bueno'
        WHEN 'BUENO' THEN 'Bueno'
        WHEN 'R' THEN 'Regular'
        WHEN 'REGULAR' THEN 'Regular'
        WHEN 'M' THEN 'Malo'
        WHEN 'MALO' THEN 'Malo'
        ELSE 'Bueno'
    END,
    LEFT(COALESCE(NULLIF(TRIM(th.cont_transito), ''), 'SIN DATO'), 50),
    CASE WHEN COALESCE(th.antecedentes, 0) > 0 THEN 1 ELSE 0 END,
    LEFT(COALESCE(NULLIF(TRIM(th.circunstancias), ''), 'SIN DATO'), 255),
    NULL,
    LEFT(COALESCE(NULLIF(TRIM(th.colision_sob_cam), ''), 'SIN DATO'), 255),
    CASE UPPER(TRIM(COALESCE(th.situacion, '')))
        WHEN 'RESUELTO' THEN 'RESUELTO'
        WHEN 'TURNADO' THEN 'TURNADO'
        WHEN 'PENDIENTE' THEN 'PENDIENTE'
        ELSE 'PENDIENTE'
    END,
    LEFT(NULLIF(TRIM(th.oficio_mp), ''), 255),
    COALESCE(th.vehiculosmp, 0),
    COALESCE(th.personasmp, 0),
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULLIF(TRIM(CAST(th.calidad_geo AS CHAR)), ''),
    NULLIF(TRIM(CAST(th.nota_geo AS CHAR)), ''),
    'legacy_peritos',
    LEFT(NULLIF(TRIM(th.domicilio), ''), 65535),
    @usuario_importacion_id,
    @usuario_importacion_id,
    CASE
        WHEN th.fecha_capt IS NOT NULL AND th.fecha_capt <> '0000-00-00' THEN TIMESTAMP(th.fecha_capt)
        WHEN th.fecha IS NOT NULL AND th.fecha <> '0000-00-00' THEN TIMESTAMP(th.fecha)
        ELSE TIMESTAMP(@fecha_importacion_default)
    END,
    @legacy_notificaciones_silenciadas_at,
    @legacy_notificaciones_silenciadas_at,
    @legacy_notificaciones_silenciadas_at,
    @ahora_importacion
FROM tmp_legacy_hechos th
JOIN legacy_peritos_import_hechos mh ON mh.old_hecho_id = th.old_hecho_id
WHERE mh.new_hecho_id IS NULL;

UPDATE legacy_peritos_import_hechos mh
JOIN hechos h ON h.client_uuid = LOWER(CONCAT(
    SUBSTR(MD5(CONCAT('peritos-hecho-', mh.old_hecho_id)), 1, 8), '-',
    SUBSTR(MD5(CONCAT('peritos-hecho-', mh.old_hecho_id)), 9, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-hecho-', mh.old_hecho_id)), 13, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-hecho-', mh.old_hecho_id)), 17, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-hecho-', mh.old_hecho_id)), 21, 12)
))
SET mh.new_hecho_id = h.id
WHERE mh.new_hecho_id IS NULL;

UPDATE hechos h
JOIN legacy_peritos_import_hechos mh ON mh.new_hecho_id = h.id
SET
    h.notificado_48_at = COALESCE(h.notificado_48_at, @legacy_notificaciones_silenciadas_at),
    h.notificado_72_at = COALESCE(h.notificado_72_at, @legacy_notificaciones_silenciadas_at),
    h.ultimo_recordatorio_72_at = COALESCE(h.ultimo_recordatorio_72_at, @legacy_notificaciones_silenciadas_at)
WHERE mh.new_hecho_id IS NOT NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_vehiculos_base;

CREATE TEMPORARY TABLE tmp_legacy_vehiculos_base AS
SELECT
    v.*,
    CASE
        WHEN UPPER(REGEXP_REPLACE(COALESCE(v.placas, ''), '[^A-Za-z0-9]', '')) IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SPLACA', 'SPLACAS', 'SINPLACA', 'SINPLACAS', 'SINPLAC', 'SINPLCAS', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'SINDOCUMENTO', 'NOPRESENTA', 'NOPRESENTO', 'PERMISO', 'ENPERMISO') THEN NULL
        WHEN CHAR_LENGTH(UPPER(REGEXP_REPLACE(COALESCE(v.placas, ''), '[^A-Za-z0-9]', ''))) < 3 THEN NULL
        ELSE LEFT(UPPER(REGEXP_REPLACE(COALESCE(v.placas, ''), '[^A-Za-z0-9]', '')), 15)
    END AS placas_key,
    CASE
        WHEN UPPER(REGEXP_REPLACE(COALESCE(v.noserie, ''), '[^A-Za-z0-9]', '')) IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SINNODATOS', 'SINSERIE', 'SINNUMERO', 'SINNUMEROSERIE', 'SINNUMERODESERIE', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'NOSEALCANZAAPRECIAR', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'NOPRESENTA', 'NOPRESENTO', 'ESTACIONADO', 'SEIGNORA', 'NOLOCALIZADO', 'NOLOCALIZADA')
            OR CHAR_LENGTH(UPPER(REGEXP_REPLACE(COALESCE(v.noserie, ''), '[^A-Za-z0-9]', ''))) < 6 THEN NULL
        ELSE LEFT(UPPER(REGEXP_REPLACE(COALESCE(v.noserie, ''), '[^A-Za-z0-9]', '')), 17)
    END AS serie_key
FROM peritos_legacy.vehiculos v
JOIN legacy_peritos_import_hechos mh ON mh.old_hecho_id = v.id_accidentes
WHERE mh.new_hecho_id IS NOT NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_placas_repetidas;

CREATE TEMPORARY TABLE tmp_legacy_placas_repetidas AS
SELECT id_accidentes, placas_key
FROM tmp_legacy_vehiculos_base
WHERE placas_key IS NOT NULL
GROUP BY id_accidentes, placas_key
HAVING COUNT(*) > 1;

ALTER TABLE tmp_legacy_placas_repetidas ADD PRIMARY KEY (id_accidentes, placas_key);

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_series_repetidas;

CREATE TEMPORARY TABLE tmp_legacy_series_repetidas AS
SELECT id_accidentes, serie_key
FROM tmp_legacy_vehiculos_base
WHERE serie_key IS NOT NULL
GROUP BY id_accidentes, serie_key
HAVING COUNT(*) > 1;

ALTER TABLE tmp_legacy_series_repetidas ADD PRIMARY KEY (id_accidentes, serie_key);

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_vehiculos_winners;

CREATE TEMPORARY TABLE tmp_legacy_vehiculos_winners AS
SELECT
    vb.*,
    CASE
        WHEN pr.placas_key IS NOT NULL THEN CONCAT('P:', vb.placas_key)
        WHEN sr.serie_key IS NOT NULL THEN CONCAT('S:', vb.serie_key)
        ELSE CONCAT('ID:', vb.id_vehiculo)
    END AS dedupe_key,
    MIN(vb.id_vehiculo) OVER (
        PARTITION BY vb.id_accidentes,
            CASE
                WHEN pr.placas_key IS NOT NULL THEN CONCAT('P:', vb.placas_key)
                WHEN sr.serie_key IS NOT NULL THEN CONCAT('S:', vb.serie_key)
                ELSE CONCAT('ID:', vb.id_vehiculo)
            END
    ) AS old_vehiculo_winner_id
FROM tmp_legacy_vehiculos_base vb
LEFT JOIN tmp_legacy_placas_repetidas pr
    ON pr.id_accidentes = vb.id_accidentes
    AND pr.placas_key = vb.placas_key
LEFT JOIN tmp_legacy_series_repetidas sr
    ON sr.id_accidentes = vb.id_accidentes
    AND sr.serie_key = vb.serie_key;

INSERT IGNORE INTO legacy_peritos_import_vehiculos (
    old_vehiculo_id,
    old_hecho_id,
    old_vehiculo_winner_id,
    new_hecho_id,
    duplicate_reason,
    placas_key,
    serie_key
)
SELECT
    vw.id_vehiculo,
    vw.id_accidentes,
    vw.old_vehiculo_winner_id,
    mh.new_hecho_id,
    CASE
        WHEN vw.id_vehiculo = vw.old_vehiculo_winner_id THEN NULL
        WHEN vw.dedupe_key LIKE 'P:%' THEN 'placas'
        WHEN vw.dedupe_key LIKE 'S:%' THEN 'serie'
        ELSE NULL
    END,
    vw.placas_key,
    vw.serie_key
FROM tmp_legacy_vehiculos_winners vw
JOIN legacy_peritos_import_hechos mh ON mh.old_hecho_id = vw.id_accidentes;

INSERT IGNORE INTO vehiculos (
    client_uuid,
    marca,
    modelo,
    tipo,
    linea,
    color,
    placas,
    estado_placas,
    serie,
    capacidad_personas,
    tipo_servicio,
    tarjeta_circulacion_nombre,
    grua,
    corralon,
    aseguradora,
    antecedente_vehiculo,
    monto_danos,
    partes_danadas,
    created_at,
    updated_at
)
SELECT
    LOWER(CONCAT(
        SUBSTR(MD5(CONCAT('peritos-vehiculo-', vw.old_vehiculo_winner_id)), 1, 8), '-',
        SUBSTR(MD5(CONCAT('peritos-vehiculo-', vw.old_vehiculo_winner_id)), 9, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-vehiculo-', vw.old_vehiculo_winner_id)), 13, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-vehiculo-', vw.old_vehiculo_winner_id)), 17, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-vehiculo-', vw.old_vehiculo_winner_id)), 21, 12)
    )),
    LEFT(COALESCE(NULLIF(TRIM(vw.marca), ''), 'SIN DATO'), 50),
    LEFT(NULLIF(TRIM(vw.modelo), ''), 10),
    LEFT(COALESCE(NULLIF(TRIM(tv.tipo_vehiculo), ''), NULLIF(TRIM(vw.tipo), ''), 'SIN DATO'), 50),
    LEFT(COALESCE(NULLIF(TRIM(vw.tipo), ''), 'SIN DATO'), 50),
    LEFT(COALESCE(NULLIF(TRIM(vw.color), ''), 'SIN DATO'), 30),
    LEFT(vw.placas_key, 15),
    LEFT(NULLIF(TRIM(vw.entidad), ''), 15),
    LEFT(vw.serie_key, 17),
    COALESCE(CAST(NULLIF(REGEXP_REPLACE(COALESCE(vw.capacidad, ''), '[^0-9]', ''), '') AS UNSIGNED), 0),
    LEFT(COALESCE(NULLIF(TRIM(vw.servicio), ''), 'SIN DATO'), 50),
    LEFT(NULLIF(TRIM(vw.propiedad), ''), 60),
    LEFT(COALESCE(NULLIF(TRIM(vw.grua), ''), 'N/A'), 255),
    LEFT(COALESCE(NULLIF(TRIM(vw.corralon), ''), 'N/A'), 255),
    NULL,
    CASE WHEN COALESCE(vw.ant_vehiculo, 0) > 0 THEN 1 ELSE 0 END,
    CAST(NULLIF(REGEXP_REPLACE(COALESCE(vw.monto, ''), '[^0-9.]', ''), '') AS DECIMAL(10, 2)),
    NULLIF(TRIM(vw.partes_daniadas), ''),
    @ahora_importacion,
    @ahora_importacion
FROM tmp_legacy_vehiculos_winners vw
LEFT JOIN peritos_legacy.tipo_vehiculos tv ON tv.id_vehiculo = vw.clasificacion
JOIN legacy_peritos_import_vehiculos mv ON mv.old_vehiculo_id = vw.id_vehiculo
WHERE vw.id_vehiculo = vw.old_vehiculo_winner_id
  AND mv.new_vehiculo_id IS NULL;

UPDATE legacy_peritos_import_vehiculos mv
JOIN vehiculos v ON v.client_uuid = LOWER(CONCAT(
    SUBSTR(MD5(CONCAT('peritos-vehiculo-', mv.old_vehiculo_winner_id)), 1, 8), '-',
    SUBSTR(MD5(CONCAT('peritos-vehiculo-', mv.old_vehiculo_winner_id)), 9, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-vehiculo-', mv.old_vehiculo_winner_id)), 13, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-vehiculo-', mv.old_vehiculo_winner_id)), 17, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-vehiculo-', mv.old_vehiculo_winner_id)), 21, 12)
))
SET mv.new_vehiculo_id = v.id
WHERE mv.new_vehiculo_id IS NULL;

INSERT IGNORE INTO hecho_vehiculo (hecho_id, vehiculo_id, created_at, updated_at)
SELECT DISTINCT
    mv.new_hecho_id,
    mv.new_vehiculo_id,
    @ahora_importacion,
    @ahora_importacion
FROM legacy_peritos_import_vehiculos mv
WHERE mv.new_hecho_id IS NOT NULL
  AND mv.new_vehiculo_id IS NOT NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_conductores;

CREATE TEMPORARY TABLE tmp_legacy_conductores AS
SELECT
    mv.old_hecho_id,
    UPPER(TRIM(COALESCE(v.conductor, ''))) AS conductor_key,
    MIN(v.id_vehiculo) AS representative_old_vehiculo_id
FROM peritos_legacy.vehiculos v
JOIN legacy_peritos_import_vehiculos mv ON mv.old_vehiculo_id = v.id_vehiculo
WHERE UPPER(TRIM(COALESCE(v.conductor, ''))) NOT IN ('', '0', 'SD', 'S/D', 'SN', 'S/N', 'SIN DATOS', 'SIN DATO', 'SIN NOMBRE', 'SE DESCONOCE', 'SE IGNORA', 'DESCONOCIDO', 'DESCONOCIDA', 'NA', 'N/A', 'NO APLICA', 'NULL')
GROUP BY mv.old_hecho_id, UPPER(TRIM(COALESCE(v.conductor, '')));

INSERT IGNORE INTO legacy_peritos_import_conductores (
    old_hecho_id,
    conductor_key,
    representative_old_vehiculo_id
)
SELECT
    old_hecho_id,
    conductor_key,
    representative_old_vehiculo_id
FROM tmp_legacy_conductores;

INSERT IGNORE INTO conductores (
    client_uuid,
    nombre,
    edad,
    domicilio,
    telefono,
    sexo,
    ocupacion,
    cinturon,
    antecedentes,
    certificado_lesiones,
    certificado_alcoholemia,
    aliento_etilico,
    estado_licencia,
    vigencia_licencia,
    permanente,
    numero_licencia,
    tipo_licencia,
    created_at,
    updated_at
)
SELECT
    LOWER(CONCAT(
        SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 1, 8), '-',
        SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 9, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 13, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 17, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 21, 12)
    )),
    LEFT(mc.conductor_key, 255),
    CAST(NULLIF(REGEXP_REPLACE(COALESCE(v.edad, ''), '[^0-9]', ''), '') AS UNSIGNED),
    LEFT(NULLIF(TRIM(v.domicilio), ''), 255),
    LEFT(NULLIF(TRIM(v.tel), ''), 20),
    CASE
        WHEN UPPER(TRIM(COALESCE(v.sexo, ''))) IN ('M', 'MASCULINO', 'HOMBRE') THEN 'MASCULINO'
        WHEN UPPER(TRIM(COALESCE(v.sexo, ''))) IN ('F', 'FEMENINO', 'MUJER') THEN 'FEMENINO'
        ELSE NULL
    END,
    LEFT(NULLIF(TRIM(v.ocupacion), ''), 255),
    CASE WHEN COALESCE(v.cinturon, 0) > 0 THEN 1 ELSE 0 END,
    CASE WHEN COALESCE(v.ant_persona, 0) > 0 THEN 1 ELSE 0 END,
    CASE WHEN NULLIF(TRIM(COALESCE(v.cert_med, '')), '') IS NOT NULL AND UPPER(TRIM(v.cert_med)) NOT IN ('0', 'NO', 'NO APLICA', 'N/A', 'NA') THEN 1 ELSE 0 END,
    CASE WHEN COALESCE(v.cert_ebriedad, 0) > 0 THEN 1 ELSE 0 END,
    CASE WHEN COALESCE(v.aliento_etil, 0) > 0 THEN 1 ELSE 0 END,
    LEFT(NULLIF(TRIM(v.entidadc), ''), 100),
    NULL,
    0,
    LEFT(NULLIF(TRIM(v.tipolic_no), ''), 50),
    LEFT(NULLIF(TRIM(v.tipolic_no), ''), 50),
    @ahora_importacion,
    @ahora_importacion
FROM legacy_peritos_import_conductores mc
JOIN peritos_legacy.vehiculos v ON v.id_vehiculo = mc.representative_old_vehiculo_id
WHERE mc.new_conductor_id IS NULL;

UPDATE legacy_peritos_import_conductores mc
JOIN conductores c ON c.client_uuid = LOWER(CONCAT(
    SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 1, 8), '-',
    SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 9, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 13, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 17, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-conductor-', mc.old_hecho_id, '-', mc.conductor_key)), 21, 12)
))
SET mc.new_conductor_id = c.id
WHERE mc.new_conductor_id IS NULL;

INSERT INTO vehiculo_conductor (vehiculo_id, conductor_id, created_at, updated_at)
SELECT DISTINCT
    mv.new_vehiculo_id,
    mc.new_conductor_id,
    @ahora_importacion,
    @ahora_importacion
FROM peritos_legacy.vehiculos ov
JOIN legacy_peritos_import_vehiculos mv ON mv.old_vehiculo_id = ov.id_vehiculo
JOIN legacy_peritos_import_conductores mc
    ON mc.old_hecho_id = mv.old_hecho_id
    AND mc.conductor_key = UPPER(TRIM(COALESCE(ov.conductor, '')))
LEFT JOIN vehiculo_conductor vc
    ON vc.vehiculo_id = mv.new_vehiculo_id
    AND vc.conductor_id = mc.new_conductor_id
WHERE mv.new_vehiculo_id IS NOT NULL
  AND mc.new_conductor_id IS NOT NULL
  AND vc.id IS NULL;

INSERT IGNORE INTO legacy_peritos_import_lesionados (
    old_persona_id,
    old_hecho_id,
    new_hecho_id
)
SELECT
    p.id_persona,
    p.id_accidentes,
    mh.new_hecho_id
FROM peritos_legacy.persona p
JOIN legacy_peritos_import_hechos mh ON mh.old_hecho_id = p.id_accidentes
WHERE mh.new_hecho_id IS NOT NULL
  AND (
      p.status IN ('1', '2')
      OR NULLIF(TRIM(COALESCE(p.nombre, '')), '') IS NOT NULL
  );

INSERT IGNORE INTO lesionados (
    client_uuid,
    hecho_id,
    nombre,
    edad,
    sexo,
    tipo_lesion,
    hospitalizado,
    hospital,
    atencion_en_sitio,
    ambulancia,
    paramedico,
    observaciones,
    created_at,
    updated_at
)
SELECT
    LOWER(CONCAT(
        SUBSTR(MD5(CONCAT('peritos-persona-', p.id_persona)), 1, 8), '-',
        SUBSTR(MD5(CONCAT('peritos-persona-', p.id_persona)), 9, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-persona-', p.id_persona)), 13, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-persona-', p.id_persona)), 17, 4), '-',
        SUBSTR(MD5(CONCAT('peritos-persona-', p.id_persona)), 21, 12)
    )),
    ml.new_hecho_id,
    LEFT(COALESCE(NULLIF(TRIM(p.nombre), ''), 'SIN DATO'), 255),
    CAST(NULLIF(REGEXP_REPLACE(COALESCE(p.edad, ''), '[^0-9]', ''), '') AS UNSIGNED),
    CASE
        WHEN UPPER(TRIM(COALESCE(p.sexo, ''))) = 'M' THEN 'Masculino'
        WHEN UPPER(TRIM(COALESCE(p.sexo, ''))) = 'F' THEN 'Femenino'
        ELSE 'Otro'
    END,
    CASE WHEN p.status = '2' THEN 'Fallecido' ELSE 'Leve' END,
    CASE WHEN NULLIF(TRIM(COALESCE(p.trasladado_a, '')), '') IS NOT NULL AND p.status <> '2' THEN 1 ELSE 0 END,
    LEFT(NULLIF(TRIM(p.trasladado_a), ''), 255),
    0,
    LEFT(NULLIF(TRIM(p.ambulancia), ''), 255),
    LEFT(NULLIF(TRIM(p.auxiliardo_por), ''), 255),
    CONCAT_WS(' | ',
        NULLIF(TRIM(p.peatonopasajero), ''),
        NULLIF(TRIM(p.domicilio), ''),
        NULLIF(TRIM(p.colonia), '')
    ),
    @ahora_importacion,
    @ahora_importacion
FROM peritos_legacy.persona p
JOIN legacy_peritos_import_lesionados ml ON ml.old_persona_id = p.id_persona
WHERE ml.new_lesionado_id IS NULL;

UPDATE legacy_peritos_import_lesionados ml
JOIN lesionados l ON l.client_uuid = LOWER(CONCAT(
    SUBSTR(MD5(CONCAT('peritos-persona-', ml.old_persona_id)), 1, 8), '-',
    SUBSTR(MD5(CONCAT('peritos-persona-', ml.old_persona_id)), 9, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-persona-', ml.old_persona_id)), 13, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-persona-', ml.old_persona_id)), 17, 4), '-',
    SUBSTR(MD5(CONCAT('peritos-persona-', ml.old_persona_id)), 21, 12)
))
SET ml.new_lesionado_id = l.id
WHERE ml.new_lesionado_id IS NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_hecho_counts;

CREATE TEMPORARY TABLE tmp_legacy_hecho_counts AS
SELECT
    mh.new_hecho_id AS hecho_id,
    COALESCE(vexp.total, 0) AS vehiculos_esperados,
    COALESCE(vcap.total, 0) AS vehiculos_capturados,
    COALESCE(cexp.total, 0) AS conductores_esperados,
    COALESCE(ccap.total, 0) AS conductores_capturados,
    COALESCE(lexp.total, 0) AS lesionados_esperados,
    COALESCE(lcap.total, 0) AS lesionados_capturados
FROM legacy_peritos_import_hechos mh
LEFT JOIN (
    SELECT new_hecho_id, COUNT(DISTINCT new_vehiculo_id) AS total
    FROM legacy_peritos_import_vehiculos
    WHERE new_hecho_id IS NOT NULL
      AND new_vehiculo_id IS NOT NULL
    GROUP BY new_hecho_id
) vexp ON vexp.new_hecho_id = mh.new_hecho_id
LEFT JOIN (
    SELECT hv.hecho_id, COUNT(DISTINCT hv.vehiculo_id) AS total
    FROM hecho_vehiculo hv
    JOIN legacy_peritos_import_hechos imh ON imh.new_hecho_id = hv.hecho_id
    GROUP BY hv.hecho_id
) vcap ON vcap.hecho_id = mh.new_hecho_id
LEFT JOIN (
    SELECT imh.new_hecho_id, COUNT(DISTINCT mc.new_conductor_id) AS total
    FROM legacy_peritos_import_conductores mc
    JOIN legacy_peritos_import_hechos imh ON imh.old_hecho_id = mc.old_hecho_id
    WHERE mc.new_conductor_id IS NOT NULL
    GROUP BY imh.new_hecho_id
) cexp ON cexp.new_hecho_id = mh.new_hecho_id
LEFT JOIN (
    SELECT hv.hecho_id, COUNT(DISTINCT vc.conductor_id) AS total
    FROM hecho_vehiculo hv
    JOIN legacy_peritos_import_hechos imh ON imh.new_hecho_id = hv.hecho_id
    JOIN vehiculo_conductor vc ON vc.vehiculo_id = hv.vehiculo_id
    GROUP BY hv.hecho_id
) ccap ON ccap.hecho_id = mh.new_hecho_id
LEFT JOIN (
    SELECT new_hecho_id, COUNT(*) AS total
    FROM legacy_peritos_import_lesionados
    WHERE new_hecho_id IS NOT NULL
      AND new_lesionado_id IS NOT NULL
    GROUP BY new_hecho_id
) lexp ON lexp.new_hecho_id = mh.new_hecho_id
LEFT JOIN (
    SELECT l.hecho_id, COUNT(*) AS total
    FROM lesionados l
    JOIN legacy_peritos_import_hechos imh ON imh.new_hecho_id = l.hecho_id
    GROUP BY l.hecho_id
) lcap ON lcap.hecho_id = mh.new_hecho_id
WHERE mh.new_hecho_id IS NOT NULL;

ALTER TABLE tmp_legacy_hecho_counts ADD PRIMARY KEY (hecho_id);

UPDATE hechos h
JOIN tmp_legacy_hecho_counts hc ON hc.hecho_id = h.id
SET
    h.vehiculos_esperados = hc.vehiculos_esperados,
    h.vehiculos_capturados = hc.vehiculos_capturados,
    h.conductores_esperados = hc.conductores_esperados,
    h.conductores_capturados = hc.conductores_capturados,
    h.lesionados_esperados = hc.lesionados_esperados,
    h.lesionados_capturados = hc.lesionados_capturados,
    h.captura_completa = 1,
    h.captura_completa_at = COALESCE(h.captura_completa_at, @ahora_importacion),
    h.updated_at = @ahora_importacion;

SELECT 'IMPORTACION_COMPLETADA' AS seccion;

SELECT 'hechos_importados' AS metric, COUNT(*) AS total
FROM legacy_peritos_import_hechos
WHERE new_hecho_id IS NOT NULL
UNION ALL
SELECT 'vehiculos_old_total_mapeados', COUNT(*)
FROM legacy_peritos_import_vehiculos
WHERE new_vehiculo_id IS NOT NULL
UNION ALL
SELECT 'vehiculos_unicos_importados', COUNT(DISTINCT new_vehiculo_id)
FROM legacy_peritos_import_vehiculos
WHERE new_vehiculo_id IS NOT NULL
UNION ALL
SELECT 'vehiculos_old_deduplicados', COUNT(*)
FROM legacy_peritos_import_vehiculos
WHERE duplicate_reason IS NOT NULL
UNION ALL
SELECT 'conductores_unicos_importados', COUNT(*)
FROM legacy_peritos_import_conductores
WHERE new_conductor_id IS NOT NULL
UNION ALL
SELECT 'lesionados_importados', COUNT(*)
FROM legacy_peritos_import_lesionados
WHERE new_lesionado_id IS NOT NULL;

SET SESSION sql_mode = @legacy_sql_mode_original;
