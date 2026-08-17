import { TrackingHandler } from './TrackingHandler.js';
import { EscalationHandler } from './EscalationHandler.js';
import { ChatHandler } from './ChatHandler.js';

/**
 * MessageRouter — single entry point for every incoming WhatsApp message.
 *
 * Routing order (first match wins):
 *  1. Tracking code    → TrackingHandler
 *  2. Escalation words → EscalationHandler
 *  3. Everything else  → ChatHandler (AI)
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

        try {
            // ── 1. Tracking code ───────────────────────────────────────────────
            if (TrackingHandler.isTrackingCode(text)) {
                await this.tracking.handle(msg, text, customerPhone);
                return;
            }

            // ── 2. Escalation / human request ─────────────────────────────────
            if (EscalationHandler.isEscalationRequest(text)) {
                await this.escalation.handle(msg, customerPhone, botNumber);
                return;
            }

            // ── 3. Normal AI chat ──────────────────────────────────────────────
            await this.chat.handle(msg, customerPhone, botNumber, text);

        } catch (err) {
            console.error('❌ MessageRouter error:', err.message);
            console.error('   Stack:', err.stack);
            await msg.reply(
                "Sorry, I had a small hiccup! Try again in a moment 😊"
            ).catch(() => {});
        }
    }
}
