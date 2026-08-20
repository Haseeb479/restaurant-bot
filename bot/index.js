import whatsappWebPkg from 'whatsapp-web.js';
import qrcodeTerminal from 'qrcode-terminal';
import QRCode from 'qrcode';
import dotenv from 'dotenv';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import { MessageRouter } from './src/handlers/MessageRouter.js';
import { InternalServer } from './src/server/InternalServer.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

// Load bot-specific env (.env.llm in the restaurant-bot root)
dotenv.config({ path: path.resolve(__dirname, '../.env.llm') });
dotenv.config({ path: path.resolve(__dirname, '../.env') });

// ── Global Handlers for Transient Puppeteer Errors ─────────────────────────────
process.on('unhandledRejection', (reason) => {
    const msg = String(reason?.message || reason);
    if (msg.includes('Target closed') || msg.includes('detached') || msg.includes('ENOTEMPTY')) {
        // Suppress expected shutdown/reset teardown warnings
        return;
    }
    console.warn('⚠️ Warning (handled):', msg);
});

process.on('uncaughtException', (err) => {
    const msg = String(err?.message || err);
    if (msg.includes('Target closed') || msg.includes('detached') || msg.includes('ENOTEMPTY')) {
        return;
    }
    console.error('❌ Error (handled):', msg);
});

const { Client, LocalAuth } = whatsappWebPkg;

const CLIENT_ID     = process.argv[2] || 'waiter-bot-v2';
const INTERNAL_PORT = process.argv[3] || process.env.BOT_INTERNAL_PORT || 3000;
const SESSION_DIR   = path.resolve(process.cwd(), '.wwebjs_auth', `session-${CLIENT_ID}`);

let client = null;
let router = null;
let isInitializing = false;
const internalServer = new InternalServer(null, INTERNAL_PORT);

// Start internal HTTP server early so dashboard can fetch QR immediately
internalServer.start();

function cleanSessionDirectory() {
    try {
        if (fs.existsSync(SESSION_DIR)) {
            fs.rmSync(SESSION_DIR, { recursive: true, force: true, maxRetries: 3, retryDelay: 500 });
            console.log('🧹 Cleaned up old session cache');
        }
    } catch (e) {
        // Ignored — will be overwritten on next session start
    }
}

async function initBot(cleanSession = false) {
    if (isInitializing) return;
    isInitializing = true;

    console.log('\n🚀 Initializing WhatsApp Client (Session: ' + CLIENT_ID + ')...');

    if (client) {
        try {
            await client.destroy().catch(() => {});
        } catch (e) {}
        client = null;
        // Small delay to allow OS to release Chromium file locks
        await new Promise(r => setTimeout(r, 1000));
    }

    if (cleanSession) {
        cleanSessionDirectory();
    }

    try {
        client = new Client({
            authStrategy: new LocalAuth({ clientId: CLIENT_ID }),
            puppeteer: {
                headless: true,
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-gpu',
                    '--disable-dev-shm-usage',
                    '--disable-extensions',
                    '--no-zygote',
                    '--single-process',
                ],
            },
        });

        router = new MessageRouter(client);
        internalServer.setClient(client);
        if (router.chat?.restaurants) {
            internalServer.setRestaurantService(router.chat.restaurants);
        }

        // ─── Events ───────────────────────────────────────────────────────────
        client.on('qr', async (qr) => {
            isInitializing = false;
            // 1. Terminal display for CLI debugging
            qrcodeTerminal.generate(qr, { small: true });
            console.log('\n📱 Scan QR code to connect!\n');

            // 2. Generate Base64 Data URL for web dashboard display
            try {
                const qrDataUrl = await QRCode.toDataURL(qr, {
                    margin: 2,
                    scale:  8,
                    color: { dark: '#0e0e10', light: '#ffffff' },
                });
                internalServer.setQr(qr, qrDataUrl);
            } catch (err) {
                console.error('❌ QR Data URL generation error:', err.message);
            }
        });

        client.on('ready', async () => {
            isInitializing = false;
            // Small delay to ensure client info is fully hydrated
            await new Promise(r => setTimeout(r, 2000));

            if (!client.info?.wid) {
                console.error('❌ Client ready but info not available. Reconnecting...');
                setTimeout(() => initBot(false), 3000);
                return;
            }

            const botNumber = client.info.wid.user;
            internalServer.setReady(botNumber);

            console.log('');
            console.log('✅ ========================================');
            console.log('✅  WhatsApp Restaurant Bot is LIVE!');
            console.log('✅ ========================================');
            console.log(`📱 Bot Number  : ${botNumber}`);
            console.log(`🤖 Session ID  : ${CLIENT_ID}`);
            console.log(`🌐 Internal API: http://localhost:${INTERNAL_PORT}`);
            console.log('');

            // Preload restaurant & menu data immediately
            await router.chat.restaurants.preload(botNumber);
        });

        client.on('message', (msg) => router.handle(msg));

        client.on('disconnected', (reason) => {
            isInitializing = false;
            console.log('⚠️  Disconnected / Logged out:', reason);
            internalServer.setDisconnected(reason);
            console.log('🔄 Cleaning session & re-generating fresh QR code...');
            setTimeout(() => initBot(true), 2000);
        });

        client.on('auth_failure', (msg) => {
            isInitializing = false;
            console.error('❌ Auth failed:', msg);
            internalServer.setDisconnected(msg);
            console.log('🔄 Cleaning session & re-generating fresh QR code...');
            setTimeout(() => initBot(true), 2000);
        });

        await client.initialize();
    } catch (err) {
        isInitializing = false;
        console.error('❌ Client initialization error:', err.message);
        setTimeout(() => initBot(true), 4000);
    }
}

// Allow dashboard to trigger re-login / new QR code
internalServer.setRestartHandler(() => {
    console.log('🔄 Manual restart requested via dashboard. Re-generating fresh QR...');
    initBot(true);
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
initBot(false);
