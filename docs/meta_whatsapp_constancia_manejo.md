# Plantilla Meta: constancia de manejo activada

El envio automatico usa una plantilla de WhatsApp con encabezado de tipo documento (PDF) y cuatro variables en el cuerpo, en este orden:

1. Folio de la constancia.
2. Nombre del solicitante.
3. Fecha de activacion.
4. Fecha de vencimiento.

Texto sugerido para registrar en Meta:

> Tu constancia {{1}} a nombre de {{2}} fue activada el {{3}} y tiene vigencia hasta el {{4}}. Conserva el PDF adjunto para presentarlo cuando sea necesario.

Configuracion:

```dotenv
WHATSAPP_CONSTANCIAS_MANEJO_ENABLED=true
WHATSAPP_CONSTANCIAS_MANEJO_COUNTRY_PREFIX=521
WHATSAPP_CONSTANCIAS_MANEJO_TEMPLATE=constancia_manejo_activada_v1
WHATSAPP_CONSTANCIAS_MANEJO_TEMPLATE_LANGUAGE=es_MX
```

La activacion no se revierte si Meta rechaza o no puede procesar el envio. El incidente queda registrado en el log del backend.
