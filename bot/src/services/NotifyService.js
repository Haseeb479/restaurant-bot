import axios from 'axios';
import { sendWhatsAppText } from '../utils/WhatsAppSender.js';
import { validateWebhookUrl } from '../utils/WebhookUrlValidator.js';

const OWNER_PHONE = process.env.OWNER_PHONE || '';

/** How long to wait on the sheet webhook before giving up on it. */
const WEBHOOK_TIMEOUT_MS = 5000;

/**
 * Read at call time, not at import: the bot's dotenv load order has bitten us
 * before, and this value is also allowed to be absent.
 */
function envSheetWebhook() {
    return (process.env.GOOGLE_SHEET_WEBHOOK || '').trim();
}

/**
 * NotifyService — sends WhatsApp messages to owners/managers and customers.
 * Also supports optional Google Sheets order logging via webhook.
 */
export class NotifyService {
    constructor(client) {
        this.client = client;
    }

    /**
     * Send a raw WhatsApp message to any phone number.
     */
    async sendToPhone(phone, message) {
        return await sendWhatsAppText(this.client, phone, message);
    }

    /**
     * Notify the restaurant owner/manager about a new confirmed order.
     * Priority: restaurant.manager_phone → restaurant.owner_phone → OWNER_PHONE env
     * @returns {Promise<boolean>} true if the owner received the WhatsApp alert.
     */
    async notifyOwner(customerPhone, session, trackingCode) {
        const restaurant  = session.restaurant;
        const targetPhone = restaurant?.manager_phone
            || restaurant?.owner_phone
            || OWNER_PHONE;

        // Same precedence as OrderController::create — the restaurant's own
        // Settings value wins over the process-wide one. The bot used to read
        // only the env var, so a webhook typed into the dashboard never fired
        // for orders placed over WhatsApp (which is all of them).
        const sheetWebhook = (restaurant?.google_sheet_webhook || '').trim() || envSheetWebhook();

        if (!targetPhone && !sheetWebhook) return false;

        // Build a brief chat summary (last 6 messages)
        const orderDetails = session.history
            .slice(-6)
            .map(h => `${h.role === 'user' ? '👤 Customer' : '🤖 Bot'}: ${h.content}`)
            .join('\n');

        const customerName = (session.customerName || '').trim();

        const ownerMsg =
            `🔔 *NEW ORDER — ${restaurant?.name || 'Restaurant'}* 🔔\n\n` +
            (customerName ? `🙋 *Name:* ${customerName}\n` : '') +
            `📱 *Customer:* ${customerPhone}\n` +
            `🔖 *Tracking:* ${trackingCode}\n\n` +
            `📝 *Chat Summary:*\n${orderDetails}\n\n` +
            `🛒 Dashboard: ${process.env.APP_URL || 'http://localhost'}`;

        // WhatsApp notification to owner/manager
        let ownerNotified = false;
        if (targetPhone) {
            ownerNotified = !!(await this.sendToPhone(targetPhone, ownerMsg));
            console.log(ownerNotified
                ? `📡 Owner notified (${restaurant?.name}): ${targetPhone}`
                : `⚠️  Owner notify FAILED (${restaurant?.name}): ${targetPhone}`);
        }

        // Optional: log order to Google Sheets via webhook
        if (sheetWebhook) {
            // The URL comes from a restaurant owner or the env file, and this
            // payload carries the customer's phone number and a chat excerpt.
            // Re-check it at send time: a stored value may predate validation,
            // and the env var never passed through a form at all.
            const rejection = await validateWebhookUrl(sheetWebhook);

            if (rejection) {
                console.warn(`⚠️  Refused to post order to unsafe sheet webhook: ${rejection}`);
            } else {
                try {
                    await axios.post(sheetWebhook, {
                        timestamp:  new Date().toISOString(),
                        customer:   customerPhone,
                        tracking:   trackingCode,
                        restaurant: restaurant?.name,
                        summary:    orderDetails,
                    }, {
                        timeout: WEBHOOK_TIMEOUT_MS,
                        // Without this, a public URL that 302s to 127.0.0.1
                        // walks straight past the check above.
                        maxRedirects: 0,
                        // A Google Apps Script `/exec` endpoint answers a POST
                        // with a 302 to script.googleusercontent.com — the
                        // script has already run by then. Treat that as
                        // delivered instead of following it or logging a
                        // failure that never happened.
                        validateStatus: status => status >= 200 && status < 400,
                    });
                    console.log('📊 Order logged to Google Sheets');
                } catch (err) {
                    console.log('⚠️  Google Sheets webhook error:', err.message);
                }
            }
        }

        return ownerNotified;
    }
}
