import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import whatsappWebPkg from 'whatsapp-web.js';
import { SessionManager } from '../services/SessionManager.js';
import { RestaurantService } from '../services/RestaurantService.js';
import { OrderService } from '../services/OrderService.js';
import { NotifyService } from '../services/NotifyService.js';
import { GroqClient } from '../ai/GroqClient.js';
import { PromptBuilder } from '../ai/PromptBuilder.js';
import { menuOcr } from '../ai/MenuOcrService.js';
import { excelMenu } from '../services/ExcelMenuService.js';

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
function findRestaurantMenuFiles(restaurantId, dbMenuFile, dbMenuImage) {
    let imagePath = null;
    let excelPath = null;

    const resolvePath = (p) => {
        if (!p) return null;
        if (path.isAbsolute(p) || p.startsWith('http')) return p;
        return path.join(LARAVEL_PUBLIC, p.replace(/^\//, ''));
    };

    const checkFile = (p) => {
        if (!p) return null;
        const resolved = resolvePath(p);
        return (resolved && fs.existsSync(resolved)) ? resolved : null;
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

    // 2. Scan uploads/menus directory if missing either image or excel
    const menusDir = path.join(LARAVEL_PUBLIC, 'uploads', 'menus');
    if (fs.existsSync(menusDir)) {
        try {
            const files = fs.readdirSync(menusDir);
            const prefix = `menu_${restaurantId}_`;

            for (const file of files) {
                if (file.startsWith(prefix)) {
                    const fullPath = path.join(menusDir, file);
                    const ext = path.extname(file).toLowerCase();

                    if (IMAGE_EXTS.has(ext) && !imagePath) {
                        imagePath = fullPath;
                    } else if (EXCEL_EXTS.has(ext) && !excelPath) {
                        excelPath = fullPath;
                    }
                }
            }
        } catch (e) {
            console.warn('⚠️ Could not scan menus directory:', e.message);
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
        this.sessions    = new SessionManager();
        this.restaurants = new RestaurantService();
        this.orders      = new OrderService();
        this.notifier    = new NotifyService(client);
        this.groq        = new GroqClient();
    }

    async handle(msg, customerPhone, botNumber, text) {
        // ── Load restaurant by bot's own WhatsApp number ───────────────────────
        if (!botNumber) {
            console.log('⚠️  botNumber not available yet — bot may still be initializing');
            await msg.reply("I'm just starting up 🔄 Please send your message again in a few seconds!");
            return;
        }

        console.log(`🔍 Looking up restaurant for bot number: ${botNumber}`);
        let restaurant = await this.restaurants.getByBotNumber(botNumber);

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

        // ── Build AI messages ──────────────────────────────────────────────────
        const systemPrompt = PromptBuilder.build(session.restaurant);
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

        // ── Send text reply ────────────────────────────────────────────────────
        if (!sentMedia || reply.length > 50) {
            await msg.reply(reply);
        }
        console.log(`✅ Replied to ${customerPhone}`);

        // ── Order confirmed? ───────────────────────────────────────────────────
        if (this.isOrderConfirmed(reply)) {
            console.log(`🎯 Order confirmed for ${customerPhone}`);
            const trackingCode = await this.orders.save(customerPhone, session);

            if (trackingCode) {
                const trackingMsg =
                    `🎉 *Your tracking code is: ${trackingCode}*\n\n` +
                    `Send this code anytime to check your order status!`;

                await this.client
                    .sendMessage(`${customerPhone}@c.us`, trackingMsg)
                    .catch(() => {});

                await this.notifier.notifyOwner(customerPhone, session, trackingCode);
            }
        }
    }

    // ── Order confirmation detection ───────────────────────────────────────────
    isOrderConfirmed(reply) {
        const lower = reply.toLowerCase();
        return (
            lower.includes('your order is placed') ||
            lower.includes('order placed')          ||
            lower.includes('آرڈر ہو گیا')            ||
            lower.includes('آرڈر ہوگیا')             ||
            (lower.includes('total') && lower.includes('placed'))
        );
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
        return `Hey! I'm here to help with *${name}* 😊 What would you like today?`;
    }
}
