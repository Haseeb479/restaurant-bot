import { getRestaurantDirectFromDb } from './Database.js';

const CACHE_TTL_MS = 5 * 1000; // 5 seconds for instant menu stock availability updates

/**
 * RestaurantService — fetches restaurant + menu + active deals.
 *
 * MySQL is the only source. There used to be an HTTP fallback to
 * `{LARAVEL_API}/restaurant-by-bot/{number}`, but routes/api.php is deliberately
 * not registered in bootstrap/app.php (finding H-05), so that request could only
 * ever 404 — it made a DB outage look like "no restaurant registered for this
 * number", which sent the bot into its unlinked-number script instead of saying
 * anything true.
 */
export class RestaurantService {
    constructor() {
        this.cache = new Map(); // normalizedBotNumber → { data, cachedAt }
    }

    /**
     * Get restaurant data for a given bot WhatsApp number.
     */
    async getByBotNumber(botNumber) {
        const normalized = botNumber.replace(/[^0-9]/g, '');

        // 1. Serve from in-memory cache if fresh
        const cached = this.cache.get(normalized);
        if (cached && Date.now() - cached.cachedAt < CACHE_TTL_MS) {
            return cached.data;
        }

        // 2. Direct MySQL Query (Instant <2ms, never blocks, 100% reliable)
        try {
            const dbData = await getRestaurantDirectFromDb(normalized);
            if (dbData) {
                console.log(`✅ Restaurant (Direct DB): ${dbData.name} | ${dbData.menu_items?.length || 0} items | ${dbData.active_deals?.length || 0} active deals`);
                this.cache.set(normalized, { data: dbData, cachedAt: Date.now() });
                return dbData;
            }

            console.log(`⚠️  No restaurant registered for bot number: ${normalized}`);
            return null;
        } catch (dbErr) {
            // A database error is NOT the same as an unregistered number, and the
            // caller must be able to tell them apart — one is a config problem the
            // owner can fix, the other is an outage.
            console.error('❌ Restaurant lookup failed (database):', dbErr.message);
            throw dbErr;
        }
    }

    /**
     * Pre-warm restaurant cache immediately when bot starts
     */
    async preload(botNumber) {
        if (!botNumber) return;
        try {
            await this.getByBotNumber(botNumber);
        } catch (e) {}
    }

    /**
     * Force-clear the cache for a specific bot number.
     */
    invalidate(botNumber) {
        this.cache.delete(botNumber.replace(/[^0-9]/g, ''));
    }
}
