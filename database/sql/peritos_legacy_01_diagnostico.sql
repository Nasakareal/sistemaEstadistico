/*
  Diagnostico del respaldo antiguo de Peritos.

  Supuestos:
  - El dump viejo ya fue importado en una base separada llamada peritos_legacy.
  - Este script NO modifica datos.
  - No se migran usuarios antiguos; la tabla peritos_legacy.usuarios solo queda como referencia del dump.
*/

SELECT 'RESUMEN_TABLAS_LEGACY' AS seccion;

SELECT
    table_name,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'peritos_legacy'
ORDER BY table_name;

SELECT 'CONTEOS_BASE' AS seccion;

SELECT 'accidentest_total' AS metric, COUNT(*) AS total
FROM peritos_legacy.accidentest
UNION ALL
SELECT 'accidentest_no_borrados', COUNT(*)
FROM peritos_legacy.accidentest
WHERE COALESCE(borrado, 0) = 0
UNION ALL
SELECT 'vehiculos_total', COUNT(*)
FROM peritos_legacy.vehiculos
UNION ALL
SELECT 'personas_total', COUNT(*)
FROM peritos_legacy.persona
UNION ALL
SELECT 'usuarios_total_solo_referencia_no_migrar', COUNT(*)
FROM peritos_legacy.usuarios;

SELECT 'FOLIOS_C5I_DUPLICADOS' AS seccion;

WITH accidentes AS (
    SELECT
        id_accidentes,
        UPPER(TRIM(COALESCE(folioc5, ''))) AS folio_norm,
        UPPER(REGEXP_REPLACE(COALESCE(folioc5, ''), '[^A-Za-z0-9]', '')) AS folio_placeholder_key
    FROM peritos_legacy.accidentest
    WHERE COALESCE(borrado, 0) = 0
),
folios_validos AS (
    SELECT *
    FROM accidentes
    WHERE folio_norm NOT IN ('', '0', '00', '000', 'S', 'S/D', 'SD', 'S/N', 'SN', 'S/F', 'SF', 'SIN', 'SIN DATOS', 'SIN DATO', 'SIN FOLIO', 'SIN NUMERO', 'SIN NÚMERO', 'N/A', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', '-', '--', 'C5', 'C5I')
      AND folio_placeholder_key NOT IN ('', '0', '00', '000', 'S', 'SD', 'SN', 'SF', 'SIN', 'SINDATOS', 'SINDATO', 'SINFOLIO', 'SINNUMERO', 'SINNMERO', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', 'C5', 'C5I')
)
SELECT 'folios_validos' AS metric, COUNT(*) AS total
FROM folios_validos
UNION ALL
SELECT 'folios_repetidos_grupos', COUNT(*)
FROM (
    SELECT folio_norm
    FROM folios_validos
    GROUP BY folio_norm
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'hechos_en_folios_repetidos', COALESCE(SUM(c), 0)
FROM (
    SELECT COUNT(*) AS c
    FROM folios_validos
    GROUP BY folio_norm
    HAVING COUNT(*) > 1
) x;

SELECT
    folio_norm,
    COUNT(*) AS hechos,
    MIN(id_accidentes) AS primer_id,
    GROUP_CONCAT(id_accidentes ORDER BY id_accidentes SEPARATOR ',') AS ids_accidentes
FROM (
    SELECT
        id_accidentes,
        UPPER(TRIM(COALESCE(folioc5, ''))) AS folio_norm,
        UPPER(REGEXP_REPLACE(COALESCE(folioc5, ''), '[^A-Za-z0-9]', '')) AS folio_placeholder_key
    FROM peritos_legacy.accidentest
    WHERE COALESCE(borrado, 0) = 0
) a
WHERE folio_norm NOT IN ('', '0', '00', '000', 'S', 'S/D', 'SD', 'S/N', 'SN', 'S/F', 'SF', 'SIN', 'SIN DATOS', 'SIN DATO', 'SIN FOLIO', 'SIN NUMERO', 'SIN NÚMERO', 'N/A', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', '-', '--', 'C5', 'C5I')
  AND folio_placeholder_key NOT IN ('', '0', '00', '000', 'S', 'SD', 'SN', 'SF', 'SIN', 'SINDATOS', 'SINDATO', 'SINFOLIO', 'SINNUMERO', 'SINNMERO', 'NA', 'NO', 'NINGUNO', 'NINGUNA', 'SELECCIONE', 'NULL', 'C5', 'C5I')
GROUP BY folio_norm
HAVING COUNT(*) > 1
ORDER BY hechos DESC, folio_norm
LIMIT 100;

SELECT 'DUPLICADOS_DENTRO_DEL_MISMO_HECHO' AS seccion;

WITH veh AS (
    SELECT
        id_vehiculo,
        id_accidentes,
        UPPER(REGEXP_REPLACE(COALESCE(placas, ''), '[^A-Za-z0-9]', '')) AS placas_norm,
        UPPER(REGEXP_REPLACE(COALESCE(noserie, ''), '[^A-Za-z0-9]', '')) AS serie_norm,
        UPPER(TRIM(COALESCE(conductor, ''))) AS conductor_norm
    FROM peritos_legacy.vehiculos
),
veh_valid AS (
    SELECT
        *,
        CASE
            WHEN placas_norm IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SPLACA', 'SPLACAS', 'SINPLACA', 'SINPLACAS', 'SINPLAC', 'SINPLCAS', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'SINDOCUMENTO', 'NOPRESENTA', 'NOPRESENTO', 'PERMISO', 'ENPERMISO') THEN NULL
            WHEN CHAR_LENGTH(placas_norm) < 3 THEN NULL
            ELSE LEFT(placas_norm, 15)
        END AS placas_key,
        CASE
            WHEN serie_norm IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SINNODATOS', 'SINSERIE', 'SINNUMERO', 'SINNUMEROSERIE', 'SINNUMERODESERIE', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'NOSEALCANZAAPRECIAR', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'NOPRESENTA', 'NOPRESENTO', 'ESTACIONADO', 'SEIGNORA', 'NOLOCALIZADO', 'NOLOCALIZADA')
                OR CHAR_LENGTH(serie_norm) < 6 THEN NULL
            ELSE LEFT(serie_norm, 17)
        END AS serie_key,
        CASE
            WHEN conductor_norm IN ('', '0', 'SD', 'S/D', 'SN', 'S/N', 'SIN DATOS', 'SIN DATO', 'SIN NOMBRE', 'SE DESCONOCE', 'SE IGNORA', 'DESCONOCIDO', 'DESCONOCIDA', 'NA', 'N/A', 'NO APLICA', 'NULL') THEN NULL
            ELSE conductor_norm
        END AS conductor_key
    FROM veh
)
SELECT 'grupos_placas_repetidas_mismo_hecho' AS metric, COUNT(*) AS total
FROM (
    SELECT id_accidentes, placas_key
    FROM veh_valid
    WHERE placas_key IS NOT NULL
    GROUP BY id_accidentes, placas_key
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'vehiculos_en_placas_repetidas', COALESCE(SUM(c), 0)
FROM (
    SELECT COUNT(*) AS c
    FROM veh_valid
    WHERE placas_key IS NOT NULL
    GROUP BY id_accidentes, placas_key
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'grupos_series_repetidas_mismo_hecho', COUNT(*)
FROM (
    SELECT id_accidentes, serie_key
    FROM veh_valid
    WHERE serie_key IS NOT NULL
    GROUP BY id_accidentes, serie_key
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'vehiculos_en_series_repetidas', COALESCE(SUM(c), 0)
FROM (
    SELECT COUNT(*) AS c
    FROM veh_valid
    WHERE serie_key IS NOT NULL
    GROUP BY id_accidentes, serie_key
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'grupos_conductores_repetidos_mismo_hecho', COUNT(*)
FROM (
    SELECT id_accidentes, conductor_key
    FROM veh_valid
    WHERE conductor_key IS NOT NULL
    GROUP BY id_accidentes, conductor_key
    HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'vehiculos_en_conductores_repetidos', COALESCE(SUM(c), 0)
FROM (
    SELECT COUNT(*) AS c
    FROM veh_valid
    WHERE conductor_key IS NOT NULL
    GROUP BY id_accidentes, conductor_key
    HAVING COUNT(*) > 1
) x;

SELECT 'TOP_DUPLICADOS_SERIE' AS seccion;

SELECT
    id_accidentes,
    serie_key,
    COUNT(*) AS vehiculos,
    GROUP_CONCAT(id_vehiculo ORDER BY id_vehiculo SEPARATOR ',') AS ids_vehiculo
FROM (
    SELECT
        id_vehiculo,
        id_accidentes,
        CASE
            WHEN UPPER(REGEXP_REPLACE(COALESCE(noserie, ''), '[^A-Za-z0-9]', '')) IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SINNODATOS', 'SINSERIE', 'SINNUMERO', 'SINNUMEROSERIE', 'SINNUMERODESERIE', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'NOSEALCANZAAPRECIAR', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'NOPRESENTA', 'NOPRESENTO', 'ESTACIONADO', 'SEIGNORA', 'NOLOCALIZADO', 'NOLOCALIZADA')
                OR CHAR_LENGTH(UPPER(REGEXP_REPLACE(COALESCE(noserie, ''), '[^A-Za-z0-9]', ''))) < 6 THEN NULL
            ELSE LEFT(UPPER(REGEXP_REPLACE(COALESCE(noserie, ''), '[^A-Za-z0-9]', '')), 17)
        END AS serie_key
    FROM peritos_legacy.vehiculos
) v
WHERE serie_key IS NOT NULL
GROUP BY id_accidentes, serie_key
HAVING COUNT(*) > 1
ORDER BY vehiculos DESC, id_accidentes
LIMIT 100;

SELECT 'TOP_DUPLICADOS_PLACAS' AS seccion;

SELECT
    id_accidentes,
    placas_key,
    COUNT(*) AS vehiculos,
    GROUP_CONCAT(id_vehiculo ORDER BY id_vehiculo SEPARATOR ',') AS ids_vehiculo
FROM (
    SELECT
        id_vehiculo,
        id_accidentes,
        CASE
            WHEN UPPER(REGEXP_REPLACE(COALESCE(placas, ''), '[^A-Za-z0-9]', '')) IN ('', '0', '00', '000', 'SD', 'SN', 'SP', 'SF', 'SINDATO', 'SINDATOS', 'SIN', 'SPLACA', 'SPLACAS', 'SINPLACA', 'SINPLACAS', 'SINPLAC', 'SINPLCAS', 'NA', 'NAA', 'NO', 'NOAPLICA', 'NOAPLICABLE', 'SELECCIONE', 'NULL', 'NOVISIBLE', 'NOVICIBLE', 'NOSEVE', 'NOSEAPRECIA', 'SEDESCONOCE', 'DESCONOCIDO', 'DESCONOCIDA', 'SINDOCUMENTO', 'NOPRESENTA', 'NOPRESENTO', 'PERMISO', 'ENPERMISO') THEN NULL
            WHEN CHAR_LENGTH(UPPER(REGEXP_REPLACE(COALESCE(placas, ''), '[^A-Za-z0-9]', ''))) < 3 THEN NULL
            ELSE LEFT(UPPER(REGEXP_REPLACE(COALESCE(placas, ''), '[^A-Za-z0-9]', '')), 15)
        END AS placas_key
    FROM peritos_legacy.vehiculos
) v
WHERE placas_key IS NOT NULL
GROUP BY id_accidentes, placas_key
HAVING COUNT(*) > 1
ORDER BY vehiculos DESC, id_accidentes
LIMIT 100;
