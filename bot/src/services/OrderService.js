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

        // 1. Subtotal extraction (with decimal support)
        let subtotal = 0;
        const subMatch = assistantMsgs.match(/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i);
        if (subMatch) {
            subtotal = parseFloat(subMatch[1].replace(/,/g, '')) || 0;
        }

        // 2. Delivery charge
        let deliveryCharge = parseFloat(session.restaurant?.delivery_charge || 0);
        const delMatch = assistantMsgs.match(/delivery(?:\s*charge)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i);
        if (delMatch) {
            deliveryCharge = parseFloat(delMatch[1].replace(/,/g, '')) || deliveryCharge;
        }

        // 3. Grand Total extraction (negative lookbehind for 'sub' so it never matches subtotal)
        let total = 0;
        const totalMatch = assistantMsgs.match(/(?<!sub)total(?:\s*payable)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i)
            || assistantMsgs.match(/rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)\s*(?:total|payable)/i);

        if (totalMatch) {
            total = parseFloat(totalMatch[1].replace(/,/g, '')) || 0;
        }

        // Cross-calculate if one was missing or if total equaled subtotal without delivery
        if (total > 0 && subtotal === 0) {
            subtotal = Math.max(0, total - deliveryCharge);
        } else if (subtotal > 0 && total === 0) {
            total = subtotal + deliveryCharge;
        } else if (subtotal > 0 && total === subtotal && deliveryCharge > 0) {
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

        // 5. Payment method detection: check USER messages first
        let paymentMethod = 'cash_on_delivery';
        const userLower = userMsgs.toLowerCase();

        if (userLower.includes('jazzcash') || userLower.includes('jazz cash')) {
            paymentMethod = 'jazzcash';
        } else if (userLower.includes('easypaisa') || userLower.includes('easy paisa')) {
            paymentMethod = 'easypaisa';
        } else if (userLower.includes('cod') || userLower.includes('cash')) {
            paymentMethod = 'cash_on_delivery';
        } else {
            // Check assistant's confirmed summary line
            const payLineMatch = assistantMsgs.match(/payment\s*[:*–-]?\s*([^\n\r]+)/i);
            if (payLineMatch) {
                const line = payLineMatch[1].toLowerCase();
                if (line.includes('jazzcash') || line.includes('jazz cash')) paymentMethod = 'jazzcash';
                else if (line.includes('easypaisa') || line.includes('easy paisa')) paymentMethod = 'easypaisa';
                else paymentMethod = 'cash_on_delivery';
            }
        }

        // 6. Extract Line Items (e.g. 4x Paneer Roll, 12x Tandoori Roti)
        const items = this.extractOrderItems(assistantMsgs);

        // 7. Notes / Summary
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
            items,
            notes,
        };
    }

    /**
     * Extract individual ordered items from Order Summary
     */
    extractOrderItems(assistantMsgs) {
        const items = [];
        const lines = assistantMsgs.split('\n');

        for (const rawLine of lines) {
            const line = rawLine.trim().replace(/^[-*•]\s*/, '').replace(/[*_]/g, '');

            const itemMatch = line.match(/^([0-9]+)\s*[xX×]\s*(.+?)(?:\s*—|\s*[-–:]\s*|\s+Rs\.|\s+@|\s+each|$)/i);
            if (itemMatch) {
                const qty = parseInt(itemMatch[1], 10) || 1;
                let fullItemName = itemMatch[2].trim();

                if (/^(order summary|subtotal|delivery|total|payment|deliver to)/i.test(fullItemName)) {
                    continue;
                }

                let size = null;
                const sizeMatch = fullItemName.match(/\(([^)]+)\)/);
                if (sizeMatch) {
                    size = sizeMatch[1].trim();
                    fullItemName = fullItemName.replace(/\([^)]+\)/, '').trim();
                }

                let unitPrice = 0;
                let subtotal = 0;

                const allPrices = Array.from(line.matchAll(/rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/gi))
                    .map(m => parseFloat(m[1].replace(/,/g, '')));

                if (allPrices.length >= 2) {
                    unitPrice = allPrices[0];
                    subtotal = allPrices[allPrices.length - 1];
                } else if (allPrices.length === 1) {
                    subtotal = allPrices[0];
                    unitPrice = subtotal / qty;
                }

                items.push({
                    name: fullItemName,
                    size: size,
                    quantity: qty,
                    unit_price: unitPrice || 0,
                    subtotal: subtotal || (unitPrice * qty) || 0
                });
            }
        }

        return items;
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
                const orderId = result.insertId;
                console.log(`✅ Order #${orderId} saved directly to MySQL — Tracking: ${trackingCode}, Total: Rs.${parsed.total}, Address: ${parsed.deliveryAddress}`);

                // Insert itemized records into order_items table
                if (parsed.items && parsed.items.length > 0) {
                    for (const item of parsed.items) {
                        await db.query(
                            `INSERT INTO order_items (order_id, name, size, unit_price, quantity, subtotal, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                            [
                                orderId,
                                item.name,
                                item.size,
                                item.unit_price,
                                item.quantity,
                                item.subtotal,
                                now,
                                now,
                            ]
                        ).catch(itemErr => console.warn('⚠️ order_item insert note:', itemErr.message));
                    }
                    console.log(`📦 Saved ${parsed.items.length} itemized records for Order #${orderId}`);
                }

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
                    items:            parsed.items,
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
