'use strict';

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
    process.env.WHATSAPP_WEB_READER_CLIENT_ID || 'sistema-estadistico-reader'
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
const allowOperationalAuthors = String(
    process.env.WHATSAPP_WEB_READER_ALLOW_OPERATIONAL_AUTHORS || 'false'
).trim().toLowerCase() === 'true';
const dataDirectory = path.join(__dirname, 'data');
const spoolPath = path.join(dataDirectory, 'pending-events.jsonl');
const qrImagePath = path.join(dataDirectory, 'whatsapp-reader-qr.png');
const authPath = path.join(__dirname, '.wwebjs_auth');
const headless = String(process.env.WHATSAPP_WEB_READER_HEADLESS || 'true') !== 'false';

if (!apiBaseUrl || !apiSecret || watchedGroupIds.size === 0
    || (allowedAuthorIds.size === 0 && !allowOperationalAuthors)) {
    console.error(
        'Falta LARAVEL_API_BASE_URL, WHATSAPP_WEB_READER_SECRET, WHATSAPP_WEB_READER_GROUP_IDS o WHATSAPP_WEB_READER_ALLOWED_AUTHOR_IDS en whatsapp-reader/.env.'
    );
    process.exit(1);
}

function operationalMessageCandidate(body) {
    const text = String(body || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9\s]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    if (!text) {
        return false;
    }

    if (text.includes('LATITUD') && text.includes('LONGITUD')) {
        return true;
    }

    return /(?:^|\s)86(?:\s|$)|\b(?:APROX|ACUDE|ATIENDE|DIRIGETE|DIRIJASE|ASIGNAD[AO]|ARRIB|LLEG|YA\s+(?:ESTAMOS\s+)?EN|EN\s+EL\s+LUGAR|EN\s+EL\s+PUNTO|EN\s+PUNTO|PRESENTES\s+EN|EN\s+(?:EL\s+)?40|EN\s+(?:EL\s+)?K\s*\d+)|\bK\s*\d+\b/.test(text);
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

async function resolveAuthorIdentity(authorId) {
    const originalId = String(authorId || '').trim().toLowerCase();
    const identifiers = [originalId].filter(Boolean);

    if (originalId.endsWith('@lid')) {
        try {
            const mappings = await client.getContactLidAndPhone([originalId]);
            const mapping = Array.isArray(mappings) ? mappings[0] : null;

            for (const identifier of [mapping?.lid, mapping?.pn]) {
                const normalized = String(identifier || '').trim().toLowerCase();

                if (normalized && !identifiers.includes(normalized)) {
                    identifiers.push(normalized);
                }
            }
        } catch (error) {
            console.error(
                `No se pudo resolver el remitente LID ${originalId}: ${errorDetails(error)}`
            );
        }
    }

    const allowed = identifiers.some(
        (identifier) => identifierAllowed(identifier, allowedAuthorIds)
    );
    const phoneId = identifiers.find(
        (identifier) => identifier.endsWith('@c.us') || identifier.endsWith('@s.whatsapp.net')
    );

    return {
        allowed,
        authorId: phoneId || originalId,
        originalId,
    };
}

function eventAllowed(endpoint, payload) {
    if (endpoint !== '/whatsapp-web-reader/messages') {
        return true;
    }

    return watchedGroupIds.has(String(payload?.group?.id || '').trim())
        && (identifierAllowed(payload?.message?.author_id, allowedAuthorIds)
            || (allowOperationalAuthors
                && operationalMessageCandidate(payload?.message?.body)));
}

function fallbackMessageId(groupId, message) {
    const fields = {
        group_id: String(groupId || ''),
        author_id: String(message?.author_id || message?.author || ''),
        body: String(message?.body || ''),
        type: String(message?.type || 'unknown'),
        has_media: Boolean(message?.has_media ?? message?.hasMedia),
        timestamp: Number(message?.timestamp) || null,
    };

    if (message?.quoted_message_id) {
        fields.quoted_message_id = String(message.quoted_message_id);
    }

    const signature = JSON.stringify(fields);
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

    await deliverOrSpool('/whatsapp-web-reader/groups', {
        groups: groups.filter((group) => watchedGroupIds.has(group.id)),
    });
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

    const author = await resolveAuthorIdentity(message.author);

    if (!author.allowed
        && !(allowOperationalAuthors && operationalMessageCandidate(message.body))) {
        console.log(`Mensaje ignorado por remitente no autorizado: ${author.originalId || '(sin autor)'}`);
        return;
    }

    if (author.authorId !== author.originalId) {
        console.log(`Remitente LID resuelto: ${author.originalId} -> ${author.authorId}`);
    }

    const chat = await message.getChat();
    const messageId = liveMessageId(message, groupId);
    let quotedMessageId = null;

    if (message.hasQuotedMsg) {
        try {
            const quotedMessage = await message.getQuotedMessage();
            quotedMessageId = liveMessageId(quotedMessage, groupId);
        } catch (error) {
            console.error(`No se pudo resolver el mensaje citado: ${errorDetails(error)}`);
        }
    }
    const timestamp = Number(message.timestamp);
    const payload = {
        group: {
            id: groupId,
            name: chat.name || null,
        },
        message: {
            id: messageId,
            quoted_message_id: quotedMessageId,
            author_id: author.authorId,
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
        console.log(`[${chat.name || groupId}] mensaje ignorado por Laravel (${response.reason || 'filtro'}): ${messageId}`);
    } else if (response) {
        console.log(
            `[${chat.name || groupId}] mensaje almacenado: ${messageId}; recomendación: ${response.recommendation_status || 'sin estado'}`
        );
    } else {
        console.log(`[${chat.name || groupId}] mensaje pendiente de reintento: ${messageId}`);
    }
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
                console.log(`Remitentes C5i autorizados: ${[...allowedAuthorIds].join(', ')}`);
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
