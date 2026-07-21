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
const pollIntervalMs = positiveIntegerEnv('WHATSAPP_WEB_READER_POLL_INTERVAL_MS', 60000, 15000);
const pollBackfillMinutes = positiveIntegerEnv('WHATSAPP_WEB_READER_POLL_BACKFILL_MINUTES', 15, 1);
const healthFailureLimit = positiveIntegerEnv('WHATSAPP_WEB_READER_HEALTH_FAILURE_LIMIT', 3, 1);
const spoolRetryIntervalMs = positiveIntegerEnv('WHATSAPP_WEB_READER_SPOOL_RETRY_INTERVAL_MS', 60000, 15000);
const audioMaxBytes = positiveIntegerEnv('WHATSAPP_WEB_READER_AUDIO_MAX_BYTES', 5 * 1024 * 1024, 1024);

function positiveIntegerEnv(name, fallback, minimum) {
    const parsed = Number.parseInt(String(process.env[name] || ''), 10);

    return Number.isInteger(parsed) && parsed >= minimum ? parsed : fallback;
}

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

    return /(?:^|\s)86(?:\s|$)|\b(?:APROX|ACUDE|ATIENDE|DIRIGETE|DIRIJASE|ASIGNAD[AO]|ARRIB|LLEG|YA\s+(?:ESTAMOS\s+)?EN|EN\s+EL\s+LUGAR|EN\s+EL\s+PUNTO|EN\s+PUNTO|PRESENTES\s+EN|EN\s+(?:EL\s+)?40|EN\s+(?:EL\s+)?K\s*\d+|INFORMA\s+UNIDAD)|\bK\s*\d+\b/.test(text);
}

function operationalAudioCandidate(message) {
    const type = String(message?.type || '').trim().toLowerCase();
    const hasMedia = Boolean(message?.has_media ?? message?.hasMedia);

    return hasMedia && ['audio', 'ptt', 'voice'].includes(type);
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
let readerReady = false;
let fatalExitScheduled = false;
let consecutiveHealthFailures = 0;
let pollInProgress = false;
let lastHealthLogAt = 0;
const seenMessageIds = new Set();
const seenMessageOrder = [];

function delay(milliseconds) {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

function withTimeout(promise, milliseconds, label) {
    let timeout;

    return Promise.race([
        promise,
        new Promise((resolve, reject) => {
            timeout = setTimeout(
                () => reject(new Error(`${label} excedió ${milliseconds} ms`)),
                milliseconds
            );
        }),
    ]).finally(() => clearTimeout(timeout));
}

function rememberMessageId(messageId) {
    const normalized = String(messageId || '').trim();

    if (!normalized || seenMessageIds.has(normalized)) {
        return;
    }

    seenMessageIds.add(normalized);
    seenMessageOrder.push(normalized);

    while (seenMessageOrder.length > 2000) {
        seenMessageIds.delete(seenMessageOrder.shift());
    }
}

function scheduleFatalExit(reason) {
    if (fatalExitScheduled) {
        return;
    }

    fatalExitScheduled = true;
    readerReady = false;
    console.error(`Reinicio requerido del lector: ${reason}`);

    setTimeout(() => process.exit(1), 1000);
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
            const mappings = await withTimeout(
                client.getContactLidAndPhone([originalId]),
                15000,
                `Resolución del remitente ${originalId}`
            );
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
                && (operationalMessageCandidate(payload?.message?.body)
                    || operationalAudioCandidate(payload?.message))));
}

async function audioMediaPayload(message, messageId) {
    if (!operationalAudioCandidate(message)) {
        return {};
    }

    try {
        let downloadable = message;

        if (typeof downloadable?.downloadMedia !== 'function'
            && messageId
            && !String(messageId).startsWith('fallback_')) {
            downloadable = await withTimeout(
                client.getMessageById(messageId),
                15000,
                `Recuperación del audio ${messageId}`
            );
        }

        if (!downloadable || typeof downloadable.downloadMedia !== 'function') {
            throw new Error('WhatsApp Web no entregó una referencia descargable');
        }

        const media = await withTimeout(
            downloadable.downloadMedia(),
            30000,
            `Descarga del audio ${messageId}`
        );
        const data = String(media?.data || '');

        if (!data) {
            throw new Error('WhatsApp Web devolvió el audio vacío');
        }

        const bytes = Buffer.byteLength(data, 'base64');

        if (bytes > audioMaxBytes) {
            throw new Error(`Audio de ${bytes} bytes excede el máximo de ${audioMaxBytes}`);
        }

        return {
            media_base64: data,
            media_mimetype: String(media?.mimetype || 'audio/ogg'),
            media_filename: media?.filename ? String(media.filename) : null,
        };
    } catch (error) {
        console.error(`No se pudo descargar el audio ${messageId}: ${errorDetails(error)}`);
        return {};
    }
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

    const messageId = liveMessageId(message, groupId);
    rememberMessageId(messageId);

    const author = await resolveAuthorIdentity(message.author);
    const isOperationalAudio = operationalAudioCandidate(message);

    if (!author.allowed
        && !(allowOperationalAuthors
            && (operationalMessageCandidate(message.body) || isOperationalAudio))) {
        console.log(`Mensaje ignorado por remitente no autorizado: ${author.originalId || '(sin autor)'}`);
        return;
    }

    if (author.authorId !== author.originalId) {
        console.log(`Remitente LID resuelto: ${author.originalId} -> ${author.authorId}`);
    }

    const chat = message.__polledGroupName
        ? { name: message.__polledGroupName }
        : await withTimeout(message.getChat(), 15000, `Consulta del chat ${groupId}`);
    let quotedMessageId = String(message.__polledQuotedMessageId || '').trim() || null;

    if (!quotedMessageId && message.hasQuotedMsg) {
        try {
            const quotedMessage = await message.getQuotedMessage();
            quotedMessageId = liveMessageId(quotedMessage, groupId);
        } catch (error) {
            console.error(`No se pudo resolver el mensaje citado: ${errorDetails(error)}`);
        }
    }
    const timestamp = Number(message.timestamp);
    const mediaPayload = await audioMediaPayload(message, messageId);
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
            ...mediaPayload,
            timestamp: Number.isInteger(timestamp) && timestamp > 0
                ? timestamp
                : Math.floor(Date.now() / 1000),
        },
    };

    const response = await deliverOrSpool('/whatsapp-web-reader/messages', payload);

    if (response?.stored === false) {
        console.log(`[${chat.name || groupId}] mensaje ignorado por Laravel (${response.reason || 'filtro'}): ${messageId}`);
    } else if (response) {
        const reason = response.recommendation_reason
            ? ` (${response.recommendation_reason})`
            : '';
        console.log(
            `[${chat.name || groupId}] mensaje almacenado: ${messageId}; recomendación: ${response.recommendation_status || 'sin estado'}${reason}; reacción: ${response.response_time_status || 'sin estado'}; transcripción: ${response.transcription_status || 'no aplica'}`
        );
    } else {
        console.log(`[${chat.name || groupId}] mensaje pendiente de reintento: ${messageId}`);
    }
}

async function fetchRecentMessageSnapshots(groupId) {
    return withTimeout(
        client.pupPage.evaluate((targetGroupId, limit) => {
            const chats = window.require('WAWebCollections').Chat;
            const chat = chats.get(targetGroupId);

            if (!chat || !chat.msgs?.getModelsArray) {
                throw new Error(`El grupo ${targetGroupId} no está disponible en la colección de WhatsApp Web`);
            }

            const groupName = chat.formattedTitle || chat.name || null;

            return chat.msgs.getModelsArray().slice(-limit).map((message) => ({
                from: targetGroupId,
                author: message.author?._serialized
                    || (typeof message.author === 'string' ? message.author : null)
                    || message.id?.participant?._serialized
                    || (typeof message.id?.participant === 'string' ? message.id.participant : null)
                    || null,
                body: message.body || message.caption || null,
                type: message.type || 'unknown',
                hasMedia: Boolean(message.mediaData || message.isMedia),
                timestamp: Number(message.t) || Math.floor(Date.now() / 1000),
                id: {
                    _serialized: message.id?._serialized || null,
                    id: message.id?.id || null,
                    fromMe: Boolean(message.id?.fromMe),
                },
                __polledGroupName: groupName,
                __polledQuotedMessageId: message.quotedStanzaID?._serialized || null,
            }));
        }, groupId, 50),
        20000,
        `Sondeo de mensajes del grupo ${groupId}`
    );
}

async function pollWatchedGroups() {
    if (!readerReady || fatalExitScheduled || pollInProgress) {
        return;
    }

    pollInProgress = true;
    const minimumTimestamp = Math.floor(Date.now() / 1000) - (pollBackfillMinutes * 60);
    let recovered = 0;
    let newestMessageTimestamp = 0;

    try {
        const state = await withTimeout(client.getState(), 10000, 'Consulta del estado de WhatsApp Web');

        if (state !== 'CONNECTED') {
            throw new Error(`Estado de WhatsApp Web no saludable: ${state || 'desconocido'}`);
        }

        for (const groupId of watchedGroupIds) {
            const messages = await fetchRecentMessageSnapshots(groupId);

            for (const message of [...messages].sort(
                (left, right) => Number(left.timestamp) - Number(right.timestamp)
            )) {
                newestMessageTimestamp = Math.max(
                    newestMessageTimestamp,
                    Number(message.timestamp) || 0
                );
                const messageId = liveMessageId(message, groupId);

                if (seenMessageIds.has(messageId)) {
                    continue;
                }

                if ((Number(message.timestamp) || 0) < minimumTimestamp) {
                    rememberMessageId(messageId);
                    continue;
                }

                await withTimeout(
                    processIncomingMessage(message),
                    45000,
                    `Recuperación del mensaje ${messageId}`
                );
                recovered += 1;
            }
        }

        consecutiveHealthFailures = 0;

        if (recovered > 0) {
            console.log(`Sondeo recuperó ${recovered} mensaje(s) que no llegaron por el evento en vivo.`);
        }

        if (Date.now() - lastHealthLogAt >= 3600000) {
            const latest = newestMessageTimestamp > 0
                ? new Date(newestMessageTimestamp * 1000).toISOString()
                : 'sin mensajes en memoria';
            console.log(`Sondeo de salud correcto; último mensaje visible: ${latest}.`);
            lastHealthLogAt = Date.now();
        }
    } catch (error) {
        consecutiveHealthFailures += 1;
        console.error(
            `Sondeo de salud falló (${consecutiveHealthFailures}/${healthFailureLimit}): ${errorDetails(error)}`
        );

        if (consecutiveHealthFailures >= healthFailureLimit) {
            scheduleFatalExit('WhatsApp Web no respondió a los sondeos de salud');
        }
    } finally {
        pollInProgress = false;
    }
}

function queueSpoolFlush() {
    if (!readerReady || fatalExitScheduled) {
        return;
    }

    eventChain = eventChain
        .then(() => withTimeout(flushSpool(), 45000, 'Reintento de eventos pendientes'))
        .catch((error) => console.error(`Error reintentando eventos pendientes: ${errorDetails(error)}`));
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
    scheduleFatalExit('falló la autenticación de WhatsApp Web');
});

client.on('ready', () => {
    readerReady = true;
    consecutiveHealthFailures = 0;
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
        .then(() => withTimeout(processIncomingMessage(message), 45000, 'Procesamiento de mensaje en vivo'))
        .catch((error) => console.error(`Error al procesar mensaje: ${errorDetails(error)}`));
});

client.on('disconnected', (reason) => {
    console.error(`WhatsApp se desconectó: ${reason}`);
    scheduleFatalExit(`WhatsApp se desconectó (${reason})`);
});

process.on('SIGINT', async () => {
    console.log('Cerrando lector...');
    await client.destroy();
    process.exit(0);
});
process.on('uncaughtException', (error) => {
    console.error(`Excepción no controlada: ${errorDetails(error)}`);
    scheduleFatalExit('excepción no controlada');
});
process.on('unhandledRejection', (error) => {
    console.error(`Promesa rechazada sin manejar: ${errorDetails(error)}`);
    scheduleFatalExit('promesa rechazada sin manejar');
});

setInterval(pollWatchedGroups, pollIntervalMs);
setInterval(queueSpoolFlush, spoolRetryIntervalMs);

console.log('Iniciando lector de WhatsApp en modo de sólo lectura...');
client.initialize().catch((error) => {
    console.error(`No se pudo iniciar WhatsApp: ${error.message}`);
    process.exit(1);
});
