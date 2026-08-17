import axios from 'axios';

const LARAVEL_API = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';

// Tracking code format: XX-2026-00042 (1-3 uppercase letters, 4-digit year, 3-6 digit number)
const TRACKING_REGEX = /^[A-Z]{1,3}-\d{4}-\d{3,6}$/i;

/**
 * TrackingHandler — detects and resolves order tracking codes.
 * When a customer sends their tracking code, this fetches the status from Laravel
 * and replies with a formatted status message.
 */
export class TrackingHandler {

    /**
     * Static check — is this message a tracking code?
     */
    static isTrackingCode(text) {
        return TRACKING_REGEX.test(text.trim());
    }

    async handle(msg, text, customerPhone) {
        const trackingCode = text.trim().toUpperCase();
        console.log(`🔍 Tracking lookup: ${trackingCode} for ${customerPhone}`);

        try {
            const res = await axios.get(
                `${LARAVEL_API}/orders/track/${trackingCode}`,
                { timeout: 5000 }
            );
            await msg.reply(this.formatReply(res.data));

        } catch (err) {
            if (err.response?.status === 404) {
                await msg.reply(
                    `❌ No order found with tracking code *${trackingCode}*.\n` +
                    `Please check the code and try again.`
                );
            } else {
                console.error('⚠️  Tracking lookup error:', err.message);
                await msg.reply(
                    `⚠️ Couldn't check your order right now. Please try again in a moment.`
                );
            }
        }
    }

    formatReply(order) {
        let riderSection = '';
        if (order.rider_name || order.rider_phone) {
            const name  = order.rider_name || 'Assigned Rider';
            const phone = order.rider_phone ? ` (${order.rider_phone})` : '';
            riderSection = `\n🛵 *Rider:* ${name}${phone}\n`;
        }

        const webLink = order.tracking_url ? `\n📍 *Live Web Tracking:* ${order.tracking_url}\n` : '';

        return (
            `📦 *Order Status*\n\n` +
            `🔖 Tracking: *${order.tracking_code}*\n` +
            `📊 Status: *${order.status_label}*\n\n` +
            `${order.status_message}\n` +
            riderSection +
            `💰 Total: Rs. ${order.total}\n` +
            `🕐 Placed: ${order.placed_at}\n` +
            webLink
        );
    }
}
