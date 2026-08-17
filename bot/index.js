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

const { Client, LocalAuth } = whatsappWebPkg;

const CLIENT_ID     = process.argv[2] || 'waiter-bot-v2';
const INTERNAL_PORT = process.argv[3] || process.env.BOT_INTERNAL_PORT || 3000;

let client = null;
let router = null;
const internalServer = new InternalServer(null, INTERNAL_PORT);

// Start internal HTTP server early so dashboard can fetch QR immediately
internalServer.start();

function initBot() {
    console.log('\n🚀 Initializing WhatsApp Client (Session: ' + CLIENT_ID + ')...');

    if (client) {
        try {
            client.destroy().catch(() => {});
        } catch (e) {}
    }

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

    // ─── Events ───────────────────────────────────────────────────────────────
    client.on('qr', async (qr) => {
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
        // Small delay to ensure client info is fully hydrated
        await new Promise(r => setTimeout(r, 2000));

        if (!client.info?.wid) {
            console.error('❌ Client ready but info not available. Reconnecting...');
            setTimeout(initBot, 3000);
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
    });

    client.on('message', (msg) => router.handle(msg));

    client.on('disconnected', (reason) => {
        console.log('⚠️  Disconnected / Logged out:', reason);
        internalServer.setDisconnected(reason);
        console.log('🔄 Re-generating fresh QR code for login...');
        setTimeout(initBot, 3000);
    });

    client.on('auth_failure', (msg) => {
        console.error('❌ Auth failed:', msg);
        internalServer.setDisconnected(msg);
        console.log('🔄 Re-generating fresh QR code for login...');
        setTimeout(initBot, 3000);
    });

    client.initialize().catch(err => {
        console.error('❌ Client initialization error:', err.message);
        setTimeout(initBot, 5000);
    });
}

// Allow dashboard to trigger re-login / new QR code
internalServer.setRestartHandler(() => {
    console.log('🔄 Manual restart requested via dashboard. Re-generating QR...');
    initBot();
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
initBot();
