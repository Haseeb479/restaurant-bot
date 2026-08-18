import { TrackingHandler } from './TrackingHandler.js';
import { EscalationHandler } from './EscalationHandler.js';
import { ChatHandler } from './ChatHandler.js';
import { rateLimiter } from '../services/RateLimiter.js';
import { Logger } from '../services/Logger.js';

/**
 * MessageRouter — single entry point for every incoming WhatsApp message.
 *
 * Routing order (first match wins):
 *  1. Spam & Rate Limiting check
 *  2. Tracking code    → TrackingHandler
 *  3. Escalation words → EscalationHandler
 *  4. Everything else  → ChatHandler (AI)
 */
export class MessageRouter {
    constructor(client) {
        this.client     = client;
        this.tracking   = new TrackingHandler();
        this.escalation = new EscalationHandler(client);
        this.chat       = new ChatHandler(client);
    }

    async handle(msg) {
        // ── Skip non-customer messages ─────────────────────────────────────────
        if (msg.from.includes('@g.us'))      return; // group chats
        if (msg.from === 'status@broadcast') return; // broadcast
        if (!msg.body?.trim())              return; // empty message

        const customerPhone = msg.from.split('@')[0];
        const botNumber     = this.client.info?.wid?.user;
        const text          = msg.body.trim();

        console.log(`\n📩 [${customerPhone}] → [${botNumber || 'BOT'}]: ${text}`);

        // ── Rate Limiting & Spam Check ─────────────────────────────────────────
        const rateCheck = rateLimiter.check(customerPhone);
        if (!rateCheck.allowed) {
            if (rateCheck.reason === 'burst') {
                console.warn(`⏳ Suppressed rapid burst message from ${customerPhone}`);
                return; // Silently drop rapid sub-second bursts
            }

            if (rateLimiter.shouldSendWarning(customerPhone)) {
                console.warn(`⚠️ Rate limit triggered for ${customerPhone} (wait ${rateCheck.waitSecs}s)`);
                await msg.reply(
                    "⚠️ Bohat zyada messages aa rahe hain! Barah-e-karam thora intezar farmayein 😊\n" +
                    `Please wait a few seconds before sending another message.`
                ).catch(() => {});
            }
            return;
        }

        // Log incoming message
        Logger.info('Incoming message', { customerPhone, botNumber, text });

        try {
            // ── 1. Tracking code ───────────────────────────────────────────────
            if (TrackingHandler.isTrackingCode(text)) {
                await this.tracking.handle(msg, text, customerPhone);
                Logger.info('Handled as tracking', { customerPhone, text });
                return;
            }

            // ── 2. Escalation / human request ─────────────────────────────────
            if (EscalationHandler.isEscalationRequest(text)) {
                await this.escalation.handle(msg, customerPhone, botNumber);
                Logger.info('Handled as escalation', { customerPhone, text });
                return;
            }

            // ── 3. Normal AI chat ──────────────────────────────────────────────
            await this.chat.handle(msg, customerPhone, botNumber, text);

        } catch (err) {
            console.error('❌ MessageRouter error:', err.message);
            console.error('   Stack:', err.stack);
            Logger.error('MessageRouter exception', { customerPhone, error: err.message, stack: err.stack });

            await msg.reply(
                "Sorry, I had a small hiccup! Try again in a moment 😊"
            ).catch(() => {});
        }
    }
}
