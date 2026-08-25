import { randomInt } from 'crypto';
import { getDbPool } from './Database.js';

/**
 * Crockford Base32 — omits I, L, O and U so a code can't be misread (1/I, 0/O).
 * Must match Order::TRACKING_CODE_ALPHABET in app/Models/Order.php.
 */
const TRACKING_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

/** 16 symbols x 5 bits = 80 bits of entropy. */
const TRACKING_LENGTH = 16;

/** Up to 3 A–Z initials from the restaurant name, for human recognisability. */
function trackingPrefix(restaurantName) {
    const initials = String(restaurantName || '')
        .toUpperCase()
        .split(/\s+/)
        .map(word => (word.match(/[A-Z]/) || [''])[0])
        .join('')
        .slice(0, 3);

    return initials || 'ORD';
}

/** CSPRNG-backed suffix (`crypto.randomInt`, not `Math.random`). */
function randomTrackingSuffix() {
    let code = '';
    for (let i = 0; i < TRACKING_LENGTH; i++) {
        code += TRACKING_ALPHABET[randomInt(TRACKING_ALPHABET.length)];
    }
    return code;
}

/**
 * Pull a usable customer name out of the value the AI wrote on the summary's
 * "Name:" line. Returns null (not "Customer") when there is nothing real to
 * store, so the DB keeps NULL and a later order can still fill it in.
 */
function cleanCustomerName(raw) {
    if (!raw) return null;

    // Take just the first clause, drop markdown, trim trailing punctuation.
    let name = String(raw).split(/[,\n\r]/)[0].replace(/[*_`]/g, '').trim();
    name = name.replace(/[.!?？،]+$/u, '').trim();

    // Reject an unfilled placeholder ("[Customer Name]") or a generic token the
    // model echoed back instead of a real name.
    if (!name || name.includes('[') || name.includes(']')) return null;
    if (/^(customer|name|naam|n\/?a|none|guest|unknown)$/i.test(name)) return null;
    if (name.length < 2 || name.length > 60) return null;

    return name;
}

/**
 * Pull a usable customer contact number out of the value the AI wrote on the summary's
 * "Phone:" or "Contact:" line. Returns the fallback phone (sender WhatsApp number)
 * if no valid or custom phone was provided.
 */
function cleanCustomerPhone(raw, fallbackPhone) {
    if (!raw) return fallbackPhone;

    let phone = String(raw).split(/[,\n\r]/)[0].replace(/[*_`]/g, '').trim();
    phone = phone.replace(/[.!?؟،]+$/u, '').trim();

    if (!phone || phone.includes('[') || phone.includes(']')) return fallbackPhone;
    if (/^(same|same number|same no|whatsapp|wapp|n\/?a|none|unknown|yahi|yahi number)$/i.test(phone)) return fallbackPhone;

    // Digits only
    const digits = phone.replace(/\D/g, '');
    if (digits.length >= 10 && digits.length <= 15) {
        return digits;
    }

    return fallbackPhone;
}

/**
 * OrderService — parses order details from conversation and saves directly to MySQL database.
 */
export class OrderService {
    /**
     * Parse items, subtotal, delivery, grand total, and delivery address from chat history
     */
    parseOrderFromHistory(session) {
        const assistantHistory = (session.history || []).filter(h => h.role === 'assistant');
        const assistantMsgs = assistantHistory.map(h => h.content).join('\n');

        // Find the FINAL/LATEST Order Summary message block
        let finalSummaryMsg = '';
        for (let i = assistantHistory.length - 1; i >= 0; i--) {
            const content = assistantHistory[i].content || '';
            if (/order summary|aapka order|subtotal|total payable/i.test(content)) {
                finalSummaryMsg = content;
                break;
            }
        }
        if (!finalSummaryMsg && assistantHistory.length > 0) {
            finalSummaryMsg = assistantHistory[assistantHistory.length - 1].content || '';
        }

        const userMsgs = (session.history || [])
            .filter(h => h.role === 'user')
            .map(h => h.content)
            .join(' ');

        // 1. Subtotal extraction (from final summary first, then history fallback)
        let subtotal = 0;
        const subMatch = finalSummaryMsg.match(/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i)
            || assistantMsgs.match(/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i);
        if (subMatch) {
            subtotal = parseFloat(subMatch[1].replace(/,/g, '')) || 0;
        }

        // 2. Delivery charge
        let deliveryCharge = parseFloat(session.restaurant?.delivery_charge || 0);
        const delMatch = finalSummaryMsg.match(/delivery(?:\s*charge)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i)
            || assistantMsgs.match(/delivery(?:\s*charge)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i);
        if (delMatch) {
            deliveryCharge = parseFloat(delMatch[1].replace(/,/g, '')) || deliveryCharge;
        }

        // 3. Grand Total extraction (negative lookbehind for 'sub' so it never matches subtotal)
        let total = 0;
        const totalMatch = finalSummaryMsg.match(/(?<!sub)total(?:\s*payable)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i)
            || assistantMsgs.match(/(?<!sub)total(?:\s*payable)?\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.[0-9]{1,2})?)/i)
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

        // 4b. Customer name — read from the summary's "Name:" line (see
        // PromptBuilder). Anchored to line start and requiring a colon so it can't
        // catch a question like "aap ka naam?" earlier in the chat; the optional
        // asterisks tolerate the model bolding the label as *Name:*. Prefer the
        // final summary, fall back to anywhere in the assistant transcript.
        const nameRe = /(?:^|\n)\s*\**\s*(?:name|naam)\s*\**\s*[:：]\s*([^\n\r]+)/i;
        const nameMatch = finalSummaryMsg.match(nameRe) || assistantMsgs.match(nameRe);
        const customerName = cleanCustomerName(nameMatch?.[1]) || session.customerName || null;

        // 4c. Customer contact number — read from summary's "Phone:" or "Contact:" line
        const phoneRe = /(?:^|\n)\s*\**\s*(?:phone|contact|mobile|cell|number|rabta)\s*\**\s*[:：]\s*([^\n\r]+)/i;
        const phoneMatch = finalSummaryMsg.match(phoneRe) || assistantMsgs.match(phoneRe);
        const contactPhone = cleanCustomerPhone(phoneMatch?.[1], null);


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

        // 6. Extract Line Items strictly from the final summary message
        const items = this.extractOrderItems(finalSummaryMsg || assistantMsgs);

        // 7. Notes / Summary
        const notes = (session.history || [])
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
            customerName,
            contactPhone,
            paymentMethod,
            items,
            notes,
        };
    }

    /**
     * Extract individual ordered items from Order Summary (with deduplication)
     */
    extractOrderItems(summaryText) {
        const itemMap = new Map();
        const lines = summaryText.split('\n');

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

                const itemKey = `${fullItemName.toLowerCase()}___${(size || '').toLowerCase()}`;
                
                // Keep the latest or update record
                itemMap.set(itemKey, {
                    name: fullItemName,
                    size: size,
                    quantity: qty,
                    unit_price: unitPrice || 0,
                    subtotal: subtotal || (unitPrice * qty) || 0
                });
            }
        }

        return Array.from(itemMap.values());
    }

    /**
     * Generate a unique tracking code, e.g. `FEZ-7K2MQX9P4TVBNH3R`.
     *
     * Must stay in sync with Order::generateTrackingCode() on the Laravel side
     * (app/Models/Order.php) — both paths write to the same `tracking_code`
     * column and customers look codes up through either.
     *
     * The previous implementation was `TRK-${prefix}-${1000 + Math.random()*9000}`,
     * which had two distinct problems:
     *   1. Only 9,000 possible codes, from a non-cryptographic PRNG. A tracking
     *      code is a bearer token for the customer's name, phone and address, so
     *      the whole space could be enumerated in minutes.
     *   2. `tracking_code` is UNIQUE, so with 9,000 values collisions start
     *      breaking real orders after only ~120 of them (birthday bound).
     */
    generateTrackingCode(restaurantName) {
        return `${trackingPrefix(restaurantName)}-${randomTrackingSuffix()}`;
    }

    /**
     * Save order directly to MySQL DB.
     */
    async save(customerPhone, session) {
        const restaurantId = session.restaurant?.id || 1;
        const parsed = this.parseOrderFromHistory(session);
        const trackingCode = this.generateTrackingCode(session.restaurant?.name);

        // 1. Determine customer phone to store (given contact number or sender WhatsApp)
        const finalCustomerPhone = parsed.contactPhone || customerPhone;

        // Remember name and contact phone on the session so the owner alert
        // can show who ordered, and so follow-up orders don't have to re-ask.
        session.customerName = parsed.customerName;
        session.contactPhone = finalCustomerPhone;

        try {
            // 1. Direct MySQL insert for 0ms reliability (all required columns included)
            const db = getDbPool();
            const now = new Date();

            const [result] = await db.query(
                `INSERT INTO orders
                 (restaurant_id, customer_phone, customer_name, delivery_address, tracking_code, status, subtotal, delivery_charge, total, payment_method, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)`,
                [
                    restaurantId,
                    finalCustomerPhone,
                    parsed.customerName,
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
                console.log(`✅ Order #${orderId} saved directly to MySQL — Phone: ${finalCustomerPhone}, Tracking: ${trackingCode}, Total: Rs.${parsed.total}, Address: ${parsed.deliveryAddress}`);

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

                // Auto-upsert customer record into customers table for CRM and deal broadcasts
                await db.query(
                    `INSERT INTO customers (restaurant_id, phone, name, address, total_orders, total_spent, last_order_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                       total_orders = total_orders + 1,
                       total_spent = total_spent + VALUES(total_spent),
                       name = COALESCE(VALUES(name), name),
                       address = IF(VALUES(address) != 'Collected via WhatsApp chat', VALUES(address), address),
                       last_order_at = VALUES(last_order_at),
                       updated_at = VALUES(updated_at)`,
                    [
                        restaurantId,
                        finalCustomerPhone,
                        parsed.customerName,
                        parsed.deliveryAddress,
                        parsed.total,
                        now,
                        now,
                        now
                    ]
                ).catch(cErr => console.warn('⚠️ customer profile upsert note:', cErr.message));

                return trackingCode;
            }

            // An INSERT that neither threw nor produced an id should be
            // impossible; treat it as the failure it is rather than reporting
            // a tracking code for a row that does not exist.
            console.error('❌ Order INSERT returned no insertId — order NOT saved.');
        } catch (dbErr) {
            console.error('❌ Order INSERT failed — order NOT saved:', dbErr.message);
        }

        // There is deliberately no HTTP fallback. It used to POST to
        // `{LARAVEL_API}/orders/create`, but routes/api.php is not registered in
        // bootstrap/app.php (finding H-05), so that call could only ever 404 —
        // and the old code returned the tracking code regardless, telling the
        // customer their order was placed when nothing had been written. Failing
        // out loud is the honest behaviour; the caller apologises.
        return null;
    }

    /**
     * Flag an order's `owner_notified` column after the owner WhatsApp alert
     * actually succeeded. Matches on the unique tracking_code.
     */
    async markOwnerNotified(trackingCode) {
        if (!trackingCode) return;
        try {
            const db = getDbPool();
            await db.query(
                `UPDATE orders SET owner_notified = 1, updated_at = ? WHERE tracking_code = ?`,
                [new Date(), trackingCode]
            );
        } catch (e) {
            console.warn('⚠️ owner_notified flag update note:', e.message);
        }
    }
}
