import { getDbPool } from '../services/Database.js';

/**
 * Codes issued with the new short format: 2–3 letter restaurant prefix followed
 * immediately by 4–6 digits. E.g. `FZ1234`, `ORD5821`, `FB10042`.
 *
 * Must stay in step with `Order::generateTrackingCode()` (app/Models/Order.php).
 */
const CURRENT_CODE = /^[A-Z]{2,3}\d{4,6}$/;

/**
 * Shapes issued *before* this change. Real orders still carry them, so they stay
 * recognised — the DB lookup below matches on the literal string either way.
 */
const LEGACY_CODES = [
    /^[A-Z]{1,5}-[0-9A-HJKMNP-TV-Z]{16}$/, // F-FBPJBPM1WJY6WYS5 (previous long format)
    /^[A-Z]{1,5}-\d{4}-\d{2,6}$/,           // JC-2026-00042
    /^[A-Z]{2,4}-\d{3,6}$/,                 // FEZ-001
];

/**
 * First name only — the same rule the public web page applies
 * (Order::getRiderDisplayNameAttribute).
 */
function riderDisplayName(riderName) {
    const name = String(riderName || '').trim();
    return name === '' ? null : name.split(/\s+/)[0];
}

/**
 * A tracking code is a bearer token: anyone it is forwarded to can look the order
 * up, from any WhatsApp number. So the rider's number is published only while the
 * order is actually in transit, matching Order::showsRiderContact().
 */
function showsRiderContact(order) {
    return order.status === 'out_for_delivery' && String(order.rider_phone || '').trim() !== '';
}

/**
 * TrackingHandler — detects and resolves order tracking codes.
 *
 * Enumeration is bounded by the per-customer RateLimiter in MessageRouter
 * (12 messages/minute), which is what makes guessing at the 80-bit code space
 * pointless rather than merely slow.
 */
export class TrackingHandler {

    /**
     * Static check — is this message an order tracking code?
     */
    static isTrackingCode(text) {
        const clean = String(text || '').trim().toUpperCase();

        // Anything typed with the old explicit prefix is a tracking attempt,
        // whatever follows it.
        if (clean.startsWith('TRK-')) return true;

        if (CURRENT_CODE.test(clean)) return true;

        return LEGACY_CODES.some(pattern => pattern.test(clean));
    }

    async handle(msg, text, customerPhone) {
        const trackingCode = text.trim().toUpperCase();
        console.log(`🔍 Tracking lookup: ${trackingCode} for ${customerPhone}`);

        let order = null;

        try {
            const db = getDbPool();
            const [rows] = await db.query(
                `SELECT o.*, r.name as restaurant_name
                 FROM orders o
                 LEFT JOIN restaurants r ON o.restaurant_id = r.id
                 WHERE o.tracking_code = ?
                 LIMIT 1`,
                [trackingCode]
            );

            order = rows[0] || null;
        } catch (dbErr) {
            // There is no HTTP fallback: routes/api.php is deliberately not
            // registered in bootstrap/app.php, so the old
            // `GET {LARAVEL_API}/orders/track/{code}` call could only ever 404.
            // Say so plainly instead of reporting a real order as missing.
            console.error('❌ Tracking lookup failed (database):', dbErr.message);
            await msg.reply(
                `⚠️ Couldn't check your order right now. Please try again in a moment.`
            );
            return;
        }

        if (!order) {
            await msg.reply(
                `❌ No order found with tracking code *${trackingCode}*.\n` +
                `Please double-check your code and try again!`
            );
            return;
        }

        await msg.reply(this.formatReplyFromDb(order));
    }

    formatReplyFromDb(order) {
        // Customer-facing simplified status
        let statusLabel = '🕐 Pending Confirmation';
        let statusMsg = 'Your order has been received and is waiting for confirmation from our team.';

        if (order.status === 'confirmed' || order.status === 'preparing') {
            statusLabel = '👨‍🍳 Preparing in Kitchen';
            statusMsg = 'Our kitchen is preparing your order fresh right now!';
        } else if (order.status === 'out_for_delivery') {
            statusLabel = '🛵 Dispatched & On The Way';
            statusMsg = 'Your order has been dispatched and is on its way with our rider!';
        } else if (order.status === 'delivered') {
            statusLabel = '🎉 Delivered';
            statusMsg = 'Your order has been delivered. Enjoy your meal!';
        } else if (order.status === 'cancelled') {
            statusLabel = '❌ Cancelled';
            statusMsg = 'This order was cancelled. Please contact us for assistance.';
        }

        let riderSection = '';
        const riderName = riderDisplayName(order.rider_name);

        if (riderName || showsRiderContact(order)) {
            const phone = showsRiderContact(order) ? ` (${order.rider_phone})` : '';
            riderSection = `\n🛵 *Rider:* ${riderName || 'Assigned Rider'}${phone}`;
            if (order.estimated_minutes) {
                riderSection += `\n⏱️ *Estimated Delivery:* ~${order.estimated_minutes} mins`;
            }
            riderSection += '\n';
        }

        const appUrl = process.env.APP_URL || 'http://localhost:8000';
        const webLink = `\n📍 *Live Web Tracking:* ${appUrl}/track/${order.tracking_code}\n`;

        return (
            `📦 *Order Status — ${order.restaurant_name || 'Restaurant'}*\n\n` +
            `🔖 Tracking Code: *${order.tracking_code}*\n` +
            `📊 Status: *${statusLabel}*\n\n` +
            `${statusMsg}\n` +
            riderSection +
            `💰 Total: Rs. ${Number(order.total || 0).toLocaleString()}\n` +
            webLink
        );
    }
}
