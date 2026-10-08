'use strict';

// Envío manual y de una sola ejecución al chat del propio número observador.
// El lector principal permanece estrictamente en modo de sólo lectura.

const path = require('path');
const { Client, LocalAuth } = require('whatsapp-web.js');

require('dotenv').config({ path: path.join(__dirname, '.env') });

if (!process.argv.includes('--confirmar')) {
    console.error('No se envió nada. Ejecuta de nuevo agregando --confirmar.');
    process.exit(2);
}

const clientId = String(
    process.env.WHATSAPP_WEB_READER_CLIENT_ID ||
        'sistema-estadistico-reader-produccion'
).trim();
const authPath = path.join(__dirname, '.wwebjs_auth');
const headless = String(process.env.WHATSAPP_WEB_READER_HEADLESS || 'true') !== 'false';
const executablePath = String(
    process.env.WHATSAPP_WEB_READER_CHROME_PATH || ''
).trim();

const puppeteer = {
    headless,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
};

if (executablePath) {
    puppeteer.executablePath = executablePath;
}

const client = new Client({
    authStrategy: new LocalAuth({ clientId, dataPath: authPath }),
    puppeteer,
});

let finishing = false;
const timeout = setTimeout(() => {
    void finish(1, 'Tiempo agotado esperando que WhatsApp Web quedara listo.');
}, 90000);

async function finish(exitCode, message) {
    if (finishing) {
        return;
    }

    finishing = true;
    clearTimeout(timeout);

    if (message) {
        (exitCode === 0 ? console.log : console.error)(message);
    }

    try {
        await client.destroy();
    } catch (_) {
        // El navegador puede haberse cerrado antes; no cambia el resultado del envío.
    }

    process.exit(exitCode);
}

client.once('qr', () => {
    void finish(
        3,
        'La sesión guardada ya no está autenticada. No se envió ningún mensaje ni debes escanear otro QR desde este comando.'
    );
});

client.once('auth_failure', (reason) => {
    void finish(3, `Falló la autenticación guardada: ${reason}`);
});

client.once('ready', async () => {
    try {
        const ownId = String(client.info?.wid?._serialized || '').trim();

        if (!/^\d+@c\.us$/.test(ownId)) {
            throw new Error('No se pudo identificar de forma segura el número observador.');
        }

        const timestamp = new Intl.DateTimeFormat('es-MX', {
            timeZone: 'America/Mexico_City',
            dateStyle: 'short',
            timeStyle: 'medium',
        }).format(new Date());
        const sent = await client.sendMessage(
            ownId,
            `Prueba de actividad de WhatsApp Web (${timestamp})`
        );

        // Da tiempo a que el navegador confirme la operación antes de cerrarse.
        await new Promise((resolve) => setTimeout(resolve, 3000));

        await finish(
            0,
            `Mensaje enviado al propio número observador. ID: ${sent?.id?._serialized || 'no disponible'}`
        );
    } catch (error) {
        await finish(1, `No se pudo enviar el mensaje: ${error.message}`);
    }
});

client.initialize().catch((error) => {
    void finish(1, `No se pudo iniciar WhatsApp Web: ${error.message}`);
});
