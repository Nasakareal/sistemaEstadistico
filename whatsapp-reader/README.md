# Lector de grupos de WhatsApp

Este proceso usa `whatsapp-web.js` exclusivamente para observar grupos permitidos y almacenar sus mensajes en Laravel. No contiene ninguna llamada a `sendMessage`. Cuando existe un servicio C5i asignado, descarga temporalmente las notas de voz para que Laravel las transcriba; el lector no conserva el archivo después de enviarlo a la API.

## Primer arranque

1. Copiar `.env.example` como `.env` dentro de esta carpeta.
2. Usar el mismo valor de `WHATSAPP_WEB_READER_SECRET` configurado en el `.env` de Laravel.
3. Dejar `WHATSAPP_WEB_READER_GROUP_IDS` vacío.
4. Ejecutar `npm install` en esta carpeta.
5. Ejecutar `npm start` y escanear el QR.

Para reconocer arribos reportados por audio también se requiere:

- `WHATSAPP_WEB_READER_ALLOW_OPERATIONAL_AUTHORS=true` en este lector y en Laravel.
- `WHATSAPP_WEB_READER_AUDIO_MAX_BYTES=5242880` con el mismo límite configurado en Laravel.
- `OPENAI_API_KEY` configurada en el `.env` de Laravel.

Al conectarse, el proceso imprime y registra todos los grupos visibles, pero no almacena sus mensajes. Después se copia únicamente el ID deseado a `WHATSAPP_WEB_READER_GROUP_IDS` y se reinicia el proceso.

Los mensajes pendientes por una caída temporal de Laravel se conservan en `data/pending-events.jsonl`. La sesión de WhatsApp queda en `.wwebjs_auth`; ambas rutas están excluidas de Git.
