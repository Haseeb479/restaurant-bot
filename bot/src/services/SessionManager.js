const SESSION_TTL_MS = 2 * 60 * 60 * 1000; // 2 hours idle before cleanup
const MAX_HISTORY    = 30;                   // max messages kept per session

/**
 * SessionManager — in-memory conversation sessions.
 * Each session holds: chat history, restaurant context, last activity timestamp.
 * Stale sessions are auto-cleaned every 15 minutes.
 */
export class SessionManager {
    constructor() {
        this.sessions = new Map(); // phone → { history, restaurant, lastActive }
        setInterval(() => this.cleanup(), 15 * 60 * 1000);
    }

    /**
     * Get an existing session or create a new one.
     * Always refreshes the restaurant data and lastActive timestamp.
     */
    getOrCreate(phone, restaurant) {
        if (!this.sessions.has(phone)) {
            this.sessions.set(phone, {
                history:    [],
                restaurant,
                lastActive: Date.now(),
            });
        }
        const session        = this.sessions.get(phone);
        session.restaurant   = restaurant; // refresh on every message
        session.lastActive   = Date.now();
        return session;
    }

    /**
     * Trim session history to MAX_HISTORY entries (keeps recent context).
     */
    trim(phone) {
        const session = this.sessions.get(phone);
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
        for (const [phone, session] of this.sessions) {
            if (now - session.lastActive > SESSION_TTL_MS) {
                this.sessions.delete(phone);
                removed++;
            }
        }
        if (removed > 0) console.log(`🧹 Cleaned ${removed} idle session(s)`);
    }

    get size() {
        return this.sessions.size;
    }
}
