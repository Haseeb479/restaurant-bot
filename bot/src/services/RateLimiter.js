/**
 * RateLimiter — protects the bot and Groq AI from message spam, rapid bursts, and abuse.
 *
 * Rules:
 *  - Burst protection: If user sends multiple messages within 1.2s, wait or debounce.
 *  - Per-minute limit: Max 12 messages per 60 seconds per phone number.
 *  - Warning cooldown: Don't spam the user with rate-limit warnings.
 */
export class RateLimiter {
    constructor(maxPerMinute = 12, burstWindowMs = 1200) {
        this.maxPerMinute   = maxPerMinute;
        this.burstWindowMs  = burstWindowMs;
        this.messageHistory = new Map(); // phone -> Array<timestamp>
        this.lastWarning    = new Map(); // phone -> timestamp
    }

    /**
     * Check if a message from customerPhone should be allowed.
     * @param {string} phone
     * @returns {{ allowed: boolean, reason?: 'burst' | 'limit', waitSecs?: number }}
     */
    check(phone) {
        const now = Date.now();
        const timestamps = this.messageHistory.get(phone) || [];

        // Clean timestamps older than 60 seconds
        const recent = timestamps.filter(t => now - t < 60000);
        this.messageHistory.set(phone, recent);

        // 1. Check Burst (message sent too rapidly after previous message)
        if (recent.length > 0) {
            const lastMsgTime = recent[recent.length - 1];
            if (now - lastMsgTime < this.burstWindowMs) {
                // Return burst notice (will be processed on the next turn)
                return { allowed: false, reason: 'burst' };
            }
        }

        // 2. Check Minute Limit
        if (recent.length >= this.maxPerMinute) {
            const oldest = recent[0];
            const waitSecs = Math.ceil((60000 - (now - oldest)) / 1000);
            return { allowed: false, reason: 'limit', waitSecs };
        }

        // Allowed - record this message
        recent.push(now);
        this.messageHistory.set(phone, recent);
        return { allowed: true };
    }

    /**
     * Check if we should send a rate-limit warning to the user (max 1 warning per 30s)
     */
    shouldSendWarning(phone) {
        const now = Date.now();
        const last = this.lastWarning.get(phone) || 0;
        if (now - last > 30000) {
            this.lastWarning.set(phone, now);
            return true;
        }
        return false;
    }

    /**
     * Clean stale data every 10 minutes
     */
    cleanup() {
        const now = Date.now();
        for (const [phone, times] of this.messageHistory) {
            const recent = times.filter(t => now - t < 60000);
            if (recent.length === 0) {
                this.messageHistory.delete(phone);
                this.lastWarning.delete(phone);
            } else {
                this.messageHistory.set(phone, recent);
            }
        }
    }
}

export const rateLimiter = new RateLimiter();
setInterval(() => rateLimiter.cleanup(), 10 * 60 * 1000);
