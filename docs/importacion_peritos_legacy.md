# Importación Segura De `peritos.sql`

Este procedimiento migra el respaldo antiguo de Peritos sin meter usuarios viejos. El dump se importa primero en una base temporal `peritos_legacy`; después se insertan datos limpios en `sistemaestadistico`.

## Qué Se Migra

- Hechos desde `peritos_legacy.accidentest`.
- Vehículos desde `peritos_legacy.vehiculos`.
- Conductores desde la columna `vehiculos.conductor`, deduplicados por hecho.
- Lesionados/fallecidos desde `peritos_legacy.persona`.

No se migran `peritos_legacy.usuarios`.

## Reglas De Limpieza

- Folios C5i repetidos: el primer hecho conserva el folio original si no existe ya en `sistemaestadistico`; los repetidos/conflictivos reciben folio `LEG{id_accidentes}`. Los placeholders como `0`, `S/N`, `S/`, `SIN FOLIO`, `C5` o `C5I` se guardan como `NULL`. El folio original queda guardado en `legacy_peritos_import_hechos`.
- Vehículos repetidos en el mismo hecho: se deduplican por serie válida repetida y por placa válida repetida. Los placeholders como `S/P`, `SIN PLACAS`, `NO VISIBLE`, `SE DESCONOCE` o `SIN SERIE` no se usan como llaves de deduplicación. Los IDs viejos quedan mapeados al vehículo ganador.
- Conductores repetidos en el mismo hecho: se crea un conductor por nombre normalizado y se vincula a los vehículos correspondientes.
- Usuarios antiguos: no se importan. Todo queda asignado al usuario actual indicado en `@usuario_importacion_id`.

## Paso A Paso En Producción

1. Poner la aplicación en mantenimiento.

```bash
php artisan down
```

2. Hacer respaldo completo de producción.

```bash
mysqldump -u USUARIO -p --single-transaction --routines --triggers sistemaestadistico > sistemaestadistico_antes_peritos.sql
```

3. Crear la base temporal limpia.

```sql
DROP DATABASE IF EXISTS peritos_legacy;
CREATE DATABASE peritos_legacy CHARACTER SET latin1 COLLATE latin1_spanish_ci;
```

4. Importar el dump antiguo a `peritos_legacy`.

En Linux/macOS:

```bash
sed -e 's/`peritos`/`peritos_legacy`/g' -e 's/,NO_AUTO_CREATE_USER//g' peritos.sql | mysql -u USUARIO -p
```

En Windows PowerShell:

```powershell
Get-Content "C:\ruta\peritos.sql" |
  ForEach-Object {
    $_ -replace 'CREATE DATABASE IF NOT EXISTS `peritos` /\*!40100 DEFAULT CHARACTER SET latin1 \*/;', 'CREATE DATABASE IF NOT EXISTS `peritos_legacy` /*!40100 DEFAULT CHARACTER SET latin1 */;' `
       -replace 'USE `peritos`;', 'USE `peritos_legacy`;' `
       -replace ',NO_AUTO_CREATE_USER', ''
  } |
  & "C:\wamp64\bin\mysql\mysql8.3.0\bin\mysql.exe" -u USUARIO -p
```

5. Ejecutar diagnóstico y guardar salida.

```bash
mysql -u USUARIO -p sistemaestadistico < database/sql/peritos_legacy_01_diagnostico.sql > diagnostico_peritos_legacy.txt
```

6. Editar `database/sql/peritos_legacy_02_importar_limpio.sql` y poner el ID del usuario actual que aparecerá como capturista de importación:

```sql
SET @usuario_importacion_id := 1;
```

Debe ser un usuario existente de `sistemaestadistico.users`.

Para ubicarlo:

```sql
SELECT id, name, email FROM users ORDER BY id;
```

7. Importar limpio.

```bash
mysql -u USUARIO -p sistemaestadistico < database/sql/peritos_legacy_02_importar_limpio.sql > importacion_peritos_legacy.txt
```

8. Validar resultado.

```bash
mysql -u USUARIO -p sistemaestadistico < database/sql/peritos_legacy_03_validacion_post_import.sql > validacion_peritos_legacy.txt
```

El bloque `DUPLICADOS_REMANENTES_EN_IMPORTACION` debe salir en cero para folios, placas, series y conductores repetidos dentro del mismo hecho importado.

9. Revisar la aplicación: listado de hechos, detalle de algunos hechos importados, vehículos, conductores, lesionados y estadísticas globales.

10. Sacar la aplicación de mantenimiento.

```bash
php artisan up
```

## Si Algo Sale Mal

Restaurar el respaldo tomado en el paso 2:

```bash
mysql -u USUARIO -p sistemaestadistico < sistemaestadistico_antes_peritos.sql
```

Si ya no se necesita auditar el origen, se puede eliminar la base temporal:

```sql
DROP DATABASE peritos_legacy;
```

No borres las tablas `legacy_peritos_import_*` hasta confirmar que todo quedó bien; son el mapa entre IDs viejos y nuevos.

## Validación Hecha En Desarrollo

Se probó en una copia local llamada `sistemaestadistico_peritos_prueba`, clonada desde `sistemaestadistico`, usando `peritos_legacy` como base temporal.

- Hechos importados: 58,222.
- Vehículos viejos mapeados: 101,512.
- Vehículos únicos importados: 101,337.
- Vehículos deduplicados: 175 total, 100 por serie y 75 por placas.
- Conductores únicos importados: 90,389.
- Lesionados/fallecidos importados: 7,237.
- Duplicados remanentes en importación: 0 folios, 0 placas, 0 series y 0 conductores dentro del mismo hecho.
- Usuarios: `sistemaestadistico.users` se mantuvo en 170 usuarios; `peritos_legacy.usuarios` tenía 155 y no se migró ninguno.
