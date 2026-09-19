/**
 * MenuFlowHandler — Deterministic, rule-based menu ordering flow for WhatsApp.
 *
 * Provides a predictable, zero-hallucination, instant (<10ms) ordering experience:
 * 1. Category browsing (from database menu)
 * 2. Item listing with clear prices and size options
 * 3. Shopping cart with real-time subtotal, delivery fee, and grand total
 * 4. Step-by-step checkout: Name -> Phone -> Address/Pin -> Confirmation
 * 5. Default Cash on Delivery (COD) with JazzCash/EasyPaisa option
 * 6. Global commands: menu, cart, clear, checkout, cancel, status
 */

export const STATES = {
    IDLE: 'IDLE',
    BROWSING_CATEGORY: 'BROWSING_CATEGORY',
    CHECKOUT_NAME: 'CHECKOUT_NAME',
    CHECKOUT_PHONE: 'CHECKOUT_PHONE',
    CHECKOUT_ADDRESS: 'CHECKOUT_ADDRESS',
    ORDER_CONFIRMATION: 'ORDER_CONFIRMATION',
};

export class MenuFlowHandler {
    constructor() {
        // Stateless handler; session holds the customer conversation state
    }

    /**
     * Main message processor for the rule-based ordering flow.
     * @param {string} rawText
     * @param {object} session
     * @param {object|null} locationCoords
     * @returns {Promise<{ reply: string, orderReady: boolean }>}
     */
    async handleMessage(rawText, session, locationCoords = null) {
        const text = (rawText || '').trim();
        const lower = text.toLowerCase();
        const restaurant = session.restaurant || {};

        // Initialize session flow data if not present
        if (!session.flowState) session.flowState = STATES.IDLE;
        if (!Array.isArray(session.cart)) session.cart = [];
        if (!session.orderData) {
            session.orderData = {
                customerName: session.customerName || null,
                contactPhone: session.contactPhone || null,
                deliveryAddress: session.deliveryAddress || null,
                deliveryLat: session.deliveryLat || null,
                deliveryLng: session.deliveryLng || null,
                paymentMethod: 'cash_on_delivery',
            };
        }

        // ── 0. Handle Location Pin at any time ────────────────────────────────
        if (locationCoords) {
            session.deliveryLat = locationCoords.lat;
            session.deliveryLng = locationCoords.lng;
            session.locationSource = 'whatsapp_pin';
            if (locationCoords.address || locationCoords.name) {
                session.deliveryAddress = locationCoords.address || locationCoords.name;
                session.orderData.deliveryAddress = session.deliveryAddress;
            }
            session.orderData.deliveryLat = locationCoords.lat;
            session.orderData.deliveryLng = locationCoords.lng;

            // If we were waiting for an address during checkout, advance to confirmation!
            if (session.flowState === STATES.CHECKOUT_ADDRESS && session.cart.length > 0) {
                session.flowState = STATES.ORDER_CONFIRMATION;
                const summary = this.buildOrderSummary(session);
                return {
                    reply: `📍 *Location Pin receive ho gayi hai!* ✅\n\n${summary}`,
                    orderReady: false
                };
            }

            return {
                reply: `📍 *Location Pin receive ho gayi hai!* ✅\n\nMenu dekhne ke liye *menu* likhein ya order jari rakhne ke liye *cart* check karein. 😊`,
                orderReady: false
            };
        }

        // ── 1. Global Commands (work from any state) ──────────────────────────
        // Cancel command
        if (/^(cancel|radd|khatam|exit|stop)$/i.test(lower)) {
            session.cart = [];
            session.flowState = STATES.IDLE;
            return {
                reply: `❌ *Order cancel kar diya gaya hai.*\n\nNai shuruat ke liye *menu* likhein ya kisi bhi waqt rabta karein! 😊`,
                orderReady: false
            };
        }

        // Clear cart command
        if (/^(clear|empty|khali|delete cart|cart clear)$/i.test(lower)) {
            session.cart = [];
            session.flowState = STATES.IDLE;
            return {
                reply: `🗑️ *Aapka cart khali kar diya gaya hai.*\n\nNaya order start karne ke liye *menu* likhein! 📋`,
                orderReady: false
            };
        }

        // View Cart command
        if (/^(cart|basket|my cart|bill|kya hai cart mein)$/i.test(lower)) {
            return {
                reply: this.buildCartView(session),
                orderReady: false
            };
        }

        // Menu / Categories command
        if (/^(menu|categories|items|list|card|start|hi|hello|hey|salam|assalam|aoa|menu card)$/i.test(lower) && session.flowState === STATES.IDLE) {
            session.flowState = STATES.IDLE;
            return {
                reply: this.buildCategoryMenu(restaurant),
                orderReady: false
            };
        }

        // Return to categories menu from category browsing
        if (lower === '0' || lower === 'back' || lower === 'wapas' || lower === 'menu') {
            session.flowState = STATES.IDLE;
            return {
                reply: this.buildCategoryMenu(restaurant),
                orderReady: false
            };
        }

        // Checkout trigger from cart or while browsing
        if (/^(checkout|check out|order now|order place|order kar do|confirm order|place order|done)$/i.test(lower)) {
            if (session.cart.length === 0) {
                return {
                    reply: `🛒 *Aapka cart abhi khali hai!*\n\nPehle menu se items select karein:\n\n` + this.buildCategoryMenu(restaurant),
                    orderReady: false
                };
            }
            return this.startCheckout(session);
        }

        // ── 2. State Machine Routing ──────────────────────────────────────────
        switch (session.flowState) {
            case STATES.IDLE:
                return this.handleIdleState(lower, text, session);

            case STATES.BROWSING_CATEGORY:
                return this.handleBrowsingCategoryState(lower, text, session);

            case STATES.CHECKOUT_NAME:
                return this.handleCheckoutNameState(text, session);

            case STATES.CHECKOUT_PHONE:
                return this.handleCheckoutPhoneState(text, session);

            case STATES.CHECKOUT_ADDRESS:
                return this.handleCheckoutAddressState(text, session);

            case STATES.ORDER_CONFIRMATION:
                return this.handleOrderConfirmationState(lower, text, session);

            default:
                session.flowState = STATES.IDLE;
                return {
                    reply: this.buildCategoryMenu(restaurant),
                    orderReady: false
                };
        }
    }

    // ── State Handlers ────────────────────────────────────────────────────────

    /**
     * IDLE: Customer selects a category number or deals
     */
    handleIdleState(lower, text, session) {
        const restaurant = session.restaurant || {};
        const categories = this.getAvailableCategories(restaurant);

        // Check if user selected a valid category number
        const catIndex = parseInt(lower, 10);
        if (!isNaN(catIndex) && catIndex >= 1 && catIndex <= categories.length) {
            const selectedCat = categories[catIndex - 1];
            session.selectedCategory = selectedCat;
            session.flowState = STATES.BROWSING_CATEGORY;

            return {
                reply: this.buildCategoryItemsMenu(restaurant, selectedCat),
                orderReady: false
            };
        }

        // If user typed category name directly (e.g. "burgers", "deals")
        const matchedCat = categories.find(c => lower.includes(c.name.toLowerCase()));
        if (matchedCat) {
            session.selectedCategory = matchedCat;
            session.flowState = STATES.BROWSING_CATEGORY;
            return {
                reply: this.buildCategoryItemsMenu(restaurant, matchedCat),
                orderReady: false
            };
        }

        // Unrecognized input in IDLE — show welcome & category menu
        return {
            reply: this.buildCategoryMenu(restaurant),
            orderReady: false
        };
    }

    /**
     * BROWSING_CATEGORY: Customer selects item(s) to add to cart
     */
    handleBrowsingCategoryState(lower, text, session) {
        const restaurant = session.restaurant || {};
        const category = session.selectedCategory;
        if (!category) {
            session.flowState = STATES.IDLE;
            return {
                reply: this.buildCategoryMenu(restaurant),
                orderReady: false
            };
        }

        const items = this.getItemsForCategory(restaurant, category);

        // Check if user wants to switch category directly (e.g. user typed another category number)
        const categories = this.getAvailableCategories(restaurant);

        // Parse selection: allows "1", "1x2", "2*3", "1, 2x3" etc.
        const addResults = this.parseItemSelections(text, items);

        if (addResults.length > 0) {
            for (const add of addResults) {
                this.addItemToCart(session, add.item, add.quantity, add.size);
            }

            const addedText = addResults.map(a => `• ${a.quantity}x ${a.item.displayName} (Rs.${a.item.price * a.quantity})`).join('\n');
            const cartTotal = this.calculateCartTotal(session);

            return {
                reply: `✅ *Cart mein add ho gaya:*\n${addedText}\n\n` +
                       `🛒 *Subtotal:* Rs.${cartTotal.subtotal} | *Total with Delivery:* Rs.${cartTotal.total}\n\n` +
                       `👉 Aur items add karne ke liye number bhejein\n` +
                       `👉 *0* ya *menu* — Categories par wapas jane ke liye\n` +
                       `👉 *cart* — Cart dekhne ke liye\n` +
                       `👉 *checkout* — Order complete karne ke liye 🛵`,
                orderReady: false
            };
        }

        // If not item number, check if user typed a category number from main menu
        const catIndex = parseInt(lower, 10);
        if (!isNaN(catIndex) && catIndex >= 1 && catIndex <= categories.length) {
            const selectedCat = categories[catIndex - 1];
            session.selectedCategory = selectedCat;
            return {
                reply: this.buildCategoryItemsMenu(restaurant, selectedCat),
                orderReady: false
            };
        }

        return {
            reply: `⚠️ Barah-e-karam item number aur quantity likhein (e.g. *1* ya *1x2*).\n\n` +
                   `Categories wapas dekhne ke liye *0* likhein, ya cart check karne ke liye *cart* likhein. 😊`,
            orderReady: false
        };
    }

    /**
     * Start checkout process
     */
    startCheckout(session) {
        if (!session.orderData) session.orderData = {};

        // If customer name is already saved, ask for phone or address directly
        if (!session.orderData.customerName && !session.customerName) {
            session.flowState = STATES.CHECKOUT_NAME;
            return {
                reply: `📝 *Order Booking — Step 1/3*\n\nBarah-e-karam apna *Naam* batayein: 👤`,
                orderReady: false
            };
        }

        session.orderData.customerName = session.orderData.customerName || session.customerName;

        if (!session.orderData.contactPhone && !session.contactPhone) {
            session.flowState = STATES.CHECKOUT_PHONE;
            return {
                reply: `📝 *Order Booking — Step 2/3*\n\nRabtay ke liye *Phone Number* batayein 📱\n_(Ya agar yahi WhatsApp number hai toh *'same'* likhein)_:`,
                orderReady: false
            };
        }

        session.orderData.contactPhone = session.orderData.contactPhone || session.contactPhone;

        // Check if location pin / address already exists
        if (session.deliveryAddress || session.orderData.deliveryAddress) {
            session.orderData.deliveryAddress = session.deliveryAddress || session.orderData.deliveryAddress;
            session.flowState = STATES.ORDER_CONFIRMATION;
            return {
                reply: this.buildOrderSummary(session),
                orderReady: false
            };
        }

        session.flowState = STATES.CHECKOUT_ADDRESS;
        return {
            reply: `📝 *Order Booking — Step 3/3*\n\n📍 Barah-e-karam apna *Delivery Address* likhein ya WhatsApp par apni *Location Pin* share karein: 🛵`,
            orderReady: false
        };
    }

    /**
     * CHECKOUT_NAME: Save name and advance to phone
     */
    handleCheckoutNameState(text, session) {
        const cleanName = text.replace(/[*_`]/g, '').trim();
        if (cleanName.length < 2) {
            return {
                reply: `Barah-e-karam apna sahi naam batayein: 👤`,
                orderReady: false
            };
        }

        session.orderData.customerName = cleanName;
        session.customerName = cleanName;

        // Next step: Phone
        session.flowState = STATES.CHECKOUT_PHONE;
        return {
            reply: `Shukriya *${cleanName}*! 😊\n\nRabtay ke liye *Phone Number* batayein 📱\n_(Ya agar yahi WhatsApp number hai toh *'same'* likhein)_:`,
            orderReady: false
        };
    }

    /**
     * CHECKOUT_PHONE: Save contact phone and advance to address
     */
    handleCheckoutPhoneState(text, session) {
        const lower = text.toLowerCase().trim();
        let phone = text;

        if (lower === 'same' || lower === 'same number' || lower === 'yahi' || lower === 'yahi number') {
            phone = session.customerPhone || 'Same as WhatsApp';
        } else {
            // Clean digits
            const digits = text.replace(/\D/g, '');
            if (digits.length >= 10 && digits.length <= 15) {
                phone = digits;
            } else {
                return {
                    reply: `⚠️ Barah-e-karam durust Pakistani mobile number likhein (e.g. *03001234567*) ya *'same'* reply karein: 📱`,
                    orderReady: false
                };
            }
        }

        session.orderData.contactPhone = phone;
        session.contactPhone = phone;

        // Next step: Address (if not already verified via pin)
        if (session.deliveryAddress || session.orderData.deliveryAddress) {
            session.orderData.deliveryAddress = session.deliveryAddress || session.orderData.deliveryAddress;
            session.flowState = STATES.ORDER_CONFIRMATION;
            return {
                reply: this.buildOrderSummary(session),
                orderReady: false
            };
        }

        session.flowState = STATES.CHECKOUT_ADDRESS;
        return {
            reply: `📍 Barah-e-karam apna *Delivery Address* likhein ya WhatsApp par apni *Location Pin* share karein: 🛵`,
            orderReady: false
        };
    }

    /**
     * CHECKOUT_ADDRESS: Save address and show Order Summary
     */
    handleCheckoutAddressState(text, session) {
        const cleanAddr = text.replace(/[*_`]/g, '').trim();
        if (cleanAddr.length < 3) {
            return {
                reply: `⚠️ Barah-e-karam mukammal delivery address likhein (misaal: House #, Street #, Area) ya apni *Location Pin* share karein: 📍`,
                orderReady: false
            };
        }

        session.orderData.deliveryAddress = cleanAddr;
        session.deliveryAddress = cleanAddr;

        // Advance to order confirmation
        session.flowState = STATES.ORDER_CONFIRMATION;
        return {
            reply: this.buildOrderSummary(session),
            orderReady: false
        };
    }

    /**
     * ORDER_CONFIRMATION: Handle confirmation or payment change
     */
    handleOrderConfirmationState(lower, text, session) {
        // Payment method change: JazzCash
        if (lower.includes('jazzcash') || lower.includes('jazz cash')) {
            session.orderData.paymentMethod = 'jazzcash';
            return {
                reply: `✅ Payment method *JazzCash* select ho gaya hai!\n\n` + this.buildOrderSummary(session),
                orderReady: false
            };
        }

        // Payment method change: EasyPaisa
        if (lower.includes('easypaisa') || lower.includes('easy paisa')) {
            session.orderData.paymentMethod = 'easypaisa';
            return {
                reply: `✅ Payment method *EasyPaisa* select ho gaya hai!\n\n` + this.buildOrderSummary(session),
                orderReady: false
            };
        }

        // Payment method change: COD
        if (lower === 'cod' || lower.includes('cash on delivery') || lower === 'cash') {
            session.orderData.paymentMethod = 'cash_on_delivery';
            return {
                reply: `✅ Payment method *Cash on Delivery (COD)* select ho gaya hai!\n\n` + this.buildOrderSummary(session),
                orderReady: false
            };
        }

        // Affirmative confirmation
        const isAffirmative = /^(yes|haan|ha|confirm|theek hai|thk hai|ok|kr do|kar do|done|jee|ji|bhej do|order confirm|placed)$/i.test(lower.trim());

        if (isAffirmative) {
            // Ready to save order to database!
            return {
                reply: '', // ChatHandler will send confirmation with tracking code
                orderReady: true
            };
        }

        // Unrecognized response during confirmation
        return {
            reply: `⚠️ Order confirm karne ke liye barah-e-karam *'YES'* ya *'CONFIRM'* reply karein ✅\n\n` +
                   `Agar order cancel karna ho toh *'CANCEL'* likhein. 😊`,
            orderReady: false
        };
    }

    // ── Cart & Pricing Logic ──────────────────────────────────────────────────

    addItemToCart(session, item, quantity = 1, size = null) {
        if (!Array.isArray(session.cart)) session.cart = [];
        const qty = Math.max(1, Math.min(quantity, 50));

        const existing = session.cart.find(c =>
            c.menuItemId === item.id &&
            c.name === item.name &&
            (c.size || null) === (size || null)
        );

        if (existing) {
            existing.quantity += qty;
            existing.subtotal = existing.quantity * existing.unitPrice;
        } else {
            session.cart.push({
                menuItemId: item.id || null,
                dealId: item.dealId || null,
                name: item.name,
                displayName: item.displayName || item.name,
                size: size || null,
                unitPrice: item.price,
                quantity: qty,
                subtotal: item.price * qty,
            });
        }
    }

    calculateCartTotal(session) {
        const cart = session.cart || [];
        let subtotal = 0;
        for (const item of cart) {
            subtotal += (item.unitPrice * item.quantity);
        }

        const deliveryCharge = parseFloat(session.restaurant?.delivery_charge || 0);
        const total = subtotal + deliveryCharge;

        return { subtotal, deliveryCharge, total };
    }

    buildCartView(session) {
        const cart = session.cart || [];
        if (cart.length === 0) {
            return `🛒 *Aapka cart khali hai!*\n\nMenu dekhne ke liye *menu* reply karein. 📋`;
        }

        const { subtotal, deliveryCharge, total } = this.calculateCartTotal(session);

        let lines = `🛒 *Aapka Shopping Cart:*\n───────────────────\n`;
        cart.forEach((item, idx) => {
            lines += `${idx + 1}. *${item.quantity}x* ${item.displayName} — Rs.${item.unitPrice * item.quantity}\n`;
        });
        lines += `───────────────────\n`;
        lines += `Subtotal: Rs.${subtotal}\n`;
        lines += `Delivery Charge: Rs.${deliveryCharge}\n`;
        lines += `*Total Payable: Rs.${total}*\n───────────────────\n\n`;
        lines += `👉 *checkout* — Order complete karne ke liye 🛵\n`;
        lines += `👉 *menu* — Mazeed items add karne ke liye\n`;
        lines += `👉 *clear* — Cart khali karne ke liye 🗑️`;

        return lines;
    }

    buildOrderSummary(session) {
        const cart = session.cart || [];
        const { subtotal, deliveryCharge, total } = this.calculateCartTotal(session);
        const orderData = session.orderData || {};

        const payMethodDisplay = orderData.paymentMethod === 'jazzcash'
            ? 'JazzCash 📱'
            : (orderData.paymentMethod === 'easypaisa' ? 'EasyPaisa 📱' : 'Cash on Delivery (COD) 💵');

        let summary = `🧾 *Order Summary*\n───────────────────\n`;
        cart.forEach(item => {
            summary += `${item.quantity}x ${item.displayName} — Rs.${item.unitPrice * item.quantity}\n`;
        });
        summary += `───────────────────\n`;
        summary += `Subtotal: Rs.${subtotal}\n`;
        summary += `Delivery: Rs.${deliveryCharge}\n`;
        summary += `*Total: Rs.${total}*\n`;
        summary += `───────────────────\n`;
        summary += `👤 Name: ${orderData.customerName || 'Customer'}\n`;
        summary += `📱 Phone: ${orderData.contactPhone || 'WhatsApp Number'}\n`;
        summary += `💵 Payment: ${payMethodDisplay}\n`;
        summary += `📍 Deliver to: ${orderData.deliveryAddress || 'Address not specified'}\n\n`;
        summary += `Kya main aapka order confirm kar doon? ✅\n\n`;
        summary += `👉 Reply karein *'YES'* ya *'CONFIRM'* order place karne ke liye!\n`;
        if (orderData.paymentMethod === 'cash_on_delivery') {
            summary += `👉 Agar JazzCash se payment karni hai toh *'JAZZCASH'* likhein.`;
        }

        return summary;
    }

    // ── Menu Data Extraction Helpers ──────────────────────────────────────────

    getAvailableCategories(restaurant) {
        const categories = [];

        // 1. Check for active deals
        if (Array.isArray(restaurant.active_deals) && restaurant.active_deals.length > 0) {
            categories.push({ id: 'deals', name: 'Special Deals & Offers 🌟', isDeals: true });
        }

        // 2. DB Categories
        if (Array.isArray(restaurant.categories) && restaurant.categories.length > 0) {
            restaurant.categories.forEach(cat => {
                categories.push({ id: cat.id, name: cat.name, isDeals: false });
            });
        }

        // 3. Fallback: If no categories but menu_items exist, create default categories
        if (categories.length === 0 && Array.isArray(restaurant.menu_items) && restaurant.menu_items.length > 0) {
            categories.push({ id: 'all', name: 'Main Menu 🍔', isDeals: false });
        }

        return categories;
    }

    getItemsForCategory(restaurant, category) {
        const items = [];

        if (category.isDeals) {
            const deals = restaurant.active_deals || [];
            deals.forEach((deal, idx) => {
                const price = parseFloat(deal.discount_value || deal.price || 0);
                items.push({
                    id: null,
                    dealId: deal.id,
                    name: deal.title || deal.name,
                    displayName: deal.title || deal.name,
                    price,
                    description: deal.description || '',
                });
            });
            return items;
        }

        const menuItems = restaurant.menu_items || [];
        const filtered = category.id === 'all'
            ? menuItems
            : menuItems.filter(m => String(m.category_id) === String(category.id));

        filtered.forEach(mi => {
            // Expand sizes if available
            let sizes = mi.sizes;
            if (typeof sizes === 'string') {
                try { sizes = JSON.parse(sizes); } catch (e) { sizes = null; }
            }

            if (Array.isArray(sizes) && sizes.length > 0) {
                sizes.forEach(s => {
                    const sPrice = parseFloat(s.price) || parseFloat(mi.price) || 0;
                    const sName = s.size || s.name || '';
                    items.push({
                        id: mi.id,
                        name: mi.name,
                        displayName: `${mi.name} (${sName})`,
                        size: sName,
                        price: sPrice,
                        description: mi.description || '',
                    });
                });
            } else {
                items.push({
                    id: mi.id,
                    name: mi.name,
                    displayName: mi.name,
                    size: null,
                    price: parseFloat(mi.price) || 0,
                    description: mi.description || '',
                });
            }
        });

        return items;
    }

    buildCategoryMenu(restaurant) {
        const categories = this.getAvailableCategories(restaurant);
        if (categories.length === 0) {
            return `⚠️ Hamara menu jald update kiya ja raha hai. Barah-e-karam thori der baad check karein!`;
        }

        let msg = `Assalam-o-Alaikum! Welcome to *${restaurant.name || 'our restaurant'}* 🍽️\n\n`;
        msg += `📋 *Menu Categories:*\n───────────────────\n`;
        categories.forEach((cat, idx) => {
            msg += `${idx + 1}️⃣ *${cat.name}*\n`;
        });
        msg += `───────────────────\n`;
        msg += `👉 Items dekhne ke liye *Category number* likhein (e.g. *1*) 👇\n`;
        msg += `💡 Kisi bhi waqt *cart* ya *checkout* likh sakte hain.`;

        return msg;
    }

    buildCategoryItemsMenu(restaurant, category) {
        const items = this.getItemsForCategory(restaurant, category);
        if (items.length === 0) {
            return `Is category mein abhi koi items dastiyab nahi hain.\n\nCategories par wapas jane ke liye *0* reply karein.`;
        }

        let msg = `🍽️ *${category.name}*\n───────────────────\n`;
        items.forEach((item, idx) => {
            msg += `${idx + 1}. *${item.displayName}* — Rs.${item.price}\n`;
            if (item.description) {
                msg += `   _${item.description}_\n`;
            }
        });
        msg += `───────────────────\n`;
        msg += `👉 Order karne ke liye item number aur quantity likhein:\n`;
        msg += `• *1* (1 qty ke liye)\n`;
        msg += `• *1x2* (2 qty ke liye)\n\n`;
        msg += `🔙 *0* — Categories menu par wapas jane ke liye\n`;
        msg += `🛒 *cart* — Cart check karne ke liye`;

        return msg;
    }

    /**
     * Parses customer selection input such as:
     * - "1"
     * - "1x2"
     * - "2 * 3"
     * - "1x2, 2x1"
     */
    parseItemSelections(text, items) {
        const results = [];
        if (!text || items.length === 0) return results;

        // Split by comma or newline if multiple items selected
        const segments = text.split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);

        for (const seg of segments) {
            // Pattern 1: 1x2 or 1*2 or 1 x 2
            const multMatch = seg.match(/^(\d+)\s*[*xX×]\s*(\d+)$/);
            if (multMatch) {
                const val1 = parseInt(multMatch[1], 10);
                const val2 = parseInt(multMatch[2], 10);

                // Usually itemIndex x quantity (e.g. 1x2 -> item 1, qty 2)
                if (val1 >= 1 && val1 <= items.length) {
                    results.push({ item: items[val1 - 1], quantity: val2, size: items[val1 - 1].size });
                    continue;
                }
                // Or quantity x itemIndex (e.g. 2x1 -> 2 of item 1)
                if (val2 >= 1 && val2 <= items.length) {
                    results.push({ item: items[val2 - 1], quantity: val1, size: items[val2 - 1].size });
                    continue;
                }
            }

            // Pattern 2: Single item number (e.g. "1")
            const singleMatch = seg.match(/^(\d+)$/);
            if (singleMatch) {
                const idx = parseInt(singleMatch[1], 10);
                if (idx >= 1 && idx <= items.length) {
                    results.push({ item: items[idx - 1], quantity: 1, size: items[idx - 1].size });
                    continue;
                }
            }
        }

        return results;
    }
}
