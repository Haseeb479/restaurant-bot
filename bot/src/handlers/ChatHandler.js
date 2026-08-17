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

/**
 * ChatHandler — AI-powered conversation handler.
 *
 * Flow for every message:
 *  1. Load restaurant + menu + deals
 *  2. Get/create in-memory session for this customer
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
        // ── Load restaurant ────────────────────────────────────────────────────
        let restaurant = await this.restaurants.getByBotNumber(botNumber);

        // Graceful default if no restaurant found in DB
        if (!restaurant) {
            console.log('⚠️  No restaurant found — using defaults');
            restaurant = {
                id: 1, name: 'Our Restaurant', address: 'City Center',
                delivery_charge: 50, minimum_order: 0,
                hours: '10 AM – 11 PM', menu_items: [], active_deals: [],
            };
        }

        // Restaurant is closed
        if (restaurant._closed) {
            await msg.reply(
                `Sorry, *${restaurant.name}* is currently closed 🔴\n` +
                `Please try again during opening hours: ${restaurant.hours || 'check back soon'}.`
            );
            return;
        }

        // ── Session ────────────────────────────────────────────────────────────
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
            reply = this.fallback(text);
        } else {
            session.history.push({ role: 'assistant', content: reply });
            this.sessions.trim(customerPhone);
        }

        // ── Check if customer asked for menu & visual flyer exists ─────────────
        const isMenuRequest = /menu|dikhao|prices|kya hai|list|card|items|منو|مینو/i.test(text);
        let sentImage = false;

        if (isMenuRequest && restaurant?.menu_image) {
            try {
                // If it's a relative path from public/
                let imagePath = restaurant.menu_image;
                if (!path.isAbsolute(imagePath) && !imagePath.startsWith('http')) {
                    imagePath = path.resolve(process.cwd(), 'public', imagePath.replace(/^\//, ''));
                }

                if (fs.existsSync(imagePath)) {
                    const media = MessageMedia.fromFilePath(imagePath);
                    await msg.reply(media, undefined, { caption: `📋 *${restaurant.name} Menu*` });
                    sentImage = true;
                    console.log(`🖼️ Sent visual menu flyer to ${customerPhone}`);
                }
            } catch (err) {
                console.log('⚠️ Could not send menu flyer image:', err.message);
            }
        }

        // ── Send reply ─────────────────────────────────────────────────────────
        if (!sentImage || reply.length > 50) {
            await msg.reply(reply);
        }
        console.log(`✅ Replied to ${customerPhone}`);

        // ── Order confirmed? ───────────────────────────────────────────────────
        if (this.isOrderConfirmed(reply)) {
            console.log(`🎯 Order confirmed for ${customerPhone}`);
            const trackingCode = await this.orders.save(customerPhone, session);

            if (trackingCode) {
                // Send tracking code as a separate follow-up message
                const trackingMsg =
                    `🎉 *Your tracking code is: ${trackingCode}*\n\n` +
                    `Send this code anytime to check your order status!`;

                await this.client
                    .sendMessage(`${customerPhone}@c.us`, trackingMsg)
                    .catch(() => {});

                // Notify restaurant owner/manager
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

    // ── Fallback when AI is unavailable ───────────────────────────────────────
    fallback(text) {
        const m = text.toLowerCase();
        if (/hi|hello|hey|salam|سلام|assalam/.test(m))
            return "Hey! Welcome 👋 What can I get for you today?";
        if (/menu|kya hai|what.*have|منو|مینو/.test(m))
            return "Here's our menu 📋\n🥤 Mango Juice – M:Rs.150 / L:Rs.250\n🥤 Orange Juice – M:Rs.150 / L:Rs.250\n💧 Water – Rs.50\n\nWhat would you like? 😊";
        if (/order|chahiye|چاہیے|want/.test(m))
            return "Sure! Tell me what you'd like and your delivery address 🙂";
        if (/track|tracking/.test(m))
            return "Please send your tracking code and I'll check your order status!";
        return "Hey! I'm here to help 😊 What would you like today?";
    }
}
