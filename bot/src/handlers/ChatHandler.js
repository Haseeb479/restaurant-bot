import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import whatsappWebPkg from 'whatsapp-web.js';
import { sessionManager } from '../services/SessionManager.js';
import { RestaurantService } from '../services/RestaurantService.js';
import { OrderService } from '../services/OrderService.js';
import { NotifyService } from '../services/NotifyService.js';
import { GroqClient } from '../ai/GroqClient.js';
import { PromptBuilder } from '../ai/PromptBuilder.js';
import { menuOcr } from '../ai/MenuOcrService.js';
import { excelMenu } from '../services/ExcelMenuService.js';
import { Logger } from '../services/Logger.js';
import { sendWhatsAppText } from '../utils/WhatsAppSender.js';

const { MessageMedia } = whatsappWebPkg;

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);
// Laravel public folder is two levels up from bot/src/handlers/
const LARAVEL_PUBLIC = path.resolve(__dirname, '..', '..', '..', 'public');

// Image extensions that should be sent as images (not documents)
const IMAGE_EXTS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.gif', '.bmp', '.jfif', '.jpe']);
const EXCEL_EXTS = new Set(['.xlsx', '.xls', '.csv', '.tsv', '.txt']);

/**
 * Helper to find the latest menu files (image and/or excel) on disk for a restaurant
 */
export function findRestaurantMenuFiles(restaurantId, dbMenuFile, dbMenuImage) {
    let imagePath = null;
    let excelPath = null;

    const checkFile = (p) => {
        if (!p) return null;
        if (typeof p !== 'string') return null;
        if (path.isAbsolute(p)) return fs.existsSync(p) ? p : null;
        if (p.startsWith('http')) return null;

        const clean = p.replace(/^\//, '');
        const filename = path.basename(clean);

        const candidates = [
            path.join(LARAVEL_PUBLIC, clean),
            path.join(LARAVEL_PUBLIC, 'menus', filename),
            path.join(LARAVEL_PUBLIC, 'uploads', 'menus', filename),
            path.join(LARAVEL_PUBLIC, 'uploads', filename),
            path.join(LARAVEL_PUBLIC, filename),
        ];

        for (const cand of candidates) {
            if (fs.existsSync(cand)) {
                return cand;
            }
        }
        return null;
    };

    // 1. Check direct DB columns
    const resolvedImage = checkFile(dbMenuImage);
    const resolvedFile  = checkFile(dbMenuFile);

    if (resolvedImage) {
        const ext = path.extname(resolvedImage).toLowerCase();
        if (IMAGE_EXTS.has(ext)) imagePath = resolvedImage;
        else if (EXCEL_EXTS.has(ext)) excelPath = resolvedImage;
    }

    if (resolvedFile) {
        const ext = path.extname(resolvedFile).toLowerCase();
        if (IMAGE_EXTS.has(ext) && !imagePath) imagePath = resolvedFile;
        else if (EXCEL_EXTS.has(ext)) excelPath = resolvedFile;
    }

    // 2. Scan menus and uploads directories if missing either image or excel
    const scanDirs = [
        path.join(LARAVEL_PUBLIC, 'menus'),
        path.join(LARAVEL_PUBLIC, 'uploads', 'menus'),
        path.join(LARAVEL_PUBLIC, 'uploads'),
    ];

    const prefix = `menu_${restaurantId}_`;
    for (const dir of scanDirs) {
        if ((imagePath && excelPath) || !fs.existsSync(dir)) continue;
        try {
            const files = fs.readdirSync(dir);
            for (const file of files) {
                if (file.startsWith(prefix)) {
                    const fullPath = path.join(dir, file);
                    const ext = path.extname(file).toLowerCase();

                    if (IMAGE_EXTS.has(ext) && !imagePath) {
                        imagePath = fullPath;
                    } else if (EXCEL_EXTS.has(ext) && !excelPath) {
                        excelPath = fullPath;
                    }
                }
            }
        } catch (e) {
            console.warn(`⚠️ Could not scan directory ${dir}:`, e.message);
        }
    }

    return { imagePath, excelPath, genericFile: resolvedFile };
}

/**
 * ChatHandler — AI-powered conversation handler.
 */
export class ChatHandler {
    constructor(client) {
        this.client      = client;
        // Shared across handlers + across bot reconnects (see SessionManager).
        this.sessions    = sessionManager;
        this.restaurants = new RestaurantService();
        this.orders      = new OrderService();
        this.notifier    = new NotifyService(client);
        this.groq        = new GroqClient();
    }

    async handle(msg, customerPhone, botNumber, text, locationCoords = null) {
        // ── Load restaurant by bot's own WhatsApp number ───────────────────────
        if (!botNumber) {
            console.log('⚠️  botNumber not available yet — bot may still be initializing');
            await msg.reply("I'm just starting up 🔄 Please send your message again in a few seconds!");
            return;
        }

        console.log(`🔍 Looking up restaurant for bot number: ${botNumber}`);

        let restaurant;
        try {
            restaurant = await this.restaurants.getByBotNumber(botNumber);
        } catch (lookupErr) {
            console.error(`❌ Restaurant lookup unavailable for ${botNumber}:`, lookupErr.message);
            await msg.reply("⚠️ We're having a brief technical problem. Please send your message again in a moment!");
            return;
        }

        if (!restaurant) {
            console.log(`❌ No restaurant found for bot number: ${botNumber}`);
            await msg.reply(
                "⚠️ This WhatsApp number is not yet linked to a restaurant.\n\n" +
                "Please ask the restaurant admin to verify the registration."
            );
            return;
        }

        // Restaurant is closed
        if (restaurant._closed) {
            await msg.reply(
                `Sorry, *${restaurant.name}* is currently closed 🔴\n` +
                `Please try again during opening hours: ${restaurant.hours || 'check back soon'}.`
            );
            return;
        }

        // ── Locate Menu Files (Image for customer, Excel for calculation) ───────
        const { imagePath, excelPath, genericFile } = findRestaurantMenuFiles(
            restaurant.id,
            restaurant.menu_file,
            restaurant.menu_image
        );

        // 1. Try reading Excel Sheet for exact prices & calculation
        if (excelPath && !restaurant.menu_excel_text) {
            const parsedExcel = excelMenu.parseExcel(restaurant.id, excelPath);
            if (parsedExcel) {
                restaurant.menu_excel_text = parsedExcel.menuText;
                restaurant.menu_excel_items = parsedExcel.items;
                if (!restaurant.menu_items || restaurant.menu_items.length === 0) {
                    restaurant.menu_items = parsedExcel.items;
                }
                console.log(`📊 Injected ${parsedExcel.items.length} items from Excel sheet for ${restaurant.name}`);
            }
        }

        // 2. If no Excel sheet and no DB items, fallback to Image OCR
        if (!restaurant.menu_excel_text && !restaurant?.menu_items?.length && !restaurant.menu_ocr_text && imagePath) {
            const ocrText = await menuOcr.extractMenu(restaurant.id, imagePath);
            if (ocrText) {
                restaurant.menu_ocr_text = ocrText;
                console.log(`🧾 Menu OCR injected for ${restaurant.name}`);
            }
        }

        // ── Session — isolated per restaurant+customer ─────────────────────────
        const session = this.sessions.getOrCreate(customerPhone, restaurant);

        if (locationCoords) {
            session.deliveryLat = locationCoords.lat;
            session.deliveryLng = locationCoords.lng;
            session.locationSource = 'whatsapp_pin';
            session.deliveryLocationConfirmed = true;
            let addressStr = locationCoords.name || locationCoords.address || '';
            if (!addressStr) {
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${locationCoords.lat}&lon=${locationCoords.lng}&zoom=18&addressdetails=1`, { headers: { 'User-Agent': 'Foodio-RestaurantBot/1.0' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.display_name) {
                            const parts = data.display_name.split(',').map(p => p.trim());
                            addressStr = parts.slice(0, Math.min(parts.length, 4)).join(', ');
                        }
                    }
                } catch (e) {
                    console.error('Reverse geocoding failed:', e.message);
                }
            }
            if (addressStr) {
                session.deliveryAddress = addressStr;
            }

            const hasItemsInHistory = this.hasFoodItemsInHistory(session.history);
            if (hasItemsInHistory) {
                // Customer already chose food items! Let execution continue straight into Groq AI
                // to immediately generate the complete itemized Order Summary with COD default.
                text = `📍 [Customer shared location pin: ${addressStr || 'Pinned Location'} (Coordinates: ${locationCoords.lat}, ${locationCoords.lng})]`;
            } else {
                const ackMsg = `📍 *Shukriya! Aapki exact delivery location pin receive ho gayi hai!* ✅\n\nAb barah-e-karam batayein aap kya order karna pasand karein ge? 🍔\n_(Menu dekhne ke liye *Menu* likhein 😊)_`;
                session.history.push({ role: 'user', content: `Shared GPS Pin: [Lat: ${locationCoords.lat}, Lng: ${locationCoords.lng}] Address: ${addressStr}` });
                session.history.push({ role: 'assistant', content: ackMsg });
                this.sessions.trim(customerPhone, restaurant.id);
                await msg.reply(ackMsg);
                return;
            }
        }

        // ── Build AI messages ──────────────────────────────────────────────────
        const systemPrompt = PromptBuilder.build(session.restaurant, session);
        session.history.push({ role: 'user', content: text });

        const messages = [
            { role: 'system', content: systemPrompt },
            ...session.history,
        ];

        // ── Call Groq ──────────────────────────────────────────────────────────
        let reply = await this.groq.chat(customerPhone, messages);

        if (!reply) {
            // AI unavailable — remove the user message we couldn't respond to
            session.history.pop();
            reply = this.fallback(text, restaurant);
        } else {
            if (locationCoords && !reply.includes('Location Pin Received') && !reply.includes('Location pin')) {
                reply = `📍 *Location Pin Received!* ✅\n\n` + reply;
            }
            session.history.push({ role: 'assistant', content: reply });
            this.sessions.trim(customerPhone, restaurant.id);
        }

        // ── Send Menu Picture / Document to Customer ───────────────────────────
        const isMenuRequest = /menu|dikhao|prices|kya hai|list|card|items|منو|مینو|pdf|sheet|flyer|photo|document|picture/i.test(text);
        let sentMedia = false;

        // When sending to customer, prioritize the visual Image (JPG/PNG)
        const fileToSend = imagePath || (genericFile && !EXCEL_EXTS.has(path.extname(genericFile).toLowerCase()) ? genericFile : null);

        if (isMenuRequest && fileToSend && fs.existsSync(fileToSend)) {
            try {
                const ext = path.extname(fileToSend).toLowerCase();
                const media = MessageMedia.fromFilePath(fileToSend);

                if (IMAGE_EXTS.has(ext)) {
                    // Force image/jpeg so WhatsApp renders it as a photo (not a document)
                    // Use msg.reply() — avoids "No LID for user" error
                    media.mimetype = 'image/jpeg';
                    media.filename = undefined;
                    await msg.reply(media, undefined, {
                        caption: `📋 *${restaurant.name} Menu*`
                    });
                } else {
                    // PDF / Document — send with title
                    const fileTitle = restaurant?.menu_file_name || `${restaurant.name} Menu`;
                    await msg.reply(media, undefined, { caption: `📋 *${fileTitle}*` });
                }

                sentMedia = true;
                console.log(`📎 Sent menu photo (${ext}) to ${customerPhone}`);
            } catch (err) {
                console.error('❌ Could not send menu file:', err.message);
            }
        }

        // ── Order confirmed detection & gated fulfillment ─────────────────────
        if (this.isOrderConfirmed(reply, session.history)) {
            console.log(`🎯 Order confirmed detection triggered for ${customerPhone}`);
            let saveResult = null;
            try {
                // Validate cart and save to database BEFORE sending confirmation to the customer
                saveResult = await this.orders.save(customerPhone, session);
            } catch (err) {
                console.error(`❌ Error saving order for ${customerPhone}:`, err.message);
                saveResult = null;
            }

            const trackingCode = (typeof saveResult === 'object' && saveResult?.trackingCode)
                ? saveResult.trackingCode
                : (saveResult ? String(saveResult) : null);
            const savedOrder = (typeof saveResult === 'object' && saveResult?.order)
                ? saveResult.order
                : (session.lastOrder || null);

            if (trackingCode) {
                // Cart validated and order saved successfully!
                // Only now send the confirmation message to the customer.
                let confirmationText = reply;
                if (savedOrder && savedOrder.total > 0) {
                    confirmationText = this.harmonizeConfirmationBill(reply, savedOrder);
                }

                await msg.reply(confirmationText);
                console.log(`✅ Replied with order confirmation to ${customerPhone}`);

                const trackingMsg =
                    `🎉 *Your tracking code is: ${trackingCode}*\n\n` +
                    `Send this code anytime to check your order status!`;

                await msg.reply(trackingMsg).catch(() => sendWhatsAppText(this.client, customerPhone, trackingMsg));

                const ownerNotified = await this.notifier.notifyOwner(customerPhone, session, trackingCode);
                if (ownerNotified) {
                    await this.orders.markOwnerNotified(trackingCode);
                }
                Logger.info('Order saved & notified', { customerPhone, trackingCode, restaurantId: restaurant.id, ownerNotified });
                Logger.logToDb(restaurant.id, customerPhone, text, reply, 'order_confirmed');
            } else {
                // The order was NOT placed because cart validation or saving failed.
                // Do NOT send the AI's confirmation text. Instead inform customer.
                const failMsg =
                    `⚠️ Sorry — something went wrong saving your order, so it has *not* been placed.\n\n` +
                    `Please send your order again in a moment, or contact us directly.`;

                await msg.reply(failMsg).catch(() => sendWhatsAppText(this.client, customerPhone, failMsg));

                console.error(`❌ Order for ${customerPhone} was NOT saved — customer informed.`);
                Logger.error('Order save failed', { customerPhone, restaurantId: restaurant.id });
                Logger.logToDb(restaurant.id, customerPhone, text, failMsg, 'order_failed');
            }
        } else {
            // ── Send normal text reply ─────────────────────────────────────────
            if (!sentMedia || reply.length > 50) {
                await msg.reply(reply);
            }
            console.log(`✅ Replied to ${customerPhone}`);

            // ── Structured Logging to File & Database for Owner Review ─────────
            Logger.info('Chat reply sent', { customerPhone, restaurantId: restaurant.id, replyLength: reply.length });
            Logger.logToDb(restaurant.id, customerPhone, text, reply, 'chat');
        }
    }

    // ── Order confirmation detection (strict check) ────────────────────────────
    isOrderConfirmed(reply, history = []) {
        const lower = reply.toLowerCase();

        // If it's still asking the user to confirm, it's NOT yet placed
        if (lower.includes('confirm kar doon') || lower.includes('shall i place') || lower.includes('kya main aapka order confirm')) {
            return false;
        }

        const replyHasPlaced = (
            lower.includes('your order is placed') ||
            lower.includes('order has been placed') ||
            lower.includes('order placed')          ||
            lower.includes('آرڈر ہو گیا')            ||
            lower.includes('آرڈر ہوگیا')             ||
            (lower.includes('total') && lower.includes('placed'))
        );

        if (!replyHasPlaced) {
            return false;
        }

        // DETERMINISTIC CUSTOMER CONFIRMATION:
        // Never place order purely on AI text alone if history is available.
        // Check that customer sent an explicit confirmation affirmative (e.g. yes, confirm, haan)
        // or that there is an order summary in the conversation.
        if (Array.isArray(history) && history.length > 0) {
            const userMsgs = history.filter(h => h.role === 'user');
            const lastUserMsg = userMsgs.length > 0 ? (userMsgs[userMsgs.length - 1].content || '').toLowerCase() : '';
            const isAffirmative = /^(?:ha|haa|haan|yes|yep|yeah|ok|theek hai|thk hai|kr do|kar do|confirm|done|jee|ji)\b/i.test(lastUserMsg.trim())
                || /confirm|kar do|kr do|bhej do|place|order/i.test(lastUserMsg);

            const hasSummary = history.some(h => h.role === 'assistant' && (/order summary|subtotal|deliver to|total payable/i.test(h.content || '')));
            
            // If we have history, ensure it's either an affirmative user response or has order summary
            if (!isAffirmative && !hasSummary) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if customer conversation history already contains selected or discussed food items.
     */
    hasFoodItemsInHistory(history = []) {
        if (!Array.isArray(history) || history.length === 0) return false;
        const allText = history.map(h => h.content || '').join(' ').toLowerCase();
        return (
            /\b(?:\d+\s*x|\d+\s*(?:burger|pizza|biryani|roll|deal|half|full|plate|bottle|piece|paratha|naan|karahi|tikka|wrap|fries|drink|pepsi|coke|sprite))\b/i.test(allText) ||
            /\b(?:chahiye|mangwana|pack|bhej do|order karna|order krna)\b/i.test(allText) ||
            allText.includes('subtotal') ||
            allText.includes('order summary')
        );
    }

    /**
     * Reconciles the confirmation message so totals and item line prices
     * strictly match the backend database calculation.
     */
    harmonizeConfirmationBill(reply, order) {
        if (!order || !order.total) return reply;

        let harmonized = reply;
        const authTotal = Number(order.total);
        const authSubtotal = Number(order.subtotal);
        const authDelivery = Number(order.deliveryCharge);

        // 1. Reconcile Grand Total
        harmonized = harmonized.replace(
            /(total(?:\s*payable)?\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/gi,
            `$1${authTotal}$3`
        );

        // 2. Reconcile Subtotal
        harmonized = harmonized.replace(
            /(subtotal\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/gi,
            `$1${authSubtotal}$3`
        );

        // 3. Reconcile Delivery Fee
        harmonized = harmonized.replace(
            /(delivery(?:\s*charge|\s*fee)?\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/gi,
            `$1${authDelivery}$3`
        );

        // 4. Reconcile item lines if present in text
        if (Array.isArray(order.items)) {
            for (const item of order.items) {
                if (item.name && item.subtotal !== undefined) {
                    const escName = item.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const itemRe = new RegExp(`(${item.quantity || '\\d+'}\\s*[xX×]\\s*${escName}[^\\n\\r]*?(?:rs\\.?\\s*))([0-9,]+(?:\\.[0-9]{1,2})?)`, 'i');
                    harmonized = harmonized.replace(itemRe, `$1${item.subtotal}`);
                }
            }
        }

        // If the authoritative total is somehow not present in the reply, append authoritative summary
        if (!harmonized.includes(`Rs.${authTotal}`) && !harmonized.includes(`Rs. ${authTotal}`)) {
            harmonized += `\n\n🧾 *Bill Details:*\nSubtotal: Rs.${authSubtotal}\nDelivery: Rs.${authDelivery}\n*Total: Rs.${authTotal}*`;
        }

        return harmonized;
    }

    // ── Fallback when AI is unavailable — uses THIS restaurant's name ──────────
    fallback(text, restaurant) {
        const name = restaurant?.name || 'our restaurant';
        const m    = text.toLowerCase();

        if (/hi|hello|hey|salam|سلام|assalam/.test(m))
            return `Hey! Welcome to *${name}* 👋 How can I help you today?`;
        if (/menu|kya hai|what.*have|منو|مینو/.test(m))
            return `Please type *menu* to see today's available items at *${name}* 📋`;
        if (/order|chahiye|چاہیے|want/.test(m))
            return `Sure! Tell me what you'd like from *${name}* and your delivery address 🙂`;
        if (/track|tracking/.test(m))
            return `Please send your tracking code and I'll check your order status!`;
        return `Hey! I'm here to help with *${name}* 😊 What would you like today? (You can type *menu* to see our items!)`;
    }
}
