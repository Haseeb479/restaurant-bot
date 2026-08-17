import { RestaurantService } from '../services/RestaurantService.js';
import { NotifyService } from '../services/NotifyService.js';

/**
 * Keywords that trigger a human handoff (English + Urdu + Roman Urdu).
 * Add more as you discover what your customers say.
 */
const ESCALATION_KEYWORDS = [
    // English
    'manager', 'human', 'real person', 'staff', 'support',
    'complaint', 'complain', 'wrong order', 'missing item',
    'refund', 'cancel my order', 'speak to someone', 'call me',
    // Urdu / Roman Urdu
    'shikayat', 'manager chahiye', 'insaan chahiye', 'banda chahiye',
    'ghalat order', 'paisa wapas', 'wapas karo', 'problem hai',
    'mushkil hai', 'manager se baat', 'kisi se baat',
];

/**
 * EscalationHandler — human handoff when customers need real help.
 * Fires when: customer asks for manager/human, complains, or uses escalation keywords.
 *
 * Action:
 *  1. Send a warm handoff reply to the customer
 *  2. Alert the restaurant owner/manager via WhatsApp
 */
export class EscalationHandler {
    constructor(client) {
        this.client      = client;
        this.restaurants = new RestaurantService();
        this.notifier    = new NotifyService(client);
    }

    /**
     * Static check — does this message contain escalation keywords?
     */
    static isEscalationRequest(text) {
        const lower = text.toLowerCase();
        return ESCALATION_KEYWORDS.some(kw => lower.includes(kw));
    }

    async handle(msg, customerPhone, botNumber) {
        console.log(`🚨 Escalation triggered for ${customerPhone}`);

        // Friendly handoff message to customer
        await msg.reply(
            `I understand — let me connect you with our team right away! 🙏\n\n` +
            `A staff member will reach out to you shortly. ` +
            `If it's urgent, please call us directly.`
        );

        // Fetch restaurant to find the right owner/manager number
        const restaurant  = await this.restaurants.getByBotNumber(botNumber);
        const targetPhone = restaurant?.manager_phone
            || restaurant?.owner_phone
            || process.env.OWNER_PHONE;

        if (targetPhone && restaurant) {
            await this.notifier.sendToPhone(
                targetPhone,
                `🚨 *Customer Needs Help — ${restaurant.name}*\n\n` +
                `📱 *Customer:* ${customerPhone}\n\n` +
                `The customer requested to speak with a human or filed a complaint. ` +
                `Please contact them directly as soon as possible.`
            );
            console.log(`📡 Escalation alert sent to: ${targetPhone}`);
        }
    }
}
