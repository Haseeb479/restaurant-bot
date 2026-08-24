import { TrackingHandler } from './TrackingHandler.js';
import { EscalationHandler } from './EscalationHandler.js';
import { ChatHandler } from './ChatHandler.js';
import { rateLimiter } from '../services/RateLimiter.js';
import { sessionManager } from '../services/SessionManager.js';
import { Logger } from '../services/Logger.js';

/**
 * Owner "resume" command — how a human hands a chat back to the bot after taking
 * over. Optional trailing digits pick which customer, when several are paused.
 */
function parseReleaseCommand(text) {
    const m = text.trim().match(/^(resume|release|done|resolved|handled|bot\s*on)\b\s*(.*)$/i);
    if (!m) return { isRelease: false, selector: '' };
    return { isRelease: true, selector: (m[2] || '').replace(/[^0-9]/g, '') };
}

/**
 * MessageRouter — single entry point for every incoming WhatsApp message.
 *
 * Routing order (first match wins):
 *  1. Spam & rate-limit check
 *  2. Owner "resume" command   → release a human handoff
 *  3. Tracking code            → TrackingHandler (works even during a handoff)
 *  4. Human handoff active     → mute the AI (a person is handling this chat)
 *  5. Escalation words         → EscalationHandler (starts a handoff)
 *  6. Everything else          → ChatHandler (AI)
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
            // ── 1. Owner resuming a paused chat ────────────────────────────────
            // Checked before muting, since the owner may themselves be flagged as
            // an in-handoff party, and before tracking so "resume 1234" isn't
            // mistaken for anything else.
            if (await this._tryOwnerRelease(msg, botNumber, customerPhone, text)) {
                return;
            }

            // ── 2. Tracking code ───────────────────────────────────────────────
            // Deliberately allowed during a handoff: it's a deterministic lookup,
            // not the AI, and a customer checking status doesn't talk over a human.
            if (TrackingHandler.isTrackingCode(text)) {
                await this.tracking.handle(msg, text, customerPhone);
                Logger.info('Handled as tracking', { customerPhone, text });
                return;
            }

            // ── 3. Human handoff active → mute the AI ──────────────────────────
            if (sessionManager.isHandoffActive(botNumber, customerPhone)) {
                console.log(`🔇 Muted (human handling): ${customerPhone}`);
                Logger.info('Muted during human handoff', { customerPhone, botNumber });
                return;
            }

            // ── 4. Escalation / human request ─────────────────────────────────
            if (EscalationHandler.isEscalationRequest(text)) {
                await this.escalation.handle(msg, customerPhone, botNumber);
                Logger.info('Handled as escalation', { customerPhone, text });
                return;
            }

            // ── 5. Normal AI chat ──────────────────────────────────────────────
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

    /**
     * If the sender is an owner/manager with paused chats and this message is a
     * "resume" command, release the handoff so the bot answers that customer
     * again. Returns true when it consumed the message.
     */
    async _tryOwnerRelease(msg, botNumber, senderPhone, text) {
        const ownerHandoffs = sessionManager.findOwnerHandoffs(botNumber, senderPhone);
        if (ownerHandoffs.length === 0) return false; // not an owner with paused chats

        const { isRelease, selector } = parseReleaseCommand(text);
        if (!isRelease) return false;

        let target = null;
        if (selector.length >= 3) {
            target = ownerHandoffs.find(h => h.customerPhone === selector || h.customerPhone.endsWith(selector));
        } else if (ownerHandoffs.length === 1) {
            target = ownerHandoffs[0];
        }

        if (!target) {
            // More than one paused and no (matching) selector — ask which.
            const list = ownerHandoffs.map(h => `• ${h.customerPhone}  → *resume ${h.customerPhone.slice(-4)}*`).join('\n');
            await msg.reply(
                `You have ${ownerHandoffs.length} chats paused for the bot:\n${list}`
            ).catch(() => {});
            return true;
        }

        sessionManager.endHandoff(botNumber, target.customerPhone);
        await sessionManager.clearHandoffPersist(target.restaurantId, target.customerPhone).catch(() => {});
        await msg.reply(`✅ Bot resumed for ${target.customerPhone} — it will reply to them normally again.`).catch(() => {});
        Logger.info('Human handoff released by owner', { botNumber, customer: target.customerPhone });
        console.log(`🤝 Handoff released by owner for ${target.customerPhone}`);
        return true;
    }
}
