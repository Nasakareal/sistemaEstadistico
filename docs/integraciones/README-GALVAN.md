# API privada SIERSE para Grúas Galván

No se entrega una carpeta para instalarla en sus servidores. Esta carpeta es el paquete de integración para su desarrollador: contiene el contrato OpenAPI, un cliente de prueba, la CA y las credenciales emitidas. La API ya vive en Seguridad Vial.

## Conexión

- URL base: `https://seguridadvial-mich.com/api/integraciones/sierse/v1`
- Autenticación obligatoria: certificado cliente mTLS y firma HMAC-SHA256.
- Primera llamada: `GET /estado`.
- El archivo `test-sierse-api.ps1` muestra cómo construir la firma y realizar llamadas.
- El PFX y su contraseña deben enviarse por canales distintos.

## Qué puede consultar Galván

Únicamente vehículos asociados a su grúa o corralón. Puede buscar por inventario, placas, NIV/serie y folio de Conduce con Legalidad como `CL-86-213`.

El expediente incluye datos del vehículo, custodia, servicio, costos y liberación. El arreglo `photos` enumera las fotografías autorizadas del vehículo, inventario, servicio de grúa, Hecho, Actividad y Conduce con Legalidad que existan para ese registro. Cada elemento contiene `download_path`; esa ruta se descarga usando nuevamente el certificado mTLS y la firma HMAC. No existen URLs públicas permanentes.

Nunca se incluyen conductores, teléfonos, domicilios ni fotografías de identificaciones personales. Tampoco se entregan rutas internas de almacenamiento.

El archivo `EJEMPLO-RESPUESTA-GALVAN.json` muestra una respuesta completa con datos ficticios.

## Qué puede enviar Galván

Los eventos se publican en:

`POST /vehiculos/vehicle_registry/{vehicleId}/eventos`

Tipos aceptados:

- `service_update`: fechas, operador, unidad, kilómetros, maniobras y folio SIERSE.
- `cost_update`: total, forma/estado de pago, referencia y desglose.
- `release_update`: liberado, fecha, folio, condición y documentos.
- `evidence_update`: memoria descriptiva, comprobante, oficio y foto de entrega.

Los documentos se mandan dentro del JSON en Base64. El servidor comprueba el tipo real, tamaño y SHA-256, los guarda de forma privada y elimina el Base64 del registro de auditoría.

Ejemplo de costos:

```json
{
  "external_event_id": "GALVAN-COSTO-000123-V1",
  "event_type": "cost_update",
  "schema_version": "1.0",
  "data": {
    "total_amount": 2450.00,
    "currency": "MXN",
    "payment_method": "TRANSFERENCIA",
    "payment_status": "PAID",
    "breakdown": [
      {"concept": "ARRASTRE", "amount": 1800.00},
      {"concept": "ALMACENAJE", "amount": 650.00}
    ]
  }
}
```

Ejemplo de liberación y evidencias:

```json
{
  "external_event_id": "GALVAN-LIBERACION-000123-V1",
  "event_type": "release_update",
  "schema_version": "1.0",
  "data": {
    "released": true,
    "released_at": "2026-11-15T21:30:00-06:00",
    "sierse_folio": "SIERSE-2026-00123",
    "delivery_condition": "CONFORME_INVENTARIO",
    "documents": {
      "memory_description": {
        "filename": "memoria.pdf",
        "mime": "application/pdf",
        "content_base64": "JVBERi0xLjQ..."
      },
      "payment_receipt": {
        "filename": "pago.pdf",
        "mime": "application/pdf",
        "content_base64": "JVBERi0xLjQ..."
      }
    }
  }
}
```

Cada `external_event_id` debe ser único. Repetir exactamente el mismo evento es seguro y devuelve el resultado anterior; reutilizar el ID con otro contenido devuelve `409`.

## Estados

- `APPLIED`: datos operativos o costos aplicados.
- `REVIEW_REQUIRED`: liberación/evidencias recibidas y visibles, pendientes de validación interna.
- `RECEIVED`: evento adicional conservado sin modificar el expediente.

Una liberación enviada por Galván nunca cambia por sí sola el estado oficial a `ENTREGADO`. Seguridad Vial ve el aviso y el checklist de costos, memoria, comprobante, oficio y foto; después valida la entrega oficial.
