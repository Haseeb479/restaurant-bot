import axios from 'axios';

const OWNER_PHONE          = process.env.OWNER_PHONE          || '';
const GOOGLE_SHEET_WEBHOOK = process.env.GOOGLE_SHEET_WEBHOOK || '';

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
        try {
            const chatId = `${phone.replace(/[^0-9]/g, '')}@c.us`;
            await this.client.sendMessage(chatId, message);
            console.log(`📤 Sent message to: ${phone}`);
        } catch (err) {
            console.log(`⚠️  Could not send to ${phone}: ${err.message}`);
        }
    }

    /**
     * Notify the restaurant owner/manager about a new confirmed order.
     * Priority: restaurant.manager_phone → restaurant.owner_phone → OWNER_PHONE env
     */
    async notifyOwner(customerPhone, session, trackingCode) {
        const restaurant  = session.restaurant;
        const targetPhone = restaurant?.manager_phone
            || restaurant?.owner_phone
            || OWNER_PHONE;

        if (!targetPhone && !GOOGLE_SHEET_WEBHOOK) return;

        // Build a brief chat summary (last 6 messages)
        const orderDetails = session.history
            .slice(-6)
            .map(h => `${h.role === 'user' ? '👤 Customer' : '🤖 Bot'}: ${h.content}`)
            .join('\n');

        const ownerMsg =
            `🔔 *NEW ORDER — ${restaurant?.name || 'Restaurant'}* 🔔\n\n` +
            `📱 *Customer:* ${customerPhone}\n` +
            `🔖 *Tracking:* ${trackingCode}\n\n` +
            `📝 *Chat Summary:*\n${orderDetails}\n\n` +
            `🛒 Dashboard: ${process.env.APP_URL || 'http://localhost'}`;

        // WhatsApp notification to owner/manager
        if (targetPhone) {
            await this.sendToPhone(targetPhone, ownerMsg);
            console.log(`📡 Owner notified (${restaurant?.name}): ${targetPhone}`);
        }

        // Optional: log order to Google Sheets via webhook
        if (GOOGLE_SHEET_WEBHOOK) {
            try {
                await axios.post(GOOGLE_SHEET_WEBHOOK, {
                    timestamp:  new Date().toISOString(),
                    customer:   customerPhone,
                    tracking:   trackingCode,
                    restaurant: restaurant?.name,
                    summary:    orderDetails,
                });
                console.log('📊 Order logged to Google Sheets');
            } catch (err) {
                console.log('⚠️  Google Sheets webhook error:', err.message);
            }
        }
    }
}
