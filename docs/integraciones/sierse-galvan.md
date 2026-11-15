# Integracion privada SIERSE / Galvan

Estado: autenticacion y enlace preparados; alcance bidireccional acordado. El intercambio de expedientes permanece apagado hasta aprobar el contrato final con Galvan.

## Alcance y seguridad

Esta API es exclusivamente servidor a servidor. La aplicacion Flutter no debe conocer el secreto ni firmar solicitudes. El backend de Galvan se conecta al backend de Seguridad Vial mediante HTTPS.

La aplicacion exige simultaneamente:

- integracion habilitada expresamente;
- IP publica incluida en lista blanca (admite IP exacta o CIDR IPv4/IPv6);
- firma HMAC-SHA256 distinta para cada solicitud;
- timestamp dentro de la ventana permitida;
- nonce unico para impedir reenvios;
- limites por cliente e IP.
- certificado de cliente mTLS valido cuando `SIERSE_API_REQUIRE_MTLS=true`.

En produccion se recomienda agregar una barrera de red (VPN, mTLS, firewall o allowlist del proxy) ademas de estas validaciones de aplicacion.

Para Galvan se eligio mTLS en lugar de VPN. El certificado se valida durante la conexion HTTPS y la firma HMAC continua siendo obligatoria.

## Activacion

Configurar en el servidor, sin confirmar estos valores al repositorio:

```dotenv
SIERSE_API_ENABLED=true
SIERSE_API_DATA_EXCHANGE_ENABLED=false
SIERSE_API_REQUIRE_MTLS=true
SIERSE_API_CLIENT_ID=galvan
SIERSE_API_CLIENT_NAME="Gruas Galvan"
SIERSE_API_KEY_ID=galvan-AAAAMMDD-identificador
SIERSE_API_CLIENT_SECRET=<64 o mas caracteres aleatorios>
SIERSE_API_SECRET_EXPIRES_AT=<fecha ISO-8601 futura>
SIERSE_API_ALLOWED_IPS=203.0.113.10,2001:db8:1234::/48
SIERSE_API_CERTIFICATE_FINGERPRINTS=<SHA-256 DEL CERTIFICADO DE GALVAN>
```

Para generar el secreto HMAC sin escribirlo automaticamente en `.env`:

```text
php artisan sierse:generate-credentials galvan
```

En Apache, la ruta de integracion debe solicitar el certificado contra la CA institucional y exportarlo a PHP:

```apache
<Location "/api/integraciones/sierse/">
    SSLVerifyClient require
    SSLVerifyDepth 2
    SSLOptions +ExportCertData
    SSLCACertificateFile "C:/ruta/ca-institucional.pem"
</Location>
```

El servidor debe entregar a PHP `SSL_CLIENT_VERIFY=SUCCESS` y `SSL_CLIENT_CERT`. No se aceptan encabezados enviados directamente por el cliente como sustituto de estas variables TLS.

Despues de cambiar el entorno se debe limpiar la cache de configuracion de Laravel.

## Firma de solicitudes

Encabezados obligatorios:

```text
X-SIERSE-Client: galvan
X-SIERSE-Key-Id: <identificador de la llave activa>
X-SIERSE-Timestamp: <Unix timestamp en segundos>
X-SIERSE-Nonce: <valor aleatorio unico de 16 a 128 caracteres>
X-SIERSE-Signature: <HMAC-SHA256 hexadecimal>
```

La cadena canonica se forma exactamente asi, conservando el orden de los parametros de consulta:

```text
METODO_HTTP
/api/integraciones/sierse/v1/estado
galvan
KEY_ID
TIMESTAMP
NONCE
SHA256_HEXADECIMAL_DEL_CUERPO
```

La firma es `HMAC-SHA256(cadena_canonica, secreto_compartido)` en hexadecimal minusculo. Para un GET sin cuerpo, el ultimo renglon es el SHA-256 de una cadena vacia.

Endpoint inicial de enlace:

```text
GET /api/integraciones/sierse/v1/estado
```

Este endpoint confirma autenticacion, reloj y conectividad. No entrega datos operativos.

El enlace puede probarse con `SIERSE_API_ENABLED=true` mientras `SIERSE_API_DATA_EXCHANGE_ENABLED=false`. Esta separacion evita abrir expedientes durante las pruebas de conectividad.

## Flujo acordado

### Seguridad Vial hacia Galvan

Solo se entregaran vehiculos cuyo `grua_id` corresponda a la grua configurada como `SIERSE_API_GRUA_NAME=GALVAN`.

Expediente permitido del vehiculo:

- identificador interno y UUID de sincronizacion;
- folio SIERSE y numero de inventario de grua;
- marca, linea, modelo, tipo, color, placas, estado de placas, permiso y serie;
- capacidad, tipo de servicio, aseguradora, antecedente y reporte de robo;
- corralon, fecha de inventario, monto y descripcion de danos;
- datos del servicio de arrastre: origen, destino, autoridad solicitante, fechas, operador, unidad de grua, kilometraje, maniobras y observaciones operativas;
- fotos del vehiculo, inventario y servicio.

Las fotos se serviran mediante endpoints autenticados con la misma firma HMAC. No se generaran URLs publicas permanentes y cada descarga volvera a comprobar cliente, IP, firma y pertenencia del vehiculo a Galvan.

### Informacion que nunca se entrega

- conductores o relaciones `vehiculo_conductor`;
- nombres, CURP, identificaciones u otros datos de conductores;
- telefonos de conductores;
- domicilios de conductores;
- credenciales, tokens o rutas internas de almacenamiento;
- fotos de identificaciones personales.

La exclusion se hara en el serializador de integracion mediante una lista positiva de campos. No se serializara el modelo completo de Laravel.

### Galvan hacia Seguridad Vial

Se podran recibir, con un identificador unico por evento:

- costo total y desglose de arrastre, maniobras, almacenaje, impuestos y otros conceptos;
- moneda, forma de pago, comprobante y estado de pago;
- kilometros, operador y unidad de grua;
- fechas de solicitud, arribo, inicio, fin e ingreso a corralon;
- estado operativo del servicio;
- liberado/no liberado, fecha de liberacion, folio SIERSE y condicion de entrega;
- observaciones y evidencias operativas;
- datos adicionales dentro de un objeto versionado acordado.

Los datos adicionales no podran sustituir campos protegidos ni ejecutar acciones. Se conservara el payload original para auditoria, junto con cliente, evento, fecha de recepcion, estado de procesamiento y resultado.

Cada evento sera idempotente: si Galvan repite el mismo identificador, el sistema devolvera el resultado previo sin duplicar costos ni liberaciones.

Una liberacion reportada por Galvan se registrara primero como evento externo trazable. La regla para convertirla en liberacion oficial debe definir previamente evidencias obligatorias y responsables; no se sobrescribira silenciosamente una entrega ya confirmada en Seguridad Vial.

## Endpoints de datos previstos

Se conservaran bajo la misma version y autenticacion:

```text
GET  /api/integraciones/sierse/v1/buscar?type={tipo}&value={valor}
GET  /api/integraciones/sierse/v1/vehiculos
GET  /api/integraciones/sierse/v1/vehiculos/{id}
GET  /api/integraciones/sierse/v1/vehiculos/{id}/fotos/{tipo}
POST /api/integraciones/sierse/v1/vehiculos/{id}/eventos
GET  /api/integraciones/sierse/v1/eventos/{external_event_id}
```

La busqueda queda preparada con estos tipos:

| `type` | Ejemplo | Campo |
|---|---|---|
| `inventory` | `00086` | Numero de inventario |
| `plate` | `PFH-123-A` | Placas, ignorando espacios, puntos y guiones |
| `vin` | `3N1AB7AP...` | Numero de serie, ignorando espacios y guiones |
| `conduce_folio` | `CL-86-213` | Folio de Conduce con Legalidad |

Antes de comparar el identificador, la consulta se limita a registros cuyo `grua_id` o `corralon_id` corresponde a Galvan. Si el identificador no existe o pertenece a otra grua se responde el mismo `404`, sin confirmar que existe en el sistema. Un folio con sufijo, por ejemplo `CL-86-213-2`, selecciona el segundo vehiculo de esa captura.

No se activaran hasta conocer el formato que Galvan puede consumir y producir. Si Galvan ya cuenta con API propia, se mantendra el mismo contrato de privacidad y la integracion podra operar mediante un proceso saliente en lugar de exponer estos endpoints.

## Pendientes antes de compartir datos

1. Solicitar a Galvan su documentacion de API o confirmar que consumiran la nuestra.
2. Aprobar nombres, tipos y obligatoriedad de los campos descritos arriba.
3. Definir catalogos de estados, conceptos de costo y reglas de correccion.
4. Acordar evidencias requeridas para reconocer una liberacion como oficial.
5. Acordar ventana historica, paginacion, frecuencia y retencion.
6. Intercambiar IP y secreto por un canal distinto al correo o al chat operativo.
7. Ejecutar pruebas en un ambiente separado antes de habilitar produccion.
