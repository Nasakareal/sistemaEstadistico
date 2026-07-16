'use strict';

// Este proceso no contiene ninguna operación que escriba en WhatsApp.
// Sólo observa el grupo permitido y entrega una copia
// de los metadatos y el texto al endpoint interno de Laravel.

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const QRCode = require('qrcode');
const qrcodeTerminal = require('qrcode-terminal');
const { Client, LocalAuth } = require('whatsapp-web.js');

require('dotenv').config({ path: path.join(__dirname, '.env') });

const apiBaseUrl = String(process.env.LARAVEL_API_BASE_URL || '')
    .trim()
    .replace(/\/+$/, '');
const apiSecret = String(process.env.WHATSAPP_WEB_READER_SECRET || '').trim();
const clientId = String(
    process.env.WHATSAPP_WEB_READER_CLIENT_ID ||
        'sistema-estadistico-reader-produccion'
).trim();
const watchedGroupIds = new Set(
    String(process.env.WHATSAPP_WEB_READER_GROUP_IDS || '')
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean)
);
const allowedAuthorIds = new Set(
    String(process.env.WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS || '')
        .split(',')
        .map((value) => value.trim().toLowerCase())
        .filter(Boolean)
);
const dataDirectory = path.join(__dirname, 'data');
const spoolPath = path.join(dataDirectory, 'pending-events.jsonl');
const qrImagePath = path.join(dataDirectory, 'whatsapp-reader-qr.png');
const authPath = path.join(__dirname, '.wwebjs_auth');
const headless = String(process.env.WHATSAPP_WEB_READER_HEADLESS || 'true') !== 'false';

if (!apiBaseUrl || !apiSecret || watchedGroupIds.size === 0 || allowedAuthorIds.size === 0) {
    console.error(
        'Falta LARAVEL_API_BASE_URL, WHATSAPP_WEB_READER_SECRET, WHATSAPP_WEB_READER_GROUP_IDS o WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS en .env.'
    );
    process.exit(1);
}

let parsedApiUrl;

try {
    parsedApiUrl = new URL(apiBaseUrl);
} catch (error) {
    console.error('LARAVEL_API_BASE_URL no es una URL válida.');
    process.exit(1);
}

if (parsedApiUrl.protocol !== 'https:') {
    console.error('Producción exige una LARAVEL_API_BASE_URL con HTTPS.');
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

const knownGroupNames = new Map();
let eventChain = Promise.resolve();

function delay(milliseconds) {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

function errorDetails(error) {
    return error?.stack || error?.message || String(error);
}

function identifierAllowed(identifier, allowedIdentifiers) {
    const normalized = String(identifier || '').trim().toLowerCase();

    if (!normalized) {
        return false;
    }

    if (allowedIdentifiers.has(normalized)) {
        return true;
    }

    const digits = normalized.replace(/\D+/g, '');

    return digits !== '' && [...allowedIdentifiers].some(
        (allowed) => allowed.replace(/\D+/g, '') === digits
    );
}

function eventAllowed(endpoint, payload) {
    if (endpoint !== '/whatsapp-web-reader/messages') {
        return true;
    }

    return watchedGroupIds.has(String(payload?.group?.id || '').trim())
        && identifierAllowed(payload?.message?.author_id, allowedAuthorIds);
}

function fallbackMessageId(groupId, message) {
    const signature = JSON.stringify({
        group_id: String(groupId || ''),
        author_id: String(message?.author_id || message?.author || ''),
        body: String(message?.body || ''),
        type: String(message?.type || 'unknown'),
        has_media: Boolean(message?.has_media ?? message?.hasMedia),
        timestamp: Number(message?.timestamp) || null,
    });
    const hash = crypto.createHash('sha256').update(signature).digest('hex');

    return `fallback_${hash}`;
}

function liveMessageId(message, groupId) {
    const serializedCandidates = [
        message?.id?._serialized,
        message?._data?.id?._serialized,
        typeof message?.id === 'string' ? message.id : null,
    ];

    for (const candidate of serializedCandidates) {
        const value = String(candidate || '').trim();

        if (value && value !== 'undefined' && value !== 'null') {
            return value.length <= 191
                ? value
                : fallbackMessageId(groupId, message);
        }
    }

    const internalId = String(
        message?.id?.id || message?._data?.id?.id || ''
    ).trim();

    if (internalId) {
        const fromMe = Boolean(message?.id?.fromMe ?? message?._data?.id?.fromMe);
        const combined = `${fromMe ? 'true' : 'false'}_${groupId}_${internalId}`;

        return combined.length <= 191
            ? combined
            : fallbackMessageId(groupId, message);
    }

    return fallbackMessageId(groupId, message);
}

function normalizeEventPayload(endpoint, payload) {
    if (endpoint !== '/whatsapp-web-reader/messages') {
        return payload;
    }

    const group = { ...(payload?.group || {}) };
    const message = { ...(payload?.message || {}) };
    const currentId = String(message.id || '').trim();

    if (!currentId || currentId === 'undefined' || currentId === 'null') {
        message.id = fallbackMessageId(group.id, message);
    }

    const timestamp = Number(message.timestamp);

    if (!Number.isInteger(timestamp) || timestamp < 1) {
        message.timestamp = Math.floor(Date.now() / 1000);
    }

    return { ...payload, group, message };
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
    const normalizedPayload = normalizeEventPayload(endpoint, payload);

    try {
        return await postJson(endpoint, normalizedPayload);
    } catch (error) {
        appendToSpool(endpoint, normalizedPayload);
        console.error(`Evento guardado para reintento: ${errorDetails(error)}`);
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
    let discarded = 0;

    for (const event of events) {
        const normalizedEvent = {
            endpoint: event.endpoint,
            payload: normalizeEventPayload(event.endpoint, event.payload),
        };

        if (!eventAllowed(normalizedEvent.endpoint, normalizedEvent.payload)) {
            discarded += 1;
            continue;
        }

        try {
            await postJson(normalizedEvent.endpoint, normalizedEvent.payload, 1);
        } catch (error) {
            pending.push(normalizedEvent);
        }
    }

    const contents = pending.map((event) => JSON.stringify(event)).join('\n');
    fs.writeFileSync(spoolPath, contents ? `${contents}\n` : '', 'utf8');

    if (events.length > pending.length) {
        console.log(`Eventos recuperados: ${events.length - pending.length - discarded}`);
    }

    if (discarded > 0) {
        console.log(`Eventos descartados por filtros de grupo/remitente: ${discarded}`);
    }
}

async function discoverGroups() {
    // Lectura mínima para evitar serializar metadatos innecesarios de chats.
    const groups = await client.pupPage.evaluate(() => {
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

    for (const group of groups) {
        knownGroupNames.set(group.id, group.name || null);
        console.log(`Grupo visible: ${group.name || '(sin nombre)'} (${group.id})`);
    }

    await deliverOrSpool('/whatsapp-web-reader/groups', {
        groups: groups.filter((group) => watchedGroupIds.has(group.id)),
    });
}

async function processIncomingMessage(message) {
    const groupId = String(message.from || '');

    if (!groupId.endsWith('@g.us') || !watchedGroupIds.has(groupId)) {
        return;
    }

    const authorId = String(message.author || '').trim();

    if (!identifierAllowed(authorId, allowedAuthorIds)) {
        console.log(`Mensaje ignorado por remitente no autorizado: ${authorId || '(sin autor)'}`);
        return;
    }

    const messageId = liveMessageId(message, groupId);
    const timestamp = Number(message.timestamp);
    const payload = {
        group: {
            id: groupId,
            name: knownGroupNames.get(groupId) || null,
        },
        message: {
            id: messageId,
            author_id: authorId,
            body: message.body || null,
            type: message.type || 'unknown',
            has_media: Boolean(message.hasMedia),
            timestamp: Number.isInteger(timestamp) && timestamp > 0
                ? timestamp
                : Math.floor(Date.now() / 1000),
        },
    };

    const response = await deliverOrSpool('/whatsapp-web-reader/messages', payload);

    if (response?.stored === false) {
        console.log(`Mensaje ignorado por Laravel (${response.reason || 'filtro'}): ${messageId}`);
    } else if (response) {
        console.log(`Mensaje entrante almacenado: ${messageId}`);
    } else {
        console.log(`Mensaje entrante pendiente de reintento: ${messageId}`);
    }
}

client.on('qr', async (qr) => {
    console.log('Escanea este QR desde WhatsApp > Dispositivos vinculados:');
    qrcodeTerminal.generate(qr, { small: true });

    try {
        await QRCode.toFile(qrImagePath, qr, {
            errorCorrectionLevel: 'M',
            margin: 2,
            width: 640,
        });
        console.log(`QR temporal: ${qrImagePath}`);
    } catch (error) {
        console.error(`No se pudo guardar el QR: ${errorDetails(error)}`);
    }
});

client.on('authenticated', () => {
    console.log('Sesión autenticada.');
});

client.on('auth_failure', (message) => {
    console.error(`Falló la autenticación: ${message}`);
});

client.on('ready', () => {
    console.log('Lector listo en modo de sólo lectura.');
    eventChain = eventChain
        .then(flushSpool)
        .then(discoverGroups)
        .then(() => {
            console.log(`Grupo autorizado: ${[...watchedGroupIds].join(', ')}`);
            console.log(`Remitentes C5i autorizados: ${[...allowedAuthorIds].join(', ')}`);
        })
        .catch((error) => console.error(`Error al iniciar: ${errorDetails(error)}`));
});

client.on('message', (message) => {
    eventChain = eventChain
        .then(() => processIncomingMessage(message))
        .catch((error) => console.error(`Error al almacenar: ${errorDetails(error)}`));
});

client.on('disconnected', (reason) => {
    console.error(`WhatsApp se desconectó: ${reason}`);
});

async function shutdown(signal) {
    console.log(`Cerrando lector por ${signal}...`);
    await client.destroy();
    process.exit(0);
}

process.on('SIGINT', () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));

console.log('Iniciando lector de producción sin capacidad de envío...');
client.initialize().catch((error) => {
    console.error(`No se pudo iniciar: ${errorDetails(error)}`);
    process.exit(1);
});
