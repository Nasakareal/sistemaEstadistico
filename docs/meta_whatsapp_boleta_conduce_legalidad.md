# Plantilla de Meta: boleta de Conduce con Legalidad

## Datos de la plantilla

- Nombre: `boleta_conduce_legalidad_v1`
- Categoría: `Utilidad`
- Idioma: `Español (México)` / código `es_MX`
- Encabezado: `Documento`
- Botones: ninguno

Meta solicitará un documento de ejemplo para el encabezado. Se puede usar una boleta PDF de prueba sin datos reales.

## Cuerpo exacto

```text
Te compartimos tu boleta de notificación del Operativo Conduce con Legalidad.

Folio: {{1}}
Persona: {{2}}
Fecha y hora: {{3}}
Vehículo: {{4}}

El documento PDF adjunto contiene la información completa registrada en la boleta. Consérvalo para cualquier aclaración o trámite posterior.
```

## Variables y ejemplos para Meta

1. `{{1}}` — Folio de la boleta. Ejemplo: `CL-25-81`
2. `{{2}}` — Nombre de la persona infractora. Ejemplo: `Mario Bautista R.`
3. `{{3}}` — Fecha y hora de la captura. Ejemplo: `2026-09-23 18:05`
4. `{{4}}` — Resumen del vehículo. Ejemplo: `Marca KTM, tipo motocicleta, línea Adventure 250, color negro, placas 99PKF8.`

Las variables deben conservar exactamente ese orden. El encabezado de documento no lleva variable de texto: el sistema adjunta dinámicamente el PDF generado para cada boleta.

## Configuración del servidor

```dotenv
WHATSAPP_CONDUCE_LEGALIDAD_BOLETA_TEMPLATE=boleta_conduce_legalidad_v1
WHATSAPP_CONDUCE_LEGALIDAD_BOLETA_TEMPLATE_LANGUAGE=es_MX
WHATSAPP_CONDUCE_LEGALIDAD_BOLETA_COUNTRY_PREFIX=521
```

Después de aprobar la plantilla y actualizar el `.env`, ejecutar:

```bash
php artisan config:clear
```

El prefijo `521` está separado en configuración para poder cambiarlo sin modificar código si el número emisor de Meta requiere otro formato para destinatarios mexicanos.
