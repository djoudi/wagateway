'use strict';

const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const path   = require('path');
const { logger } = require('../utils/logger');
const { notifyLaravel } = require('../utils/notify');

const sessions    = new Map();
const restartLock = new Set(); // Prevent double-restart

async function createSession(sessionId, retryCount = 0) {
    if (sessions.has(sessionId)) {
        logger.warn(`Session ${sessionId} already exists`);
        return sessions.get(sessionId);
    }

    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: sessionId,
            dataPath:  process.env.SESSION_PATH || path.resolve('/app/sessions'),
        }),
        puppeteer: {
            headless: true,
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu',
                '--single-process',
                '--no-zygote',
            ],
        },
        restartOnAuthFail: true,
    });

    // ── QR ──────────────────────────────────────────────────────────────────
    client.on('qr', async (qr) => {
        try {
            const qrDataUrl = await qrcode.toDataURL(qr);
            logger.info(`QR generated for ${sessionId}`);
            await notifyLaravel('qr', { session_id: sessionId, qr: qrDataUrl });
        } catch (err) {
            logger.error(`QR generation failed: ${err.message}`);
        }
    });

    // ── Ready ────────────────────────────────────────────────────────────────
    client.on('ready', async () => {
        const info = client.info;
        logger.info(`Session ready: ${sessionId} (${info?.wid?.user})`);
        sessions.set(sessionId, client);
        await notifyLaravel('ready', {
            session_id:   sessionId,
            phone:        info?.wid?.user,
            name:         info?.pushname,
        });
    });

    // ── Disconnected + auto-restart ──────────────────────────────────────────
    client.on('disconnected', async (reason) => {
        logger.warn(`Session disconnected: ${sessionId} — ${reason}`);
        sessions.delete(sessionId);

        await notifyLaravel('disconnected', { session_id: sessionId, reason });

        // Auto-restart for recoverable reasons (not LOGOUT or banned)
        const noRestart = ['LOGOUT', 'CONFLICT'];
        if (!noRestart.includes(reason) && !restartLock.has(sessionId) && retryCount < 3) {
            restartLock.add(sessionId);
            logger.info(`Auto-restarting ${sessionId} in 10s (attempt ${retryCount + 1}/3)`);
            setTimeout(async () => {
                restartLock.delete(sessionId);
                await createSession(sessionId, retryCount + 1);
            }, 10_000);
        }
    });

    // ── Auth failure ─────────────────────────────────────────────────────────
    client.on('auth_failure', async (msg) => {
        logger.error(`Auth failure for ${sessionId}: ${msg}`);
        sessions.delete(sessionId);
        await notifyLaravel('disconnected', { session_id: sessionId, reason: 'AUTH_FAILURE' });
    });

    // ── Message ACK ──────────────────────────────────────────────────────────
    client.on('message_ack', async (msg, ack) => {
        try {
            await notifyLaravel('message_ack', {
                session_id: sessionId,
                id:  msg.id._serialised,
                ack, // 1=sent 2=delivered 3=read -1=error
            });
        } catch (err) {
            logger.error(`message_ack notify failed: ${err.message}`);
        }
    });

    // ── Incoming messages ────────────────────────────────────────────────────
    client.on('message', async (msg) => {
        // Skip groups and broadcast
        if (msg.from.includes('@g.us') || msg.from === 'status@broadcast') return;

        try {
            await notifyLaravel('message_received', {
                session_id: sessionId,
                id:        msg.id._serialised,
                from:      msg.from.replace('@c.us', ''),
                body:      msg.body,
                type:      msg.hasMedia ? msg.type : 'text',
                timestamp: msg.timestamp,
            });
        } catch (err) {
            logger.error(`Inbound notify failed: ${err.message}`);
        }
    });

    // ── Change state ─────────────────────────────────────────────────────────
    client.on('change_state', (state) => {
        logger.debug(`Session ${sessionId} state: ${state}`);
    });

    sessions.set(sessionId, client);

    try {
        await client.initialize();
    } catch (err) {
        logger.error(`Session init failed for ${sessionId}: ${err.message}`);
        sessions.delete(sessionId);
        throw err;
    }

    return client;
}

async function getSession(sessionId) {
    return sessions.get(sessionId) || null;
}

async function terminateSession(sessionId) {
    const client = sessions.get(sessionId);
    if (client) {
        try {
            await client.destroy();
        } catch (err) {
            logger.warn(`Destroy error for ${sessionId}: ${err.message}`);
        } finally {
            sessions.delete(sessionId);
            logger.info(`Session terminated: ${sessionId}`);
        }
    }
}

async function getSessionStatus(sessionId) {
    const client = sessions.get(sessionId);
    if (!client)   return 'disconnected';
    if (client.info) return 'connected';
    return 'connecting';
}

async function restoreAll() {
    logger.info('Session store initialised');
    // Sessions are restored automatically by LocalAuth on createSession call
}

// Health: return active session count
function activeCount() { return sessions.size; }

module.exports = { createSession, getSession, terminateSession, getSessionStatus, restoreAll, activeCount, sessions };
