# Lector de grupos de WhatsApp

Este proceso usa `whatsapp-web.js` exclusivamente para observar grupos permitidos y almacenar sus mensajes en Laravel. No contiene ninguna llamada a `sendMessage` ni descarga archivos multimedia.

## Primer arranque

1. Copiar `.env.example` como `.env` dentro de esta carpeta.
2. Usar el mismo valor de `WHATSAPP_WEB_READER_SECRET` configurado en el `.env` de Laravel.
3. Dejar `WHATSAPP_WEB_READER_GROUP_IDS` vacío.
4. Ejecutar `npm install` en esta carpeta.
5. Ejecutar `npm start` y escanear el QR.

Al conectarse, el proceso imprime y registra todos los grupos visibles, pero no almacena sus mensajes. Después se copia únicamente el ID deseado a `WHATSAPP_WEB_READER_GROUP_IDS` y se reinicia el proceso.

Los mensajes pendientes por una caída temporal de Laravel se conservan en `data/pending-events.jsonl`. La sesión de WhatsApp queda en `.wwebjs_auth`; ambas rutas están excluidas de Git.
