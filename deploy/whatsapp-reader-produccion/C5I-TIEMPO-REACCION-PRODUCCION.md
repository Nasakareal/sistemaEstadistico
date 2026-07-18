# Tiempo de reacción C5i / Siniestros

## Plantilla oficial de Meta

- Nombre: `alerta_tiempo_reaccion_siniestros_v1`
- Categoría: `Utility`
- Idioma: `Spanish (Mexico)` (`es_MX`)
- Encabezado: ninguno
- Botones: ninguno
- Pie opcional: `Registro automático del Sistema Estadístico.`

Copiar exactamente este cuerpo:

```text
*TIEMPO DE REACCIÓN — SINIESTROS*

Servicio C5i: {{1}}
Unidad: {{2}}
Lugar: {{3}}

Reporte C5i: {{4}}
Asignación: {{5}}
Arribo detectado por GPS: {{6}}
Mensaje de arribo: {{7}}

C5i → GPS: {{8}}
Asignación → GPS: {{9}}
Comparación: {{10}}
```

Ejemplos para la revisión de Meta, en el mismo orden:

1. `C5i TEST-2040`
2. `3252`
3. `Avenida Madero, colonia Tiníjaro, Morelia`
4. `17/07/2026 16:00:00`
5. `17/07/2026 16:02:00`
6. `17/07/2026 16:10:00`
7. `17/07/2026 16:30:00`
8. `10 minutos`
9. `8 minutos`
10. `El mensaje se envió 20 minutos después del arribo GPS; GPS a 35 m del punto; precisión 12 m`

## Laravel `.env` de producción

Mantener `DRY_RUN=true` hasta que Meta muestre la plantilla como `APPROVED`.

```dotenv
WHATSAPP_WEB_READER_ALLOWED_GROUP_IDS=120363424100430316@g.us
WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us,5214433284672@c.us
WHATSAPP_WEB_READER_ALLOW_OPERATIONAL_AUTHORS=true

WHATSAPP_C5I_RESPONSE_TIME_ENABLED=true
WHATSAPP_C5I_RESPONSE_TIME_DRY_RUN=true
WHATSAPP_C5I_RESPONSE_TIME_TO=5214434765057
WHATSAPP_C5I_RESPONSE_TIME_GROUP_IDS=120363424100430316@g.us
WHATSAPP_C5I_RESPONSE_TIME_SOURCE_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us
WHATSAPP_C5I_RESPONSE_TIME_DISPATCH_AUTHOR_IDS=5214433284672@c.us
WHATSAPP_C5I_RESPONSE_TIME_TEMPLATE=alerta_tiempo_reaccion_siniestros_v1
WHATSAPP_C5I_RESPONSE_TIME_TEMPLATE_LANGUAGE=es_MX
WHATSAPP_C5I_RESPONSE_TIME_UNIT_SLUG=siniestros
WHATSAPP_C5I_RESPONSE_TIME_ARRIVAL_RADIUS_METERS=200
WHATSAPP_C5I_RESPONSE_TIME_MAX_ACCURACY_METERS=100
WHATSAPP_C5I_RESPONSE_TIME_OPEN_SERVICE_MINUTES=240
```

Cuando la plantilla esté aprobada, cambiar solamente:

```dotenv
WHATSAPP_C5I_RESPONSE_TIME_DRY_RUN=false
```

Para agregar los otros despachadores, anexar sus identificadores separados por comas:

```dotenv
WHATSAPP_C5I_RESPONSE_TIME_DISPATCH_AUTHOR_IDS=5214433284672@c.us,OTRO_NUMERO@c.us
WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us,5214433284672@c.us,OTRO_NUMERO@c.us
```

Después de modificar Laravel:

```bash
php artisan migrate --force
php artisan config:clear
php artisan config:cache
```

## Lector Node `.env` de producción

Conservar la URL y el secreto reales ya configurados, y dejar estas líneas:

```dotenv
WHATSAPP_WEB_READER_GROUP_IDS=120363424100430316@g.us
WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us,5214433284672@c.us
WHATSAPP_WEB_READER_ALLOW_OPERATIONAL_AUTHORS=true
WHATSAPP_WEB_READER_HEADLESS=true
```

Reiniciar el lector:

```bash
pm2 restart sistema-estadistico-whatsapp-reader
pm2 logs sistema-estadistico-whatsapp-reader
```

El modo de autores operativos sólo reenvía mensajes con estructura probable de reporte C5i, asignación o arribo. Los mensajes ordinarios del grupo siguen descartándose.
