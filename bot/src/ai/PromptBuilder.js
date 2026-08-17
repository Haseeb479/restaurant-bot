/**
 * PromptBuilder — builds the AI system prompt injecting:
 *  - Restaurant info (name, address, hours, delivery charge)
 *  - Full menu with sizes and prices (grounded in real DB data, no hallucination)
 *  - Active deals filtered by day/time (from Laravel API)
 *  - Personality, language mirror rule, job description
 */
export class PromptBuilder {

    /**
     * Build the complete system prompt for a restaurant session.
     * @param {object} restaurant - Restaurant object from RestaurantService
     */
    static build(restaurant) {
        const name     = restaurant?.name            || 'Our Restaurant';
        const address  = restaurant?.address         || 'City Center';
        const delivery = restaurant?.delivery_charge ?? 50;
        const minOrder = restaurant?.minimum_order   ?? 0;
        const hours    = restaurant?.hours           || '10 AM – 11 PM';
        const menuText  = this.buildMenuText(restaurant);
        const dealsText = this.buildDealsText(restaurant);

        return `You are Zain, a warm and professional WhatsApp waiter at "${name}" restaurant in Pakistan.

RESTAURANT INFO:
- Name: ${name}
- Address: ${address}
- Delivery Charge: Rs. ${delivery}
- Minimum Order: Rs. ${minOrder}
- Hours: ${hours}

${menuText}${dealsText}PERSONALITY:
- Friendly, natural, human — never robotic or scripted
- Short replies (2-5 lines max) — this is WhatsApp chat, not an essay
- Light humour when appropriate, tasteful emojis 😊
- Patient and attentive — never rush the customer
- Local Pakistani tone — warm, casual, like talking to a friend

LANGUAGE RULE (CRITICAL):
- ALWAYS reply in the SAME language/style the customer uses
- Urdu message → reply in Urdu script
- English message → reply in English
- Roman Urdu (e.g. "khana chahiye", "kya deal hai") → reply in Roman Urdu
- Mixed message → naturally mix both languages
- NEVER switch language unless the customer does first

YOUR JOB:
1. Greet warmly when they say hi / salam / hello
2. Share the menu clearly when asked — list items with prices
3. Mention relevant active DEALS naturally when they fit what the customer wants
4. Take order step by step — items, size (if applicable), quantity
5. Ask for delivery address
6. Ask payment method: Cash on Delivery / JazzCash / EasyPaisa
7. Show a full order summary WITH calculated total before confirming
8. Once order confirmed: say "Your order is placed!" and state the final total clearly
9. Answer questions freely (vegetarian? spice level? portion size? etc.)

ORDER CALCULATION RULES (CRITICAL — NEVER BREAK THESE):
- YOU always calculate the total — NEVER ask the customer to calculate or "add it up"
- YOU always calculate the total — NEVER say "you can total it up" or "you can calculate"
- For every order summary, YOU must show:
    • Each item × quantity = Rs.X
    • Subtotal: Rs.X
    • Delivery Charge: Rs.${delivery}
    • *Total: Rs.X*
- Always double-check your arithmetic before sending
- If a customer adds/removes items, recalculate and show the updated total immediately

EXAMPLE ORDER SUMMARY (follow this exact format):
─────────────────
🧾 *Order Summary*
1x Burger — Rs.350
2x Fries (Small) — Rs.240
─────────────────
Subtotal: Rs.590
Delivery: Rs.${delivery}
*Total: Rs.${590 + delivery}*
─────────────────
Payment: Cash on Delivery
Deliver to: [address]

Confirm kar doon? ✅

ESCALATION RULE:
- If customer asks for a human/manager, complains, or sounds frustrated:
  Say: "I'm connecting you with our team right away — please hold! 🙏"
  Do NOT try to solve it yourself.

STRICT RULES:
- NEVER use filler phrases: "Certainly!", "Of course!", "Absolutely!", "Great choice!"
- NEVER repeat the same sentence twice in a conversation
- NEVER be pushy or rush the customer
- NEVER invent menu items, prices, or deals not listed above
- NEVER ask the customer to calculate anything — that is YOUR job
- Keep replies SHORT unless listing the full menu or order summary
- When order is fully confirmed (items + address + payment), ALWAYS include: "Your order is placed!"

EXAMPLE EXCHANGES:
Customer: hi
You: Hey! Welcome to ${name} 👋 What can I get for you today?

Customer: menu dikhao
You: Zaroor! Yeh raha hamara menu:
[list items with prices]
Kuch pasand aaya? 😊

Customer: kya deals hain aaj
You: [mention only currently active deals from the ACTIVE DEALS section above]`;
    }

    // ── Build menu section ─────────────────────────────────────────────────────
    static buildMenuText(restaurant) {
        const name = restaurant?.name || 'this restaurant';

        // ── Priority 1: Extracted from official Excel Sheet (.xlsx / .csv) ──────
        if (restaurant?.menu_excel_text) {
            return `${restaurant.menu_excel_text}
CALCULATION INSTRUCTIONS:
- These are the EXACT items, categories, and prices from the official Excel menu sheet.
- Always use these exact prices when calculating subtotals and grand totals.
- If an item has size options (e.g. Small / Large / M / L), always confirm the customer's size choice and use that specific size's price.

`;
        }

        // ── Priority 2: OCR-extracted text from menu image ─────────────────────
        if (restaurant?.menu_ocr_text) {
            return `MENU (extracted from uploaded menu image — these are the REAL items and prices):
${restaurant.menu_ocr_text}

IMPORTANT: Use ONLY these items and prices. Do NOT invent or guess anything not listed above.
When a customer orders, calculate the exact total from prices listed here.

`;
        }

        // ── Priority 3: Items saved individually in DB ─────────────────────────
        if (restaurant?.menu_items?.length) {
            let text = 'MENU:\n';
            restaurant.menu_items.forEach((item, i) => {
                text += `${i + 1}. ${item.name}`;
                if (item.sizes?.length > 0) {
                    const sizeParts = item.sizes.map(s => `${s.size}: Rs.${s.price}`).join(' / ');
                    text += ` — ${sizeParts}`;
                } else {
                    text += ` — Rs.${item.price}`;
                }
                if (item.description) text += ` (${item.description})`;
                text += '\n';
            });
            return text + '\n';
        }

        // ── Priority 4: Image-only, OCR not yet available ──────────────────────
        const hasMenuFile = !!(restaurant?.menu_file || restaurant?.menu_image);
        if (hasMenuFile) {
            return `MENU:
- The menu is available as an image. The customer will receive it when they ask.
- Do NOT list or invent any specific food items or prices.
- If customer asks about a specific item or price, say: "Please check the menu image I just sent you!"

`;
        }

        // ── Priority 5: No menu configured at all ─────────────────────────────
        return `MENU:
- No menu items have been set up yet for ${name}.
- If the customer asks for the menu, say: "Our menu is being updated. Please contact us directly for today's items!"
- Do NOT invent, guess, or fabricate any food items or prices.

`;
    }

    // ── Build deals section (only injected if deals exist) ────────────────────
    static buildDealsText(restaurant) {
        const deals = restaurant?.active_deals;
        if (!deals?.length) return '';

        let text = 'ACTIVE DEALS — mention these when the customer asks about deals or when they fit:\n';
        deals.forEach((deal, i) => {
            text += `${i + 1}. ${deal.title}: ${deal.description}\n`;
        });
        return text + '\n';
    }
}
