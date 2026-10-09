/*
  Corrige registros de CONDUCE CON LEGALIDAD asignados por error a:
    1 = Siniestros
  para dejarlos en:
    5 = Unidad de Proteccion en Vialidades Urbanas

  Alcance deliberadamente acotado:
  - Solo capturas cuya unidad actual es 1 dentro de operativos de unidad 5.
  - Solo operativos con tipo_operativo = 'conduce_legalidad'.
  - No modifica la unidad del operativo.
  - Tambien corrige sus actividades espejo y referencias de grua.

  IMPORTANTE: revisar los dos SELECT iniciales antes de ejecutar los UPDATE.
*/

SET @unidad_origen  := 1;
SET @unidad_destino := 5;

-- Confirma que los IDs corresponden a las unidades esperadas.
SELECT id, nombre, slug
FROM unidades
WHERE id IN (@unidad_origen, @unidad_destino)
ORDER BY id;

-- Vista previa: estos son exactamente los registros que se corregiran.
SELECT
    c.id AS captura_id,
    c.fecha,
    c.created_by,
    c.unidad_id AS captura_unidad,
    o.id AS operativo_id,
    o.tipo_operativo,
    o.unidad_id AS operativo_unidad,
    c.actividad_id,
    a.unidad_org_id AS actividad_unidad
FROM conduce_legalidad_capturas AS c
INNER JOIN conduce_legalidad_operativos AS o
    ON o.id = c.operativo_id
LEFT JOIN actividades AS a
    ON a.id = c.actividad_id
WHERE c.unidad_id = @unidad_origen
  AND o.unidad_id = @unidad_destino
  AND o.tipo_operativo = 'conduce_legalidad'
ORDER BY c.id;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_cl_capturas_corregir;
CREATE TEMPORARY TABLE tmp_cl_capturas_corregir AS
SELECT
    c.id AS captura_id,
    c.operativo_id,
    c.actividad_id
FROM conduce_legalidad_capturas AS c
INNER JOIN conduce_legalidad_operativos AS o
    ON o.id = c.operativo_id
WHERE c.unidad_id = @unidad_origen
  AND o.unidad_id = @unidad_destino
  AND o.tipo_operativo = 'conduce_legalidad';

ALTER TABLE tmp_cl_capturas_corregir
    ADD PRIMARY KEY (captura_id),
    ADD INDEX idx_tmp_cl_operativo (operativo_id),
    ADD INDEX idx_tmp_cl_actividad (actividad_id);

-- 1) Capturas fuente. El operativo ya pertenece a la unidad correcta.
UPDATE conduce_legalidad_capturas AS c
INNER JOIN tmp_cl_capturas_corregir AS x
    ON x.captura_id = c.id
SET
    c.unidad_id = @unidad_destino,
    c.updated_at = NOW()
WHERE c.unidad_id = @unidad_origen;

-- 2) Actividades estadisticas espejo.
UPDATE actividades AS a
INNER JOIN tmp_cl_capturas_corregir AS x
    ON x.actividad_id = a.id
SET
    a.unidad_org_id = @unidad_destino,
    a.updated_at = NOW()
WHERE a.unidad_org_id = @unidad_origen;

-- 3) Contexto de unidad guardado en vehiculos con servicio de grua.
UPDATE conduce_legalidad_vehiculos AS v
INNER JOIN tmp_cl_capturas_corregir AS x
    ON x.captura_id = v.captura_id
SET
    v.servicio_unidad_id = @unidad_destino,
    v.updated_at = NOW()
WHERE v.servicio_unidad_id = @unidad_origen;

-- 4) Servicio de grua generado desde la actividad espejo.
UPDATE servicios AS s
INNER JOIN actividad_vehiculo AS av
    ON av.vehiculo_id = s.vehiculo_id
INNER JOIN tmp_cl_capturas_corregir AS x
    ON x.actividad_id = av.actividad_id
SET
    s.unidad_id = @unidad_destino,
    s.updated_at = NOW()
WHERE s.unidad_id = @unidad_origen;

-- Verificacion final: debe devolver cero filas.
SELECT
    c.id AS captura_id,
    c.unidad_id AS captura_unidad,
    a.unidad_org_id AS actividad_unidad
FROM tmp_cl_capturas_corregir AS x
INNER JOIN conduce_legalidad_capturas AS c
    ON c.id = x.captura_id
LEFT JOIN actividades AS a
    ON a.id = x.actividad_id
WHERE c.unidad_id <> @unidad_destino
   OR (x.actividad_id IS NOT NULL AND a.unidad_org_id <> @unidad_destino);

-- Ejecutar esta linea despues de confirmar que la verificacion devuelve cero filas.
COMMIT;
-- Si se ejecutan los bloques manualmente y la verificacion falla, usar ROLLBACK en lugar de COMMIT.
-- ROLLBACK;
