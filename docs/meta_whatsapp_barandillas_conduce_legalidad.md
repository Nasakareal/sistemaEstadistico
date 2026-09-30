# Plantillas de Meta: aviso anticipado a Barandillas

El sistema envía estos documentos al número configurado en
`WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_TO` solamente cuando la captura incluye
un vehículo remitido (retención, grúa o corralón). Primero manda la boleta PDF y
después el IPH de Barandillas en DOCX.

## 1. Boleta PDF

- Nombre: `aviso_barandillas_conduce_v1`
- Categoría: `Utilidad`
- Idioma: `Español (México)` / `es_MX`
- Encabezado: `Documento`
- Botones: ninguno

```text
Aviso anticipado de vehículo en traslado a Barandillas.

Folio: {{1}}
Fecha y hora: {{2}}
Vehículo: {{3}}
Destino/corralón: {{4}}

Se adjunta la boleta de notificación para conocimiento y preparación de la recepción.
```

## 2. IPH de Barandillas

- Nombre: `iph_barandillas_conduce_v1`
- Categoría: `Utilidad`
- Idioma: `Español (México)` / `es_MX`
- Encabezado: `Documento`
- Botones: ninguno

```text
Documentación anticipada para recepción en Barandillas.

Folio: {{1}}
Fecha y hora: {{2}}
Vehículo: {{3}}
Elemento actuante: {{4}}

Se adjunta el IPH generado con la información registrada en el operativo Conduce con Legalidad.
```

## Activación

Después de que Meta apruebe ambas plantillas, configurar:

```dotenv
WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_ENABLED=true
WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_TO=5214433163728
WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_BOLETA_TEMPLATE=aviso_barandillas_conduce_v1
WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_IPH_TEMPLATE=iph_barandillas_conduce_v1
WHATSAPP_CONDUCE_LEGALIDAD_BARANDILLAS_TEMPLATE_LANGUAGE=es_MX
```

Finalmente ejecutar `php artisan config:clear`.
