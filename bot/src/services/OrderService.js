import axios from 'axios';
import { getDbPool } from './Database.js';

const LARAVEL_API = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';

/**
 * OrderService — parses order details from conversation and saves directly to MySQL database.
 */
export class OrderService {
    /**
     * Parse items, subtotal, delivery, grand total, and delivery address from chat history
     */
    parseOrderFromHistory(session) {
        const assistantMsgs = session.history
            .filter(h => h.role === 'assistant')
            .map(h => h.content)
            .join('\n');

        const userMsgs = session.history
            .filter(h => h.role === 'user')
            .map(h => h.content)
            .join(' ');

        // 1. Total extraction
        let total = 0;
        const totalMatch = assistantMsgs.match(/total\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i)
            || assistantMsgs.match(/rs\.?\s*([0-9,]+)\s*total/i)
            || assistantMsgs.match(/total\s*[:*–-]?\s*([0-9,]+)/i);

        if (totalMatch) {
            total = parseFloat(totalMatch[1].replace(/,/g, '')) || 0;
        }

        // 2. Subtotal extraction
        let subtotal = 0;
        const subtotalMatch = assistantMsgs.match(/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i);
        if (subtotalMatch) {
            subtotal = parseFloat(subtotalMatch[1].replace(/,/g, '')) || 0;
        }

        // 3. Delivery charge
        const deliveryCharge = parseFloat(session.restaurant?.delivery_charge || 0);

        if (total > 0 && subtotal === 0) {
            subtotal = Math.max(0, total - deliveryCharge);
        } else if (subtotal > 0 && total === 0) {
            total = subtotal + deliveryCharge;
        }

        // 4. Delivery address extraction
        let deliveryAddress = 'Collected via WhatsApp chat';
        const addrMatch = assistantMsgs.match(/deliver\s*to\s*[:*–-]?\s*([^\n\r]+)/i)
            || assistantMsgs.match(/address\s*[:*–-]?\s*([^\n\r]+)/i)
            || assistantMsgs.match(/pata\s*[:*–-]?\s*([^\n\r]+)/i);

        if (addrMatch && addrMatch[1]?.trim()) {
            const cleanAddr = addrMatch[1].replace(/[*_]/g, '').trim();
            if (cleanAddr.length > 3 && !cleanAddr.includes('[address]')) {
                deliveryAddress = cleanAddr;
            }
        }

        // 5. Payment method detection
        let paymentMethod = 'cash_on_delivery';
        const lowerAll = (userMsgs + ' ' + assistantMsgs).toLowerCase();
        if (lowerAll.includes('jazzcash') || lowerAll.includes('jazz cash')) {
            paymentMethod = 'jazzcash';
        } else if (lowerAll.includes('easypaisa') || lowerAll.includes('easy paisa')) {
            paymentMethod = 'easypaisa';
        }

        // 6. Notes / Summary
        const notes = session.history
            .filter(h => h.role === 'assistant')
            .slice(-2)
            .map(h => h.content)
            .join('\n')
            .substring(0, 1000)
            || 'Order placed via WhatsApp bot';

        return {
            subtotal,
            deliveryCharge,
            total,
            deliveryAddress,
            paymentMethod,
            notes,
        };
    }

    /**
     * Generate unique tracking code (e.g. TRK-FEZ-8492)
     */
    generateTrackingCode(restaurantName) {
        const prefix = (restaurantName || 'ORD').slice(0, 3).toUpperCase().replace(/[^A-Z]/g, 'X');
        const rand = Math.floor(1000 + Math.random() * 9000);
        return `TRK-${prefix}-${rand}`;
    }

    /**
     * Save order directly to MySQL DB, with HTTP API fallback
     */
    async save(customerPhone, session) {
        const restaurantId = session.restaurant?.id || 1;
        const parsed = this.parseOrderFromHistory(session);
        const trackingCode = this.generateTrackingCode(session.restaurant?.name);

        try {
            // 1. Direct MySQL insert for 0ms reliability (all required columns included)
            const db = getDbPool();
            const now = new Date();

            const [result] = await db.query(
                `INSERT INTO orders 
                 (restaurant_id, customer_phone, delivery_address, tracking_code, status, subtotal, delivery_charge, total, payment_method, notes, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)`,
                [
                    restaurantId,
                    customerPhone,
                    parsed.deliveryAddress,
                    trackingCode,
                    parsed.subtotal,
                    parsed.deliveryCharge,
                    parsed.total,
                    parsed.paymentMethod,
                    parsed.notes,
                    now,
                    now,
                ]
            );

            if (result && result.insertId) {
                console.log(`✅ Order #${result.insertId} saved directly to MySQL — Tracking: ${trackingCode}, Total: Rs.${parsed.total}, Address: ${parsed.deliveryAddress}`);
                return trackingCode;
            }
        } catch (dbErr) {
            console.warn('⚠️ Direct MySQL order insert failed, trying HTTP API fallback:', dbErr.message);
        }

        // 2. HTTP Fallback
        try {
            const res = await axios.post(
                `${LARAVEL_API}/orders/create`,
                {
                    customer_phone:   customerPhone,
                    restaurant_id:    restaurantId,
                    delivery_address: parsed.deliveryAddress,
                    notes:            parsed.notes,
                    status:           'pending',
                    subtotal:         parsed.subtotal,
                    delivery_charge:  parsed.deliveryCharge,
                    total:            parsed.total,
                    payment_method:   parsed.paymentMethod,
                },
                { timeout: 5000 }
            );

            return res.data?.tracking_code || trackingCode;
        } catch (apiErr) {
            console.error('❌ Could not save order via API:', apiErr.message);
            return trackingCode;
        }
    }
}
