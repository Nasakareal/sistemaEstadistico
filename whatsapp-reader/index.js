'use strict';

const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const qrcodeTerminal = require('qrcode-terminal');
const { Client, LocalAuth } = require('whatsapp-web.js');

require('dotenv').config({ path: path.join(__dirname, '.env') });

const apiBaseUrl = String(process.env.LARAVEL_API_BASE_URL || '')
    .trim()
    .replace(/\/+$/, '');
const apiSecret = String(process.env.WHATSAPP_WEB_READER_SECRET || '').trim();
const clientId = String(
    process.env.WHATSAPP_WEB_READER_CLIENT_ID || 'sistema-estadistico-reader'
).trim();
const watchedGroupIds = new Set(
    String(process.env.WHATSAPP_WEB_READER_GROUP_IDS || '')
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean)
);
const dataDirectory = path.join(__dirname, 'data');
const spoolPath = path.join(dataDirectory, 'pending-events.jsonl');
const qrImagePath = path.join(dataDirectory, 'whatsapp-reader-qr.png');
const authPath = path.join(__dirname, '.wwebjs_auth');
const headless = String(process.env.WHATSAPP_WEB_READER_HEADLESS || 'true') !== 'false';

if (!apiBaseUrl || !apiSecret) {
    console.error(
        'Falta LARAVEL_API_BASE_URL o WHATSAPP_WEB_READER_SECRET en whatsapp-reader/.env.'
    );
    process.exit(1);
}

fs.mkdirSync(dataDirectory, { recursive: true });

const puppeteer = { headless };
const chromeExecutablePath = String(process.env.CHROME_EXECUTABLE_PATH || '').trim();

if (chromeExecutablePath) {
    puppeteer.executablePath = chromeExecutablePath;
}

const client = new Client({
    authStrategy: new LocalAuth({
        clientId,
        dataPath: authPath,
    }),
    puppeteer,
});

let eventChain = Promise.resolve();

function delay(milliseconds) {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

function errorDetails(error) {
    if (error && error.stack) {
        return error.stack;
    }

    if (error && error.message) {
        return error.message;
    }

    try {
        return JSON.stringify(error);
    } catch (serializationError) {
        return String(error);
    }
}

async function postJson(endpoint, payload, attempts = 3) {
    let lastError;

    for (let attempt = 1; attempt <= attempts; attempt += 1) {
        try {
            const response = await fetch(`${apiBaseUrl}${endpoint}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-WhatsApp-Reader-Secret': apiSecret,
                },
                body: JSON.stringify(payload),
                signal: AbortSignal.timeout(15000),
            });

            if (!response.ok) {
                const body = await response.text();
                throw new Error(`HTTP ${response.status}: ${body.slice(0, 500)}`);
            }

            return await response.json();
        } catch (error) {
            lastError = error;

            if (attempt < attempts) {
                await delay(attempt * 1500);
            }
        }
    }

    throw lastError;
}

function appendToSpool(endpoint, payload) {
    fs.appendFileSync(
        spoolPath,
        `${JSON.stringify({ endpoint, payload })}\n`,
        'utf8'
    );
}

async function deliverOrSpool(endpoint, payload) {
    try {
        return await postJson(endpoint, payload);
    } catch (error) {
        appendToSpool(endpoint, payload);
        console.error(`Evento guardado para reintento: ${error.message}`);
        return null;
    }
}

async function flushSpool() {
    if (!fs.existsSync(spoolPath)) {
        return;
    }

    const events = fs
        .readFileSync(spoolPath, 'utf8')
        .split(/\r?\n/)
        .filter(Boolean)
        .map((line) => JSON.parse(line));
    const pending = [];

    for (const event of events) {
        try {
            await postJson(event.endpoint, event.payload, 1);
        } catch (error) {
            pending.push(event);
        }
    }

    const contents = pending.map((event) => JSON.stringify(event)).join('\n');
    fs.writeFileSync(spoolPath, contents ? `${contents}\n` : '', 'utf8');

    if (events.length > pending.length) {
        console.log(`Eventos recuperados: ${events.length - pending.length}`);
    }
}

function serializeGroup(chat) {
    return {
        id: chat.id._serialized,
        name: chat.name || null,
        participant_count: Array.isArray(chat.participants) ? chat.participants.length : 0,
    };
}

async function discoverGroups() {
    let chats = null;
    let lastError = null;

    for (let attempt = 1; attempt <= 2; attempt += 1) {
        try {
            chats = await client.getChats();
            break;
        } catch (error) {
            lastError = error;
            console.error(
                `La consulta completa de chats falló (intento ${attempt}/2): ${errorDetails(error)}`
            );

            if (attempt < 2) {
                await delay(3000);
            }
        }
    }

    let groups;

    if (Array.isArray(chats)) {
        groups = chats
            .filter((chat) => chat.isGroup && chat.id && chat.id._serialized)
            .map(serializeGroup);
    } else {
        console.log('Usando descubrimiento básico de sólo lectura como alternativa segura.');
        groups = await client.pupPage.evaluate(() => {
            const chatModels = window
                .require('WAWebCollections')
                .Chat.getModelsArray();

            return chatModels
                .filter((chat) => {
                    const id = chat?.id?._serialized || '';
                    return id.endsWith('@g.us');
                })
                .map((chat) => {
                    const participants = chat.groupMetadata?.participants;
                    let participantCount = 0;

                    if (Array.isArray(participants)) {
                        participantCount = participants.length;
                    } else if (participants?.getModelsArray) {
                        participantCount = participants.getModelsArray().length;
                    } else if (Number.isInteger(participants?.length)) {
                        participantCount = participants.length;
                    }

                    return {
                        id: chat.id._serialized,
                        name: chat.formattedTitle || chat.name || null,
                        participant_count: participantCount,
                    };
                });
        });
    }

    if (!Array.isArray(groups)) {
        throw lastError || new Error('WhatsApp no devolvió la lista de grupos.');
    }

    console.log('\nGrupos encontrados:');

    for (const group of groups) {
        console.log(`- ${group.name || '(sin nombre)'}: ${group.id}`);
    }

    await deliverOrSpool('/whatsapp-web-reader/groups', { groups });
    return groups;
}

async function processIncomingMessage(message) {
    const groupId = String(message.from || '');

    if (!groupId.endsWith('@g.us')) {
        return;
    }

    if (watchedGroupIds.size === 0 || !watchedGroupIds.has(groupId)) {
        return;
    }

    const chat = await message.getChat();
    const payload = {
        group: {
            id: groupId,
            name: chat.name || null,
        },
        message: {
            id: message.id._serialized,
            author_id: message.author || null,
            body: message.body || null,
            type: message.type || 'unknown',
            has_media: Boolean(message.hasMedia),
            timestamp: Number(message.timestamp),
        },
    };

    await deliverOrSpool('/whatsapp-web-reader/messages', payload);
    console.log(`[${chat.name || groupId}] mensaje almacenado: ${message.id._serialized}`);
}

client.on('qr', async (qr) => {
    console.log('Escanea este QR con el número que únicamente leerá el grupo:');
    qrcodeTerminal.generate(qr, { small: true });

    try {
        await QRCode.toFile(qrImagePath, qr, {
            errorCorrectionLevel: 'M',
            margin: 2,
            width: 640,
        });
        console.log(`QR guardado temporalmente en: ${qrImagePath}`);
    } catch (error) {
        console.error(`No se pudo guardar el QR: ${error.message}`);
    }
});

client.on('authenticated', () => {
    console.log('Sesión de WhatsApp autenticada.');
});

client.on('auth_failure', (message) => {
    console.error(`Falló la autenticación: ${message}`);
});

client.on('ready', () => {
    console.log('Cliente de WhatsApp listo; consultando grupos en modo de sólo lectura...');
    eventChain = eventChain
        .then(flushSpool)
        .then(discoverGroups)
        .then(() => {
            if (watchedGroupIds.size === 0) {
                console.log(
                    '\nModo descubrimiento: no se almacenarán mensajes hasta configurar WHATSAPP_WEB_READER_GROUP_IDS.'
                );
            } else {
                console.log(`Grupos autorizados para lectura: ${[...watchedGroupIds].join(', ')}`);
            }
        })
        .catch((error) => console.error(`Error al iniciar el lector: ${errorDetails(error)}`));
});

client.on('message', (message) => {
    eventChain = eventChain
        .then(() => processIncomingMessage(message))
        .catch((error) => console.error(`Error al procesar mensaje: ${errorDetails(error)}`));
});

client.on('disconnected', (reason) => {
    console.error(`WhatsApp se desconectó: ${reason}`);
});

process.on('SIGINT', async () => {
    console.log('Cerrando lector...');
    await client.destroy();
    process.exit(0);
});

console.log('Iniciando lector de WhatsApp en modo de sólo lectura...');
client.initialize().catch((error) => {
    console.error(`No se pudo iniciar WhatsApp: ${error.message}`);
    process.exit(1);
});
