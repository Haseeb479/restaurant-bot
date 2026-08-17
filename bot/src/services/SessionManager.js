const SESSION_TTL_MS = 2 * 60 * 60 * 1000; // 2 hours idle before cleanup
const MAX_HISTORY    = 30;                   // max messages kept per session

/**
 * SessionManager — in-memory conversation sessions, fully isolated per restaurant.
 *
 * Key format: `${restaurantId}:${phone}` — so the same customer texting
 * two different restaurant bots gets completely separate conversation histories.
 */
export class SessionManager {
    constructor() {
        this.sessions = new Map(); // `restaurantId:phone` → { history, restaurant, lastActive }
        setInterval(() => this.cleanup(), 15 * 60 * 1000);
    }

    /**
     * Build session key — always scoped to a specific restaurant.
     */
    _key(phone, restaurantId) {
        return `${restaurantId}:${phone}`;
    }

    /**
     * Get an existing session or create a new one.
     * Always refreshes the restaurant data and lastActive timestamp.
     */
    getOrCreate(phone, restaurant) {
        const key = this._key(phone, restaurant.id);

        if (!this.sessions.has(key)) {
            this.sessions.set(key, {
                history:    [],
                restaurant,
                lastActive: Date.now(),
            });
        }
        const session        = this.sessions.get(key);
        session.restaurant   = restaurant; // refresh on every message
        session.lastActive   = Date.now();
        return session;
    }

    /**
     * Trim session history to MAX_HISTORY entries (keeps recent context).
     */
    trim(phone, restaurantId) {
        const session = this.sessions.get(this._key(phone, restaurantId));
        if (!session) return;
        if (session.history.length > MAX_HISTORY) {
            session.history = session.history.slice(-MAX_HISTORY);
        }
    }

    /**
     * Remove sessions idle longer than SESSION_TTL_MS.
     */
    cleanup() {
        const now = Date.now();
        let removed = 0;
        for (const [key, session] of this.sessions) {
            if (now - session.lastActive > SESSION_TTL_MS) {
                this.sessions.delete(key);
                removed++;
            }
        }
        if (removed > 0) console.log(`🧹 Cleaned ${removed} idle session(s)`);
    }

    get size() {
        return this.sessions.size;
    }
}
