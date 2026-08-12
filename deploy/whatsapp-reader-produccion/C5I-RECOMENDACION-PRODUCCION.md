# Recomendación automática de unidad de Siniestros para C5i

> **Retirada:** el sistema ya no procesa ni envía estas recomendaciones automáticas. La recepción y almacenamiento de mensajes del grupo continúa funcionando. Mantén `WHATSAPP_C5I_RECOMMENDATION_ENABLED=false`; el controlador ignora la configuración anterior aunque permanezca activa en un `.env` de producción.

El lector Web únicamente observa `SINIESTROS GC` y guarda mensajes. Laravel analiza los reportes C5i y el envío se realiza por separado mediante la API oficial de Meta.

Dentro de ese grupo sólo se almacenan mensajes enviados por `5214437916890` y `5214437938996`. Los mensajes de cualquier otro participante se descartan tanto en el lector como en Laravel, incluida la cola de reintentos.

El código se despliega desactivado y en simulación. No debe habilitarse el envío hasta que la plantilla esté aprobada, los remitentes estén verificados y una simulación haya elegido correctamente una patrulla de Siniestros.

## 1. Crear la plantilla en WhatsApp Manager

En Meta Business Suite abre **WhatsApp Manager > Plantillas de mensajes > Crear plantilla**.

- Categoría: `UTILITY` / Utilidad.
- Nombre: `recomendacion_unidad_siniestros_c5i_v1`.
- Idioma: Español (México), código `es_MX`.
- Encabezado de texto: `RECOMENDACIÓN DE UNIDAD — C5i`.
- Pie: `Cálculo automático. Validar disponibilidad antes de asignar.`
- Botones: ninguno.

Cuerpo exacto:

```text
Reporte C5i recibido: {{1}}

Ubicación: {{2}}

Unidad de Siniestros más cercana: {{3}}
Distancia aproximada: {{4}} km
Ubicación de la unidad actualizada: {{5}}

Mapa del reporte: {{6}}
Mapa de la unidad: {{7}}

Recomendación automática; validar disponibilidad y asignación por radio antes de despachar.
```

Los campos de **Muestras de variables** solicitan valores ficticios de ejemplo, no nombres ni descripciones. Escribe exactamente estos ejemplos en las cajas de la derecha:

| Variable | Nombre lógico | Ejemplo |
|---|---|---|
| `{{1}}` | `fecha_hora_reporte` | `16/07/2026 08:35` |
| `{{2}}` | `ubicacion_reporte` | `AVENIDA FRANCISCO I. MADERO P #S/N LOCALIDAD: MORELIA COL. TINÍJARO` |
| `{{3}}` | `numero_economico_unidad` | `SP-123` |
| `{{4}}` | `distancia_km` | `1.27` |
| `{{5}}` | `fecha_hora_ubicacion_unidad` | `16/07/2026 08:34` |
| `{{6}}` | `mapa_reporte` | `https://www.google.com/maps?q=19.6969222,-101.2583930` |
| `{{7}}` | `mapa_unidad` | `https://www.google.com/maps?q=19.7010000,-101.2500000` |

Espera a que el estado sea **Aprobada/Approved**. Los dos destinatarios deben haber aceptado recibir estas notificaciones por WhatsApp.

## 2. Desplegar sin envíos

En el servidor Laravel:

```bash
cd /var/www/html/sistemaEstadistico
git pull
php artisan migrate --force
```

Los reportes C5i se identifican por los remitentes `5214437916890` y `5214437938996`. Los destinatarios de la recomendación son números distintos y deben configurarse por separado en `WHATSAPP_C5I_RECOMMENDATION_TO`.

Consulta cómo se guardó realmente el remitente de los reportes con coordenadas:

```bash
php artisan tinker --execute="dump(App\Models\WhatsAppWebMessage::query()->where('body','like','%LATITUD%')->where('body','like','%LONGITUD%')->latest('id')->limit(20)->pluck('author_whatsapp_id')->unique()->values()->all());"
```

El lector sólo almacena mensajes recibidos después de quedar conectado; no importa el historial anterior. Si la consulta devuelve `[]`, espera al siguiente reporte C5i con coordenadas y vuelve a ejecutarla.

Para confirmar si ya se guardó cualquier mensaje nuevo, sin filtrar por coordenadas:

```bash
php artisan tinker --execute="dump(['total' => App\Models\WhatsAppWebMessage::count(), 'ultimos' => App\Models\WhatsAppWebMessage::query()->latest('id')->limit(10)->get(['id','author_whatsapp_id','body','sent_at'])->toArray()]);"
```

Se usan `5214437916890@c.us` y `5214437938996@c.us` como remitentes. Cuando WhatsApp Web entrega un identificador terminado en `@lid`, el lector lo resuelve automáticamente al teléfono antes de autorizarlo y enviarlo a Laravel. No sustituyas estos teléfonos por valores `@lid` tomados al azar del registro.

Agrega al `.env` de Laravel:

```dotenv
WHATSAPP_WEB_READER_ALLOWED_GROUP_IDS=120363424100430316@g.us
WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us
WHATSAPP_GRAPH_VERSION=v25.0
WHATSAPP_C5I_RECOMMENDATION_ENABLED=true
WHATSAPP_C5I_RECOMMENDATION_DRY_RUN=true
WHATSAPP_C5I_RECOMMENDATION_TO=NUMERO_DESTINO_1,NUMERO_DESTINO_2
WHATSAPP_C5I_RECOMMENDATION_GROUP_IDS=120363424100430316@g.us
WHATSAPP_C5I_RECOMMENDATION_SOURCE_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us
WHATSAPP_C5I_RECOMMENDATION_TEMPLATE=recomendacion_unidad_siniestros_c5i_v1
WHATSAPP_C5I_RECOMMENDATION_TEMPLATE_LANGUAGE=es_MX
WHATSAPP_C5I_RECOMMENDATION_UNIT_SLUG=siniestros
WHATSAPP_C5I_RECOMMENDATION_LOCATION_MAX_AGE_MINUTES=10
WHATSAPP_C5I_RECOMMENDATION_MAX_ACCURACY_METERS=200
```

En `/home/nasaka/apps/whatsapp-reader-produccion/.env` agrega también:

```dotenv
WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS=5214437916890@c.us,5214437938996@c.us
```

Conserva también los valores reales ya existentes de:

```dotenv
WHATSAPP_ACCESS_TOKEN=TOKEN_PERMANENTE_DE_META
WHATSAPP_PHONE_NUMBER_ID=PHONE_NUMBER_ID_DE_META
```

Recarga configuración:

```bash
php artisan optimize:clear
php artisan config:cache
```

Con `DRY_RUN=true` se calcula y registra la recomendación, pero no se llama a Meta.

## 3. Verificar la simulación con un reporte ya guardado

Puedes usar el último reporte con coordenadas que ya existe en la base. Este comando fuerza el modo simulación durante esa ejecución y nunca llama a Meta:

```bash
php artisan whatsapp:c5i-recomendacion-simular
```

Para usar un `id` específico de `whatsapp_web_messages`:

```bash
php artisan whatsapp:c5i-recomendacion-simular ID
```

El resultado correcto contiene `"status": "dry_run"`, el número económico de la patrulla candidata y la distancia.

También puedes revisar directamente la fila:

```bash
php artisan tinker --execute="dump(App\Models\WhatsAppWebMessage::query()->latest('id')->first(['id','author_whatsapp_id','incident_lat','incident_lng','recommended_patrulla_id','recommendation_distance_km','recommendation_status','recommendation_meta'])->toArray());"
```

El estado debe ser `dry_run`. En `recommendation_meta.candidate` revisa que la unidad sea realmente de Siniestros y que la ubicación sea reciente. Los filtros aplicados son:

- unidad activa con `slug=siniestros`;
- patrulla activa y perteneciente a la misma unidad;
- usuario con ubicación compartida;
- ubicación con antigüedad máxima de 10 minutos;
- precisión máxima de 200 metros cuando el dispositivo informa precisión;
- una sola recomendación: la patrulla con menor distancia Haversine.

Si el estado es `ignored` y la razón es `source_not_allowed`, confirma que el lector desplegado sea la versión que resuelve automáticamente los identificadores `@lid` y que ambos `.env` conserven los teléfonos C5i indicados. Si es `no_candidate`, no había una patrulla de Siniestros con ubicación suficientemente reciente.

## 4. Activar los dos envíos oficiales

Sólo después de verificar la simulación y la aprobación de Meta, cambia una línea:

```dotenv
WHATSAPP_C5I_RECOMMENDATION_DRY_RUN=false
```

Luego:

```bash
php artisan optimize:clear
php artisan config:cache
```

No es necesario reiniciar PM2 porque el lector Web no cambió. Cada mensaje se deduplica por mensaje y destinatario mediante `whatsapp_send_guards`.

Para desactivar inmediatamente los envíos sin detener el lector:

```dotenv
WHATSAPP_C5I_RECOMMENDATION_ENABLED=false
```

y vuelve a ejecutar `php artisan optimize:clear` y `php artisan config:cache`.
