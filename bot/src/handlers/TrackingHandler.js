import axios from 'axios';
import { getDbPool } from '../services/Database.js';

const LARAVEL_API = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';

/**
 * TrackingHandler — detects and resolves order tracking codes.
 * Supports: TRK-FEZ-1234, JC-2026-00042, FEZ-2026-001, etc.
 */
export class TrackingHandler {

    /**
     * Static check — is this message an order tracking code?
     */
    static isTrackingCode(text) {
        const clean = text.trim().toUpperCase();
        if (clean.startsWith('TRK-')) return true;
        if (/^[A-Z]{1,5}-\d{4}-\d{2,6}$/.test(clean)) return true;
        if (/^[A-Z]{2,4}-\d{3,6}$/.test(clean)) return true;
        return false;
    }

    async handle(msg, text, customerPhone) {
        const trackingCode = text.trim().toUpperCase();
        console.log(`🔍 Tracking lookup: ${trackingCode} for ${customerPhone}`);

        // 1. Direct MySQL Lookup for 0ms speed
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

            if (rows.length > 0) {
                const order = rows[0];
                await msg.reply(this.formatReplyFromDb(order));
                return;
            }
        } catch (dbErr) {
            console.warn('⚠️ Direct DB tracking lookup fallback to API:', dbErr.message);
        }

        // 2. HTTP Fallback
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
                    `Please double-check your code and try again!`
                );
            } else {
                console.error('⚠️ Tracking lookup error:', err.message);
                await msg.reply(
                    `⚠️ Couldn't check your order right now. Please try again in a moment.`
                );
            }
        }
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
        if (order.rider_name || order.rider_phone) {
            const name  = order.rider_name || 'Assigned Rider';
            const phone = order.rider_phone ? ` (${order.rider_phone})` : '';
            riderSection = `\n🛵 *Rider:* ${name}${phone}`;
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
