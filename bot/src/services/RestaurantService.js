import axios from 'axios';

const LARAVEL_API  = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';
const CACHE_TTL_MS = 10 * 60 * 1000; // 10 minutes

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
    async getByBotNumber(botNumber, retries = 2) {
        const normalized = botNumber.replace(/[^0-9]/g, '');

        // Serve from cache if fresh
        const cached = this.cache.get(normalized);
        if (cached && Date.now() - cached.cachedAt < CACHE_TTL_MS) {
            return cached.data;
        }

        for (let attempt = 1; attempt <= retries + 1; attempt++) {
            try {
                console.log(`🔍 Fetching restaurant for bot number: ${normalized} (attempt ${attempt})`);

                const res = await axios.get(
                    `${LARAVEL_API}/restaurant-by-bot/${normalized}`,
                    { timeout: 8000 }
                );

                const data = res.data;

                if (data.is_open === false) {
                    console.log(`⚠️  Restaurant "${data.name}" is currently closed`);
                    return { ...data, _closed: true };
                }

                const dealCount = data.active_deals?.length || 0;
                console.log(`✅ Restaurant: ${data.name} | ${data.menu_items?.length || 0} items | ${dealCount} active deals`);

                this.cache.set(normalized, { data, cachedAt: Date.now() });
                return data;

            } catch (err) {
                if (err.response?.status === 404) {
                    console.log(`⚠️  No restaurant registered for bot number: ${normalized}`);
                    return null;
                }

                console.warn(`⚠️  Restaurant fetch attempt ${attempt} failed: ${err.message}`);
                if (attempt <= retries) {
                    await new Promise(r => setTimeout(r, 1000));
                }
            }
        }

        return null;
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
     * Force-clear the cache for a specific bot number (e.g. after menu update).
     */
    invalidate(botNumber) {
        this.cache.delete(botNumber.replace(/[^0-9]/g, ''));
    }
}
