# Lector de WhatsApp: traslado a producción

Esta carpeta es el paquete que debes copiar. Está fuera de `public` y no contiene el secreto real ni la sesión de WhatsApp. En el servidor también debe permanecer fuera de la raíz pública del sitio.

No copies aquí el `.env` real ni `.wwebjs_auth`. El `.gitignore` excluye esos archivos, el QR, los mensajes pendientes y `node_modules`.

## 1. Publicar los cambios de Laravel

Despliega el proyecto Laravel completo, incluidos el controlador, los modelos, las rutas y la migración del lector. En producción ejecuta:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan config:cache
php artisan route:cache
```

Genera un secreto exclusivo de producción:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Agrégalo al `.env` real de Laravel:

```dotenv
WHATSAPP_WEB_READER_SECRET=EL_SECRETO_GENERADO
```

No uses el secreto de localhost.

## 2. Copiar e instalar el lector Node

El servidor necesita Node.js 20 o posterior y Chrome/Chromium. El lector no abre un puerto público: únicamente hace conexiones salientes hacia WhatsApp Web y hacia la API HTTPS de Laravel.

Primero crea una carpeta privada conectándote al servidor:

```bash
ssh usuario@servidor
mkdir -p ~/apps
exit
```

Desde esta computadora copia la carpeta completa, cambiando el usuario y el servidor:

```bash
scp -r deploy/whatsapp-reader-produccion usuario@servidor:~/apps/
```

Si el repositorio ya está desplegado en el servidor, puedes copiar el paquete desde allí:

```bash
mkdir -p ~/apps/whatsapp-reader-produccion
cp -R deploy/whatsapp-reader-produccion/. ~/apps/whatsapp-reader-produccion/
```

Luego instala y crea el `.env` privado del lector:

```bash
cd ~/apps/whatsapp-reader-produccion
cp reader.env.example .env
npm install --omit=dev
```

Edita `~/apps/whatsapp-reader-produccion/.env`:

```dotenv
LARAVEL_API_BASE_URL=https://TU-DOMINIO-REAL/api
WHATSAPP_WEB_READER_SECRET=EL_MISMO_SECRETO_DE_LARAVEL
WHATSAPP_WEB_READER_GROUP_IDS=120363424100430316@g.us
WHATSAPP_WEB_READER_CLIENT_ID=sistema-estadistico-reader-produccion
WHATSAPP_WEB_READER_HEADLESS=true
```

La URL debe usar HTTPS y no debe decir `localhost`. Si el dominio tiene `public` configurado como raíz web, la URL tampoco lleva `/public`.

## 3. Vincular el número en producción

El número telefónico no se escribe en el `.env`. Se vincula escaneando el QR generado por el proceso que corre en el servidor. Conéctate por SSH y haz el primer arranque:

```bash
cd ~/apps/whatsapp-reader-produccion
node index.js
```

En el teléfono abre **WhatsApp > Dispositivos vinculados > Vincular dispositivo** y escanea el QR de esa terminal. La sesión quedará en `~/apps/whatsapp-reader-produccion/.wwebjs_auth`. No borres ni publiques esa carpeta.

El ID `120363424100430316@g.us` identifica al grupo `SINIESTROS GC`; no es el número telefónico y no concede capacidad de envío.

## 4. Mantener el lector ejecutándose

Cuando el primer arranque indique que el lector está listo, detenlo con `Ctrl+C` e inicia PM2:

```bash
npm install -g pm2
cd ~/apps/whatsapp-reader-produccion
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup
```

Ejecuta también el comando que muestre `pm2 startup`. Para revisar el proceso:

```bash
pm2 status
pm2 logs sistema-estadistico-whatsapp-reader
```

En Windows Server registra `node index.js` como servicio con NSSM o el Programador de tareas y usa la carpeta del lector como directorio de trabajo.

## 5. Verificar y retirar localhost

1. Sin encabezado secreto, `https://TU-DOMINIO-REAL/api/whatsapp-web-reader/groups` debe responder `403`.
2. Los logs deben mostrar `Lector listo en modo de sólo lectura` y el grupo `120363424100430316@g.us`.
3. La tabla `whatsapp_web_groups` debe contener `SINIESTROS GC`.
4. Cuando llegue un mensaje normal al grupo, comprueba que aparece en `whatsapp_web_messages`.
5. Sólo después de confirmar lo anterior, detén el lector local para que la sesión activa quede únicamente en producción.

El `index.js` sólo observa el grupo permitido, envía una copia del evento a Laravel y no descarga multimedia.
