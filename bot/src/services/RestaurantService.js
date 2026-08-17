import axios from 'axios';

const LARAVEL_API  = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';
const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

/**
 * RestaurantService — fetches restaurant + menu + active deals from Laravel API.
 * Results are cached in memory per bot number to avoid a DB hit on every message.
 */
export class RestaurantService {
    constructor() {
        this.cache = new Map(); // normalizedBotNumber → { data, cachedAt }
    }

    /**
     * Get restaurant data for a given bot WhatsApp number.
     * Returns null if not found; returns { ...data, _closed: true } if restaurant is closed.
     */
    async getByBotNumber(botNumber) {
        const normalized = botNumber.replace(/[^0-9]/g, '');

        // Serve from cache if fresh
        const cached = this.cache.get(normalized);
        if (cached && Date.now() - cached.cachedAt < CACHE_TTL_MS) {
            return cached.data;
        }

        try {
            console.log(`🔍 Fetching restaurant for bot number: ${normalized}`);

            const res = await axios.get(
                `${LARAVEL_API}/restaurant-by-bot/${normalized}`,
                { timeout: 5000 }
            );

            const data = res.data;

            if (data.is_open === false) {
                console.log(`⚠️  Restaurant "${data.name}" is currently closed`);
                // Don't cache closed state — restaurant may open soon
                return { ...data, _closed: true };
            }

            const dealCount = data.active_deals?.length || 0;
            console.log(`✅ Restaurant: ${data.name} | ${data.menu_items?.length || 0} items | ${dealCount} active deals`);

            this.cache.set(normalized, { data, cachedAt: Date.now() });
            return data;

        } catch (err) {
            if (err.response?.status === 404) {
                console.log('⚠️  No restaurant registered for this bot number');
            } else {
                console.log(`⚠️  Could not fetch restaurant: ${err.message}`);
            }
            return null;
        }
    }

    /**
     * Force-clear the cache for a specific bot number (e.g. after menu update).
     */
    invalidate(botNumber) {
        this.cache.delete(botNumber.replace(/[^0-9]/g, ''));
    }
}
