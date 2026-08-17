import fs from 'fs';
import path from 'path';
import whatsappWebPkg from 'whatsapp-web.js';
import { SessionManager } from '../services/SessionManager.js';
import { RestaurantService } from '../services/RestaurantService.js';
import { OrderService } from '../services/OrderService.js';
import { NotifyService } from '../services/NotifyService.js';
import { GroqClient } from '../ai/GroqClient.js';
import { PromptBuilder } from '../ai/PromptBuilder.js';

const { MessageMedia } = whatsappWebPkg;

// Image extensions that should be sent as images (not documents)
const IMAGE_EXTS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.gif', '.bmp', '.jfif', '.jpe']);

/**
 * ChatHandler — AI-powered conversation handler.
 *
 * Flow for every message:
 *  1. Load restaurant + menu + deals (isolated per restaurant)
 *  2. Get/create in-memory session for this customer (scoped to restaurantId)
 *  3. Build message array and call Groq AI
 *  4. Send AI reply to customer
 *  5. If AI reply contains order confirmation → save to DB + send tracking code
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

        // ── Check if customer asked for menu & image/document exists ───────────
        const isMenuRequest = /menu|dikhao|prices|kya hai|list|card|items|منو|مینو|pdf|sheet|flyer|photo|document/i.test(text);
        let sentMedia = false;

        const menuFilePath = restaurant?.menu_file || restaurant?.menu_image;
        if (isMenuRequest && menuFilePath) {
            try {
                let resolvedPath = menuFilePath;
                if (!path.isAbsolute(resolvedPath) && !resolvedPath.startsWith('http')) {
                    resolvedPath = path.resolve(process.cwd(), 'public', resolvedPath.replace(/^\//, ''));
                }

                if (fs.existsSync(resolvedPath)) {
                    const ext = path.extname(resolvedPath).toLowerCase();
                    const media = MessageMedia.fromFilePath(resolvedPath);

                    if (IMAGE_EXTS.has(ext)) {
                        // Force correct MIME type so it shows as a proper image, not a document
                        media.mimetype = 'image/jpeg';
                        media.filename = undefined; // Remove filename so WA treats it as photo
                        await this.client.sendMessage(`${customerPhone}@c.us`, media, {
                            caption: `📋 *${restaurant.name} Menu*`
                        });
                    } else {
                        // PDF / Excel / Doc — send as document with filename
                        const fileTitle = restaurant?.menu_file_name || `${restaurant.name} Menu`;
                        await msg.reply(media, undefined, { caption: `📋 *${fileTitle}*` });
                    }

                    sentMedia = true;
                    console.log(`📎 Sent menu (${ext}) to ${customerPhone}`);
                }
            } catch (err) {
                console.log('⚠️ Could not send menu file:', err.message);
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
