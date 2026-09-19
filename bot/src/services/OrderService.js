import { randomInt } from 'crypto';
import { getDbPool } from './Database.js';
import { LogSanitizer } from '../utils/LogSanitizer.js';

/**
 * Crockford Base32 — omits I, L, O and U so a code can't be misread (1/I, 0/O).
 * Must match Order::TRACKING_CODE_ALPHABET in app/Models/Order.php.
 */
export const TRACKING_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

/** 16 symbols x 5 bits = 80 bits of entropy. */
export const TRACKING_LENGTH = 16;

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
export function randomTrackingSuffix() {
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

function cleanCustomerPhone(raw, fallbackPhone) {
    if (!raw) return fallbackPhone;

    let phone = String(raw).split(/[,\n\r]/)[0].replace(/[*_`]/g, '').trim();
    phone = phone.replace(/[.!?؟،]+$/u, '').trim();

    if (!phone || phone.includes('[') || phone.includes(']')) return fallbackPhone;
    if (/^(same|same number|same no|whatsapp|wapp|n\/?a|none|unknown|yahi|yahi number)$/i.test(phone)) return fallbackPhone;

    // Digits only
    let digits = phone.replace(/\D/g, '');
    if (digits.length === 12 && digits.startsWith('923')) {
        digits = '0' + digits.slice(2);
    } else if (digits.length === 14 && digits.startsWith('00923')) {
        digits = '0' + digits.slice(4);
    } else if (digits.length === 10 && digits.startsWith('3')) {
        digits = '0' + digits;
    }

    if (digits.length >= 10 && digits.length <= 15) {
        return digits;
    }

    return fallbackPhone;
}

/**
 * Normalizes item names for matching against stored menu items.
 */
export function normalizeItemName(str) {
    return String(str || '')
        .toLowerCase()
        .replace(/[*_`~]/g, '')
        .replace(/[^\p{L}\p{N}\s]/gu, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Resolves an item's authoritative unit price from stored menu items or deals.
 * Checks exact match, size variations, and active deals.
 */
export function resolveItemPriceFromMenu(itemName, itemSize, menuItems = [], deals = []) {
    const normSearch = normalizeItemName(itemName);
    if (!normSearch) return null;

    const normSize = itemSize ? normalizeItemName(itemSize) : '';

    // If itemSize exists, construct composite candidates first (e.g. "Butter Naan", "Naan Butter")
    const candidates = [];
    if (normSize) {
        candidates.push({ name: `${normSize} ${normSearch}`, isComposite: true });
        candidates.push({ name: `${normSearch} ${normSize}`, isComposite: true });
    }
    candidates.push({ name: normSearch, isComposite: false });

    const extractPrice = (mi, isComposite = false) => {
        let unitPrice = 0;
        let matchedSize = null;

        let sizes = mi.sizes;
        if (typeof sizes === 'string') {
            try { sizes = JSON.parse(sizes); } catch (e) { sizes = null; }
        }

        if (Array.isArray(sizes) && sizes.length > 0) {
            if (itemSize && !isComposite) {
                const normS = normalizeItemName(itemSize);
                const sMatch = sizes.find(s => {
                    const sName = normalizeItemName(s.size || s.name);
                    return sName === normS || sName.startsWith(normS) || normS.startsWith(sName);
                });
                if (sMatch && sMatch.price !== undefined) {
                    unitPrice = parseFloat(sMatch.price) || 0;
                    matchedSize = sMatch.size || itemSize;
                }
            }
            if (unitPrice === 0 && (mi.price === null || mi.price === undefined || parseFloat(mi.price) <= 0) && sizes[0]?.price) {
                unitPrice = parseFloat(sizes[0].price) || 0;
                matchedSize = sizes[0].size;
            }
        }

        if (unitPrice === 0 && mi.price !== undefined && mi.price !== null) {
            unitPrice = parseFloat(mi.price) || 0;
        }

        return {
            matched: true,
            menuItemId: mi.id || null,
            canonicalName: mi.name,
            size: isComposite ? null : (matchedSize || itemSize),
            unitPrice,
        };
    };

    // 1a. Check menu_items for EXACT match against candidates (composite variants first)
    for (const cand of candidates) {
        for (const mi of menuItems) {
            const normMi = normalizeItemName(mi.name);
            if (normMi === cand.name) {
                return extractPrice(mi, cand.isComposite);
            }
        }
    }

    // 2a. Check active deals for EXACT match against candidates
    for (const cand of candidates) {
        for (const deal of deals) {
            const normDeal = normalizeItemName(deal.title || deal.name);
            if (normDeal === cand.name) {
                const dealPrice = parseFloat(deal.discount_value || deal.price || 0);
                return {
                    matched: true,
                    dealId: deal.id || null,
                    canonicalName: deal.title || deal.name,
                    size: cand.isComposite ? null : itemSize,
                    unitPrice: dealPrice,
                };
            }
        }
    }

    // 1b. Check menu_items where menu item name contains candidate
    for (const cand of candidates) {
        for (const mi of menuItems) {
            const normMi = normalizeItemName(mi.name);
            if (normMi && normMi.includes(cand.name)) {
                return extractPrice(mi, cand.isComposite);
            }
        }
    }

    // 1c. Check menu_items where candidate contains menu item name, sorting menu items by length DESCENDING
    // so longer/more specific item names (e.g. "Butter Naan") match before generic short ones (e.g. "Naan")
    const sortedMenuItems = [...menuItems].sort((a, b) => (b.name?.length || 0) - (a.name?.length || 0));
    for (const cand of candidates) {
        for (const mi of sortedMenuItems) {
            const normMi = normalizeItemName(mi.name);
            if (normMi && cand.name.includes(normMi)) {
                return extractPrice(mi, cand.isComposite);
            }
        }
    }

    // 2b. Check active deals for substring match
    for (const cand of candidates) {
        for (const deal of deals) {
            const normDeal = normalizeItemName(deal.title || deal.name);
            if (normDeal && (normDeal.includes(cand.name) || cand.name.includes(normDeal))) {
                const dealPrice = parseFloat(deal.discount_value || deal.price || 0);
                return {
                    matched: true,
                    dealId: deal.id || null,
                    canonicalName: deal.title || deal.name,
                    size: cand.isComposite ? null : itemSize,
                    unitPrice: dealPrice,
                };
            }
        }
    }

    return null;
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

        // Find the FINAL/LATEST Order Summary message block (prioritizing messages with item lines)
        let finalSummaryMsg = '';
        for (let i = assistantHistory.length - 1; i >= 0; i--) {
            const content = assistantHistory[i].content || '';
            if (/order summary|aapka order/i.test(content) && /\d+\s*[xX×]/.test(content)) {
                finalSummaryMsg = content;
                break;
            }
        }
        if (!finalSummaryMsg) {
            for (let i = assistantHistory.length - 1; i >= 0; i--) {
                const content = assistantHistory[i].content || '';
                if (/order summary|aapka order|subtotal|total payable/i.test(content)) {
                    finalSummaryMsg = content;
                    break;
                }
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
        let contactPhone = cleanCustomerPhone(phoneMatch?.[1], null);

        // If no phone found in AI summary, search all user messages for any Pakistani mobile number:
        if (!contactPhone) {
            const userPhoneMatch = userMsgs.match(/(?:(?:\+|00)?92|0)?(3\d{2}[- ]?\d{7})/);
            if (userPhoneMatch) {
                contactPhone = cleanCustomerPhone(userPhoneMatch[0], null);
            }
        }

        if (!contactPhone && session.contactPhone) {
            contactPhone = cleanCustomerPhone(session.contactPhone, null);
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

        // 6. Extract Line Items strictly from the final summary message, falling back to assistant history
        let items = this.extractOrderItems(finalSummaryMsg);
        if (!items || items.length === 0) {
            items = this.extractOrderItems(assistantMsgs);
        }

        // 7. Notes / Summary
        const notes = (session.history || [])
            .filter(h => h.role === 'assistant')
            .slice(-2)
            .map(h => h.content)
            .join('\n')
            .substring(0, 1000)
            || 'Order placed via WhatsApp bot';

        // Override delivery address if GPS pin provided
        if ((session.locationSource === 'whatsapp_pin' || session.locationSource === 'customer_pin') && session.deliveryAddress) {
            deliveryAddress = session.deliveryAddress;
        }

        const parsed = {
            subtotal,
            deliveryCharge,
            total,
            deliveryAddress,
            deliveryLat: session.deliveryLat || null,
            deliveryLng: session.deliveryLng || null,
            customerName,
            contactPhone,
            paymentMethod,
            items,
            notes,
        };

        // Recalculate using authoritative menu items from session if present
        if (session.restaurant?.menu_items?.length || session.restaurant?.active_deals?.length) {
            this.recalculateTotalsFromMenu(
                parsed,
                session.restaurant.menu_items || [],
                session.restaurant.active_deals || [],
                session.restaurant.delivery_charge
            );
        } else {
            // Even without menu_items (e.g. mock unit tests), enforce: final total = subtotal + delivery charge
            parsed.deliveryCharge = parseFloat(session.restaurant?.delivery_charge ?? deliveryCharge ?? 0);
            parsed.total = (parseFloat(parsed.subtotal) || 0) + (parseFloat(parsed.deliveryCharge) || 0);
        }

        return parsed;
    }

    /**
     * Recalculates all order totals strictly from authoritative database prices:
     * item subtotal + delivery charge = final total.
     * AI prices are NEVER trusted.
     */
    recalculateTotalsFromMenu(parsed, menuItems = [], deals = [], deliveryCharge = null) {
        if (!parsed) return parsed;

        let calculatedSubtotal = 0;
        let anyItemMatched = false;

        if (Array.isArray(parsed.items) && parsed.items.length > 0) {
            for (const item of parsed.items) {
                const resolved = resolveItemPriceFromMenu(item.name, item.size, menuItems, deals);
                if (resolved && resolved.matched) {
                    anyItemMatched = true;
                    item.menu_item_id = resolved.menuItemId || null;
                    item.name = resolved.canonicalName || item.name;
                    item.size = resolved.size !== undefined ? resolved.size : item.size;
                    item.unit_price = resolved.unitPrice;
                    item.subtotal = resolved.unitPrice * (item.quantity || 1);
                    calculatedSubtotal += (item.subtotal || 0);
                } else {
                    // SECURITY: Database menu pricing is the ONLY price authority.
                    // If an item does NOT match the menu or active deals, reject it (unit_price = 0).
                    // Cart validation will reject any cart where items cannot be matched.
                    item.menu_item_id = null;
                    item.unit_price = 0;
                    item.subtotal = 0;
                    item.unmatched = true;
                }
            }
        }

        // If items were present or matched, subtotal is the exact sum of line items
        if (anyItemMatched || (Array.isArray(parsed.items) && parsed.items.length > 0)) {
            parsed.subtotal = calculatedSubtotal;
        }

        // Authoritative delivery charge from restaurant configuration
        if (deliveryCharge !== null && deliveryCharge !== undefined) {
            parsed.deliveryCharge = parseFloat(deliveryCharge) || 0;
        }

        // Authoritative final total = item subtotal + delivery charge
        parsed.total = (parseFloat(parsed.subtotal) || 0) + (parseFloat(parsed.deliveryCharge) || 0);

        return parsed;
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
     * Generate a unique, cryptographically secure random tracking code,
     * e.g. `FZ-7K2MQX9P4TVBNH3R` or `ORD-8X9K2M1PQ4TVBNH3`.
     *
     * Must stay in sync with Order::generateTrackingCode() on the Laravel side
     * (app/Models/Order.php) — both paths write to the same `tracking_code`
     * column and customers look codes up through either.
     *
     * Uses CSPRNG with 80 bits of entropy from Crockford Base32 alphabet.
     */
    async generateTrackingCode(restaurantIdOrName, maybeRestaurantName) {
        const restaurantName = maybeRestaurantName !== undefined ? maybeRestaurantName : restaurantIdOrName;
        const prefix = trackingPrefix(restaurantName);

        for (let attempt = 0; attempt < 5; attempt++) {
            const code = `${prefix}-${randomTrackingSuffix()}`;
            try {
                const db = getDbPool();
                const [rows] = await db.query('SELECT id FROM orders WHERE tracking_code = ? LIMIT 1', [code]);
                if (!rows || rows.length === 0) {
                    return code;
                }
            } catch (e) {
                // If DB query fails for uniqueness check, return the CSPRNG code directly
                // (collision probability across 80 bits is ~10^-24).
                return code;
            }
        }

        return `${prefix}-${randomTrackingSuffix()}`;
    }

    /**
     * Validates the parsed cart before attempting to save the order to the database.
     * Ensures:
     * 1. At least one line item exists.
     * 2. Every item has quantity > 0.
     * 3. Authoritative subtotal > 0 and final total > 0.
     */
    validateCart(parsed) {
        if (!parsed) return false;

        if (!Array.isArray(parsed.items) || parsed.items.length === 0) {
            return false;
        }

        for (const item of parsed.items) {
            if (!item.name || !item.quantity || item.quantity <= 0) {
                return false;
            }
            // SECURITY: Never accept items that failed menu matching or have zero/negative price
            if (item.unmatched || !item.unit_price || item.unit_price <= 0) {
                return false;
            }
        }

        const subtotal = parseFloat(parsed.subtotal);
        const total = parseFloat(parsed.total);

        if (isNaN(subtotal) || subtotal <= 0) {
            return false;
        }

        if (isNaN(total) || total <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Save order directly to MySQL DB.
     */
    async save(customerPhone, session) {
        const restaurantId = session.restaurant?.id || 1;
        const parsed = this.parseOrderFromHistory(session);

        // Fetch authoritative database prices and delivery charge for the restaurant
        try {
            const db = getDbPool();
            const [menuRows] = await db.query(
                'SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = 1 ORDER BY sort_order',
                [restaurantId]
            ).catch(() => [[]]);

            const [dealRows] = await db.query(
                'SELECT * FROM deals WHERE restaurant_id = ? AND is_active = 1',
                [restaurantId]
            ).catch(() => [[]]);

            const [restRows] = await db.query(
                'SELECT delivery_charge FROM restaurants WHERE id = ?',
                [restaurantId]
            ).catch(() => [[]]);

            const dbDelivery = restRows?.[0]?.delivery_charge !== undefined ? restRows[0].delivery_charge : (session.restaurant?.delivery_charge ?? 0);

            // Combine DB menu items with any parsed Excel/CSV items from session
            const allItems = [...(menuRows || [])];
            if (Array.isArray(session.restaurant?.menu_excel_items)) {
                for (const exItem of session.restaurant.menu_excel_items) {
                    if (!allItems.some(mi => normalizeItemName(mi.name) === normalizeItemName(exItem.name))) {
                        allItems.push(exItem);
                    }
                }
            }

            this.recalculateTotalsFromMenu(parsed, allItems, dealRows || [], dbDelivery);
        } catch (dbErr) {
            console.error('❌ Could not fetch authoritative DB prices for recalculation:', dbErr.message);
            return null;
        }

        // Validate cart before proceeding
        if (!this.validateCart(parsed)) {
            console.warn(`⚠️ Cart validation failed for ${customerPhone}:`, {
                itemsCount: parsed.items?.length || 0,
                subtotal: parsed.subtotal,
                total: parsed.total,
            });
            return null;
        }

        const trackingCode = await this.generateTrackingCode(restaurantId, session.restaurant?.name);

        // 1. Determine customer phone to store (given contact number or sender WhatsApp)
        let finalCustomerPhone = parsed.contactPhone;
        if (!finalCustomerPhone) {
            const cleanDigits = String(customerPhone || '').replace(/\D/g, '');
            if (cleanDigits.length === 12 && cleanDigits.startsWith('923')) {
                finalCustomerPhone = '0' + cleanDigits.slice(2);
            } else if (cleanDigits.length === 10 && cleanDigits.startsWith('3')) {
                finalCustomerPhone = '0' + cleanDigits;
            } else {
                finalCustomerPhone = customerPhone;
            }
        }

        // Remember name and contact phone on the session so the owner alert
        // can show who ordered, and so follow-up orders don't have to re-ask.
        session.customerName = parsed.customerName;
        session.contactPhone = finalCustomerPhone;

        let connection = null;
        try {
            // 1. Direct MySQL insert with full transaction support for atomicity
            const pool = getDbPool();
            connection = await pool.getConnection();
            await connection.beginTransaction();

            const now = new Date();

            const [result] = await connection.query(
                `INSERT INTO orders
                 (restaurant_id, customer_phone, customer_name, delivery_address, delivery_lat, delivery_lng, tracking_code, status, subtotal, delivery_charge, total, payment_method, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)`,
                [
                    restaurantId,
                    finalCustomerPhone,
                    parsed.customerName,
                    parsed.deliveryAddress,
                    parsed.deliveryLat,
                    parsed.deliveryLng,
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

            if (!result || !result.insertId) {
                throw new Error('Order INSERT returned no insertId');
            }

            const orderId = result.insertId;
            const maskedPhone = LogSanitizer.maskPhone(finalCustomerPhone);
            const maskedAddress = LogSanitizer.redactAddress(parsed.deliveryAddress);
            console.log(`✅ Order #${orderId} saved directly to MySQL — Phone: ${maskedPhone}, Tracking: ${trackingCode}, Total: Rs.${parsed.total}, Address: ${maskedAddress}`);

            // Insert itemized records into order_items table atomically
            if (parsed.items && parsed.items.length > 0) {
                for (const item of parsed.items) {
                    await connection.query(
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
                    );
                }
                console.log(`📦 Saved ${parsed.items.length} itemized records for Order #${orderId}`);
            }

            // Auto-upsert customer record into customers table for CRM and deal broadcasts
            await connection.query(
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

            await connection.commit();

            session.lastOrder = parsed;

            const resObj = new String(trackingCode);
            resObj.trackingCode = trackingCode;
            resObj.order = parsed;
            return resObj;
        } catch (dbErr) {
            if (connection) {
                try {
                    await connection.rollback();
                } catch (rbErr) {
                    console.error('❌ Rollback failed:', rbErr.message);
                }
            }
            console.error('❌ Order transaction failed — order rolled back / NOT saved:', dbErr.message);
        } finally {
            if (connection) {
                connection.release();
            }
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
     * Save order directly from session cart (for rule-based menu flow).
     */
    async saveFromCart(customerPhone, session) {
        const restaurantId = session.restaurant?.id || 1;
        const cart = session.cart || [];
        const orderData = session.orderData || {};

        if (cart.length === 0) {
            console.warn(`⚠️ Cannot save order: cart is empty for ${customerPhone}`);
            return null;
        }

        let subtotal = 0;
        const items = [];
        for (const item of cart) {
            const qty = item.quantity || 1;
            const price = parseFloat(item.unitPrice || item.price || 0);
            const lineSub = price * qty;
            subtotal += lineSub;
            items.push({
                menu_item_id: item.menuItemId || null,
                deal_id: item.dealId || null,
                name: item.displayName || item.name,
                size: item.size || null,
                unit_price: price,
                quantity: qty,
                subtotal: lineSub,
            });
        }

        const deliveryCharge = parseFloat(session.restaurant?.delivery_charge || 0);
        const total = subtotal + deliveryCharge;

        // Clean phone
        let finalCustomerPhone = cleanCustomerPhone(orderData.contactPhone, null);
        if (!finalCustomerPhone) {
            finalCustomerPhone = cleanCustomerPhone(session.contactPhone, null);
        }
        if (!finalCustomerPhone) {
            const cleanDigits = String(customerPhone || '').replace(/\D/g, '');
            if (cleanDigits.length === 12 && cleanDigits.startsWith('923')) {
                finalCustomerPhone = '0' + cleanDigits.slice(2);
            } else if (cleanDigits.length === 10 && cleanDigits.startsWith('3')) {
                finalCustomerPhone = '0' + cleanDigits;
            } else {
                finalCustomerPhone = customerPhone;
            }
        }

        const deliveryAddress = orderData.deliveryAddress || session.deliveryAddress || 'Collected via WhatsApp';
        const deliveryLat = orderData.deliveryLat || session.deliveryLat || null;
        const deliveryLng = orderData.deliveryLng || session.deliveryLng || null;
        const customerName = cleanCustomerName(orderData.customerName) || cleanCustomerName(session.customerName) || null;
        const paymentMethod = orderData.paymentMethod || 'cash_on_delivery';

        const parsed = {
            subtotal,
            deliveryCharge,
            total,
            deliveryAddress,
            deliveryLat,
            deliveryLng,
            customerName,
            contactPhone: finalCustomerPhone,
            paymentMethod,
            items,
            notes: 'Order placed via WhatsApp rule-based bot',
        };

        if (!this.validateCart(parsed)) {
            console.warn(`⚠️ Cart validation failed in saveFromCart for ${customerPhone}:`, parsed);
            return null;
        }

        const trackingCode = await this.generateTrackingCode(restaurantId, session.restaurant?.name);

        session.customerName = customerName;
        session.contactPhone = finalCustomerPhone;

        let connection = null;
        try {
            const pool = getDbPool();
            connection = await pool.getConnection();
            await connection.beginTransaction();

            const now = new Date();

            const [result] = await connection.query(
                `INSERT INTO orders
                 (restaurant_id, customer_phone, customer_name, delivery_address, delivery_lat, delivery_lng, tracking_code, status, subtotal, delivery_charge, total, payment_method, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)`,
                [
                    restaurantId,
                    finalCustomerPhone,
                    parsed.customerName,
                    parsed.deliveryAddress,
                    parsed.deliveryLat,
                    parsed.deliveryLng,
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

            if (!result || !result.insertId) {
                throw new Error('Order INSERT returned no insertId');
            }

            const orderId = result.insertId;
            const maskedPhone = LogSanitizer.maskPhone(finalCustomerPhone);
            const maskedAddress = LogSanitizer.redactAddress(parsed.deliveryAddress);
            console.log(`✅ Order #${orderId} saved directly to MySQL via cart — Phone: ${maskedPhone}, Tracking: ${trackingCode}, Total: Rs.${parsed.total}, Address: ${maskedAddress}`);

            if (parsed.items && parsed.items.length > 0) {
                for (const item of parsed.items) {
                    await connection.query(
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
                    );
                }
            }

            // Auto-upsert customer record
            await connection.query(
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

            await connection.commit();

            session.lastOrder = parsed;

            const resObj = new String(trackingCode);
            resObj.trackingCode = trackingCode;
            resObj.order = parsed;
            return resObj;
        } catch (dbErr) {
            if (connection) {
                try {
                    await connection.rollback();
                } catch (rbErr) {
                    console.error('❌ Rollback failed:', rbErr.message);
                }
            }
            console.error('❌ saveFromCart transaction failed:', dbErr.message);
            return null;
        } finally {
            if (connection) {
                connection.release();
            }
        }
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
