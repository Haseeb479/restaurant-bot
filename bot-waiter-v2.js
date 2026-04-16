import http from 'http';
import whatsappWebPkg from 'whatsapp-web.js';
import qrcode from 'qrcode-terminal';
import axios from 'axios';
import Groq from 'groq-sdk';
import dotenv from 'dotenv';

dotenv.config({ path: '.env.llm' });

// ─── Models in priority order ────────────────────────────────────────────────
const MODELS = [
    'llama-3.3-70b-versatile',
    'llama-3.1-8b-instant',
    'meta-llama/llama-4-scout-17b-16e-instruct',
];

const lastRequestTime = {};
const REQUEST_DELAY_MS = parseInt(process.env.REQUEST_DELAY_MS) || 2000;

const { Client, LocalAuth } = whatsappWebPkg;

const GROQ_API_KEY = process.env.GROQ_API_KEY || '';
const LARAVEL_API = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';
const CLIENT_ID = process.argv[2] || 'waiter-bot-v2';
const INTERNAL_PORT = process.argv[3] || process.env.BOT_INTERNAL_PORT || 3000;

let groq;
const sessions = {};

if (GROQ_API_KEY) {
    groq = new Groq({ apiKey: GROQ_API_KEY });
    console.log('✅ Groq AI loaded');
} else {
    console.log('⚠️  No GROQ_API_KEY — fallback mode only');
}

// ─── WhatsApp Client ──────────────────────────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth({ clientId: CLIENT_ID }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    }
});

// ─── Get Restaurant by BOT number (msg.to) ───────────────────────────────────
async function getRestaurantByBotNumber(botNumber) {
    try {
        const normalized = botNumber.replace('@c.us', '').replace(/[^0-9]/g, '');
        console.log(`🔍 Looking up restaurant for bot number: ${normalized}`);

        const res = await axios.get(
            `${LARAVEL_API}/restaurant-by-bot/${normalized}`,
            { timeout: 5000 }
        );

        if (res.data.is_open === false) {
            console.log(`⚠️  Restaurant "${res.data.name}" is currently closed`);
            return { ...res.data, _closed: true };
        }

        console.log(`✅ Restaurant found: ${res.data.name}`);
        return res.data;

    } catch (err) {
        if (err.response?.status === 404) {
            console.log(`⚠️  No restaurant registered for this bot number`);
        } else {
            console.log(`⚠️  Could not fetch restaurant: ${err.message}`);
        }
        return null;
    }
}

// ─── Build menu text for AI prompt ───────────────────────────────────────────
function buildMenuText(restaurant) {
    if (!restaurant?.menu_items?.length) {
        return `Menu:
1. Mango Juice     - M: Rs.150 / L: Rs.250
2. Orange Juice    - M: Rs.150 / L: Rs.250
3. Mix Fruit Juice - M: Rs.200 / L: Rs.300
4. Water Bottle    - Rs.50`;
    }

    let text = 'Menu:\n';
    restaurant.menu_items.forEach((item, i) => {
        text += `${i + 1}. ${item.name}`;
        if (item.sizes && item.sizes.length > 0) {
            const sizeParts = item.sizes.map(s => `${s.size}: Rs.${s.price}`).join(' / ');
            text += ` — ${sizeParts}`;
        } else {
            text += ` — Rs.${item.price}`;
        }
        if (item.description) text += ` (${item.description})`;
        text += '\n';
    });

    return text;
}

// ─── System Prompt ────────────────────────────────────────────────────────────
function buildSystemPrompt(restaurant) {
    const name = restaurant?.name || 'Our Restaurant';
    const address = restaurant?.address || 'City Center';
    const delivery = restaurant?.delivery_charge ?? 50;
    const minOrder = restaurant?.minimum_order ?? 0;
    const hours = restaurant?.hours || '10 AM – 11 PM';
    const menu = buildMenuText(restaurant);

    return `You are Zain, a warm and professional WhatsApp waiter at "${name}" restaurant in Pakistan.

RESTAURANT INFO:
- Name: ${name}
- Address: ${address}
- Delivery Charge: Rs. ${delivery}
- Minimum Order: Rs. ${minOrder}
- Hours: ${hours}

${menu}

PERSONALITY:
- Friendly, natural, human — never robotic or scripted
- Short replies (2-5 lines max) — this is WhatsApp chat, not an essay
- Light humour when appropriate, tasteful emojis
- Patient and attentive — never rush the customer

LANGUAGE RULE (CRITICAL):
- ALWAYS reply in the SAME language the customer uses
- Urdu message → reply in Urdu
- English message → reply in English
- Mixed message → mix both
- Roman Urdu is fine

YOUR JOB:
1. Greet warmly when they say hi
2. Share menu when asked
3. Take order step by step — items, size (if applicable), quantity
4. Ask for delivery address
5. Ask payment method (Cash on Delivery / JazzCash / EasyPaisa)
6. Show full order summary with total before confirming
7. Confirm order is placed — tell customer they will receive a tracking code shortly
8. Answer questions freely

STRICT RULES:
- NEVER use filler phrases like "Certainly!", "Of course!", "Absolutely!"
- NEVER repeat the same line twice
- NEVER be pushy or rush the customer
- NEVER make up items or prices not in the menu above
- When order is fully confirmed (items + address + payment), always include:
  "Your order is placed!" and show the total clearly
- Keep replies SHORT unless listing the menu

EXAMPLE:
Customer: hi
You: Hey! Welcome to ${name} 👋 What can I get for you today?

Customer: menu dikhao
You: Zaroor! Yeh raha hamara menu:\n[list items]\nKuch pasand aaya? 😊`;
}

// ─── Groq Chat ────────────────────────────────────────────────────────────────
async function chat(customerPhone, userMessage, restaurant) {
    if (!groq) return fallback(userMessage);

    const now = Date.now();
    const last = lastRequestTime[customerPhone] || 0;
    const wait = REQUEST_DELAY_MS - (now - last);
    if (wait > 0) await new Promise(r => setTimeout(r, wait));
    lastRequestTime[customerPhone] = Date.now();

    if (!sessions[customerPhone]) {
        sessions[customerPhone] = { history: [], restaurant };
    }

    const session = sessions[customerPhone];
    const systemPrompt = buildSystemPrompt(session.restaurant);

    session.history.push({ role: 'user', content: userMessage });

    let reply = null;
    let lastError = null;

    for (const modelName of MODELS) {
        try {
            console.log(`🔄 Trying model: ${modelName}`);

            const messages = [
                { role: 'system', content: systemPrompt },
                ...session.history
            ];

            const completion = await groq.chat.completions.create({
                model: modelName,
                messages,
                max_tokens: 512,
                temperature: 0.85,
                top_p: 0.95,
            });

            reply = completion.choices[0]?.message?.content?.trim();
            if (reply) { console.log(`✅ Reply via ${modelName}`); break; }

        } catch (err) {
            const isRateLimit = err.status === 429 ||
                err.message?.includes('rate_limit_exceeded');

            if (isRateLimit) {
                console.log(`⚠️  ${modelName} rate limited — trying next in 3s...`);
                lastError = err;
                await new Promise(r => setTimeout(r, 3000));
                continue;
            }

            console.error(`❌ ${modelName} error: ${err.message}`);
            lastError = err;
            continue;
        }
    }

    if (!reply) {
        console.error('❌ All models failed:', lastError?.message);
        session.history.pop();
        return fallback(userMessage);
    }

    session.history.push({ role: 'assistant', content: reply });

    if (session.history.length > 30) {
        session.history = session.history.slice(-30);
    }

    // If order confirmed → save to DB and send tracking code to customer
    if (isOrderConfirmed(reply)) {
        console.log(`🎯 Order confirmed for ${customerPhone}`);
        const trackingCode = await saveOrderToDB(customerPhone, session);
        if (trackingCode) {
            const trackingMsg = `🎉 *Your tracking code is: ${trackingCode}*\n\nSend this code anytime to check your order status!`;
            try {
                await client.sendMessage(`${customerPhone}@c.us`, trackingMsg);
                console.log(`📤 Tracking code sent to ${customerPhone}: ${trackingCode}`);
            } catch (e) {
                console.log('⚠️  Could not send tracking message:', e.message);
            }
        }
    }

    return reply;
}

// ─── Order Confirmed Detection ────────────────────────────────────────────────
function isOrderConfirmed(reply) {
    const lower = reply.toLowerCase();
    return (
        lower.includes('your order is placed') ||
        lower.includes('order placed') ||
        lower.includes('آرڈر ہو گیا') ||
        lower.includes('آرڈر ہوگیا') ||
        (lower.includes('total') && lower.includes('placed'))
    );
}

// ─── Save Order to Laravel DB ─────────────────────────────────────────────────
async function saveOrderToDB(customerPhone, session) {
    try {
        const res = await axios.post(`${LARAVEL_API}/orders/create`, {
            customer_phone: customerPhone,
            restaurant_id: session.restaurant?.id || 1,
            delivery_address: 'Collected via chat',
            notes: session.history.filter(h => h.role === 'assistant').slice(-2).map(h => h.content).join('\n').substring(0, 500) || 'Order taken via WhatsApp bot',
            status: 'pending',
            subtotal: 0,
            delivery_charge: session.restaurant?.delivery_charge || 0,
            total: 0,
            payment_method: 'cash_on_delivery',
        }, { timeout: 5000 });

        const trackingCode = res.data?.tracking_code;
        if (trackingCode) {
            console.log(`✅ Order saved — Tracking: ${trackingCode}`);
            return trackingCode;
        }
    } catch (err) {
        console.log('⚠️  Could not save order:', err.message);
    }
    return null;
}

// ─── Tracking Code Detection ──────────────────────────────────────────────────
// Format: JC-2026-00042 (initials-year-paddednumber)
function isTrackingCode(message) {
    return /^[A-Z]{1,3}-\d{4}-\d{3,6}$/i.test(message.trim());
}

// ─── Get Order Status from Laravel ───────────────────────────────────────────
async function getOrderStatus(trackingCode) {
    try {
        const res = await axios.get(
            `${LARAVEL_API}/orders/track/${trackingCode.toUpperCase()}`,
            { timeout: 5000 }
        );
        return res.data;
    } catch (err) {
        if (err.response?.status === 404) return null;
        throw err;
    }
}

// ─── Format Tracking Reply ────────────────────────────────────────────────────
function formatTrackingReply(order) {
    return `📦 *Order Status*\n\n`
        + `🔖 Tracking: *${order.tracking_code}*\n`
        + `📊 Status: *${order.status_label}*\n\n`
        + `${order.status_message}\n\n`
        + `💰 Total: Rs. ${order.total}\n`
        + `🕐 Placed: ${order.placed_at}`;
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
function fallback(message) {
    const m = message.toLowerCase();
    if (/hi|hello|hey|salam|سلام|assalam/.test(m))
        return "Hey! Welcome 👋 What can I get for you today?";
    if (/menu|kya hai|what.*have|منو|مینو/.test(m))
        return "Here's our menu 📋\n🥤 Mango Juice – M:Rs.150 / L:Rs.250\n🥤 Orange Juice – M:Rs.150 / L:Rs.250\n💧 Water – Rs.50\n\nWhat would you like? 😊";
    if (/order|chahiye|چاہیے|want/.test(m))
        return "Sure! Tell me what you'd like and your delivery address 🙂";
    if (/track|tracking/.test(m))
        return "Please share your tracking code and I'll check your order status!";
    return "Hey! I'm here to help 😊 What would you like today?";
}

// ─── WhatsApp Events ──────────────────────────────────────────────────────────
client.on('qr', (qr) => {
    qrcode.generate(qr, { small: true });
    console.log('\n📱 Scan QR code to connect!\n');
});

client.on('ready', () => {
    console.log('');
    console.log('✅ ========================================');
    console.log('✅  WhatsApp Restaurant Bot is LIVE!');
    console.log('✅ ========================================');
    console.log(`🤖 Models: ${MODELS.join(' → ')}`);
    console.log('🏪 Mode: Multi-restaurant (identifies by bot number)');
    console.log('🌐 Languages: English + Urdu (auto-detect)');
    console.log('🔗 Laravel API:', LARAVEL_API);
    console.log(`🌐 Internal API: port ${INTERNAL_PORT}`);
    console.log('');
});

client.on('message', async (msg) => {
    if (msg.from.includes('@g.us')) return; // skip groups
    if (msg.from === 'status@broadcast') return; // skip broadcast
    if (!msg.body?.trim()) return; // skip empty

    const customerPhone = msg.from.split('@')[0];
    const botNumber = msg.to;
    console.log(`\n📩 [${customerPhone}] → [${botNumber}]: ${msg.body}`);

    try {
        // ── Check if customer is sending a tracking code ──
        if (isTrackingCode(msg.body.trim())) {
            const trackingCode = msg.body.trim().toUpperCase();
            console.log(`🔍 Tracking lookup: ${trackingCode}`);
            const order = await getOrderStatus(trackingCode);
            if (!order) {
                await msg.reply(`❌ No order found with tracking code *${trackingCode}*.\nPlease check and try again.`);
            } else {
                await msg.reply(formatTrackingReply(order));
            }
            return; // don't pass to AI
        }

        // ── Step 1: Identify which restaurant this message is for ──
        let restaurant = await getRestaurantByBotNumber(botNumber);

        // ── Step 2: Handle restaurant not found ──
        if (!restaurant) {
            console.log('⚠️  No restaurant found for this number — using defaults');
            restaurant = {
                id: 1,
                name: 'Our Restaurant',
                address: 'City Center',
                delivery_charge: 50,
                minimum_order: 0,
                hours: '10 AM – 11 PM',
                menu_items: [],
            };
        }

        // ── Step 3: Handle closed restaurant ──
        if (restaurant._closed) {
            await msg.reply(`Sorry, *${restaurant.name}* is currently closed 🔴\nPlease try again during opening hours.`);
            return;
        }

        // ── Step 4: Update session with correct restaurant ──
        if (!sessions[customerPhone]) {
            sessions[customerPhone] = { history: [], restaurant };
        } else {
            sessions[customerPhone].restaurant = restaurant;
        }

        // ── Step 5: Get AI reply ──
        const reply = await chat(customerPhone, msg.body, restaurant);

        // ── Step 6: Send reply ──
        await msg.reply(reply);
        console.log(`✅ Replied to ${customerPhone}`);

    } catch (err) {
        console.error('❌ Message handler error:', err.message);
        console.error('❌ Stack:', err.stack);
        await msg.reply("Sorry, I had a small hiccup! Try again in a moment 😊").catch(() => { });
    }
});

client.on('disconnected', (reason) => {
    console.log('⚠️  Disconnected:', reason);
    process.exit(1);
});

client.on('auth_failure', (msg) => {
    console.error('❌ Auth failed:', msg);
    process.exit(1);
});

// ─── Start Bot ────────────────────────────────────────────────────────────────
console.log('🚀 Starting WhatsApp Restaurant Bot...');
client.initialize();

// ─── Internal HTTP Server ─────────────────────────────────────────────────────
// Laravel calls POST http://localhost:3000/send-message
// to send WhatsApp notifications to owner or customer
const internalServer = http.createServer(async (req, res) => {
    if (req.method === 'POST' && req.url === '/send-message') {
        let body = '';

        req.on('data', chunk => { body += chunk.toString(); });

        req.on('end', async () => {
            try {
                const { to, message } = JSON.parse(body);

                if (!to || !message) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, error: 'Missing to or message' }));
                    return;
                }

                const normalized = to.replace(/[^0-9]/g, '');
                const chatId = `${normalized}@c.us`;

                await client.sendMessage(chatId, message);
                console.log(`📤 Sent notification to ${normalized}`);

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true }));

            } catch (err) {
                console.error('❌ Send message error:', err.message);
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, error: err.message }));
            }
        });

    } else {
        res.writeHead(404);
        res.end('Not found');
    }
});

internalServer.listen(INTERNAL_PORT, () => {
    console.log(`🌐 Internal API running on port ${INTERNAL_PORT}`);
});