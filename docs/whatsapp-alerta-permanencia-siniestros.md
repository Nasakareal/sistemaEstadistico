# Alerta de permanencia de Siniestros en Grúas Muñoz

El monitoreo es híbrido. Flutter conserva la visita localmente y encola los eventos aunque no tenga cobertura; el backend también calcula la permanencia con las ubicaciones que sí recibe. Ambos caminos se fusionan por un identificador de visita y por patrulla para evitar mensajes duplicados.

## Plantillas en Meta

### Ruta completa en Meta

1. Entrar a `business.facebook.com` con el usuario administrador del negocio.
2. Abrir **Meta Business Suite** y seleccionar el portafolio empresarial que contiene el número de Seguridad Vial.
3. Abrir **Todas las herramientas** y después **WhatsApp Manager**.
4. Seleccionar la cuenta de WhatsApp Business vinculada al `WHATSAPP_PHONE_NUMBER_ID` del backend.
5. Abrir **Plantillas de mensajes / Message templates**.
6. Presionar **Crear plantilla / Create template**.
7. En categoría seleccionar **Utility / Utilidad**. Si aparece un selector de tipo, elegir **Custom / Personalizada**.
8. En idioma seleccionar **Spanish (Mexico)**. El código que usa el backend es `es_MX`.
9. En formato de variables seleccionar **Number / Posicional** para que las variables sean `{{1}}`, `{{2}}` y `{{3}}`; no seleccionar variables con nombre.
10. Capturar exactamente los campos indicados abajo, agregar los ejemplos y enviar a revisión.

No agregar encabezado, pie ni botones en ninguna de las dos plantillas.

### 1. Permanencia confirmada

- Nombre: `alerta_permanencia_siniestros_v1`
- Categoría: `Utility`
- Idioma: `Spanish (Mexico)`
- Encabezado: `None / Ninguno`
- Cuerpo: `Alerta de permanencia: la unidad {{1}} registró más de {{2}} minutos en {{3}}.`
- Pie: `None / Ninguno`
- Botones: `None / Ninguno`
- Ejemplos de variables:
  - `{{1}}`: `3165`
  - `{{2}}`: `5`
  - `{{3}}`: `Grúas Muñoz`
- Vista previa: `Alerta de permanencia: la unidad 3165 registró más de 5 minutos en Grúas Muñoz.`

### 2. Salida del lugar

- Nombre: `alerta_salida_permanencia_siniestros_v1`
- Categoría: `Utility`
- Idioma: `Spanish (Mexico)`
- Encabezado: `None / Ninguno`
- Cuerpo: `Alerta de salida: la unidad {{1}} pasó {{2}} minutos en {{3}} y ya se retiró.`
- Pie: `None / Ninguno`
- Botones: `None / Ninguno`
- Ejemplos de variables:
  - `{{1}}`: `3165`
  - `{{2}}`: `35`
  - `{{3}}`: `Grúas Muñoz`
- Vista previa: `Alerta de salida: la unidad 3165 pasó 35 minutos en Grúas Muñoz y ya se retiró.`

## Configuración instalada

- Coordenadas: `19.6603522,-101.2373983`
- Radio de entrada: `120 m`
- Permanencia mínima: `5 min`
- Radio de salida: `180 m` (histéresis contra rebotes del GPS)
- Precisión GPS máxima: `100 m`
- Separación máxima entre muestras: `3 min`
- Eventos locales aceptados al recuperar cobertura: hasta `24 h`
- Destinatarios: `WHATSAPP_SUSPICIOUS_PLACE_TO`, separados por coma

Mientras las plantillas están pendientes, usar:

```dotenv
WHATSAPP_SUSPICIOUS_PLACE_ENABLED=true
WHATSAPP_SUSPICIOUS_PLACE_DRY_RUN=true
```

Después de que ambas aparezcan como **Active/Approved**, cambiar solamente:

```dotenv
WHATSAPP_SUSPICIOUS_PLACE_DRY_RUN=false
```

Luego ejecutar `php artisan config:clear`. Antes de pasar a envío real conviene confirmar que no haya una visita piloto todavía activa en `suspicious_place_visits` con `entry_notification_status=dry_run`.

Si Meta muestra la plantilla como **Rejected**, no cambiar el nombre ni el orden de variables en el código. Abrir el motivo del rechazo en WhatsApp Manager y corregir sólo el texto o solicitar revisión. Si Meta aprueba otro código de idioma distinto de `es_MX`, actualizar `WHATSAPP_SUSPICIOUS_PLACE_TEMPLATE_LANGUAGE` para que coincida exactamente.
