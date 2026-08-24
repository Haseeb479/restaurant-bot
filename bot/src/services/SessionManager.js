import { getDbPool } from './Database.js';

const SESSION_TTL_MS = 2 * 60 * 60 * 1000; // 2 hours idle before cleanup
const MAX_HISTORY    = 30;                  // max messages kept per session

/**
 * How long a human handoff stays in force before the AI is allowed to resume on
 * its own. A stuck-forever mute is worse than the bot politely re-engaging, so
 * this is a safety net on top of the explicit owner "resume" release.
 */
export const HANDOFF_TTL_MS = 60 * 60 * 1000; // 1 hour

/** Keep only digits; used to normalise phone/bot numbers before keying. */
function digitsOnly(value) {
    return String(value ?? '').replace(/[^0-9]/g, '');
}

/**
 * Bot numbers reach us in several shapes (`923001234567` from wid.user,
 * `03001234567` / `+92 300 1234567` in the DB). The DB restaurant lookup already
 * matches on the last 10 digits, so key handoffs the same way — that is what lets
 * MessageRouter recognise a handoff without doing its own restaurant lookup.
 */
function last10(value) {
    return digitsOnly(value).slice(-10);
}

/**
 * SessionManager — in-memory conversation sessions, fully isolated per restaurant,
 * plus the human-handoff lock that mutes the AI while a person is helping.
 *
 * Exported as a shared singleton (`sessionManager`). It used to be `new`-ed inside
 * ChatHandler, which meant every bot reconnect built a fresh manager (dropping
 * in-progress carts) AND leaked another cleanup `setInterval`. One shared instance
 * fixes both, and lets MessageRouter / EscalationHandler see the same handoff state.
 *
 * Session key format: `${restaurantId}:${phone}` — so the same customer texting
 * two different restaurant bots gets completely separate conversation histories.
 * Handoff key format:  `${last10(botNumber)}:${digits(phone)}`.
 */
export class SessionManager {
    constructor() {
        this.sessions = new Map(); // `restaurantId:phone` → { history, restaurant, lastActive }
        this.handoffs = new Map(); // `botLast10:phone`    → { botLast10, customerPhone, ownerLast10, restaurantId, until }

        // .unref() so this timer never keeps the process (or a test runner) alive
        // on its own — it only fires while the bot is otherwise running.
        const timer = setInterval(() => this.cleanup(), 15 * 60 * 1000);
        if (typeof timer.unref === 'function') timer.unref();
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
     * Remove sessions idle longer than SESSION_TTL_MS, and drop expired handoffs.
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
        this.pruneHandoffs(now);
    }

    get size() {
        return this.sessions.size;
    }

    // ── Human handoff lock ─────────────────────────────────────────────────────

    _handoffKey(botNumber, phone) {
        return `${last10(botNumber)}:${digitsOnly(phone)}`;
    }

    /**
     * Put a conversation into human-handling mode (in-memory only — persistence to
     * the conversations table is a separate, awaitable step so the pure logic here
     * stays testable without a DB). Returns the stored record.
     */
    startHandoff({ botNumber, customerPhone, ownerPhone, restaurantId = null, ttlMs = HANDOFF_TTL_MS, now = Date.now() }) {
        const rec = {
            botLast10:     last10(botNumber),
            customerPhone: digitsOnly(customerPhone),
            ownerLast10:   last10(ownerPhone),
            restaurantId:  restaurantId ?? null,
            until:         now + ttlMs,
        };
        this.handoffs.set(this._handoffKey(botNumber, customerPhone), rec);
        return rec;
    }

    /**
     * Is a customer currently being handled by a human on this bot? Expired
     * handoffs are pruned as a side effect so the AI resumes automatically.
     */
    isHandoffActive(botNumber, customerPhone, now = Date.now()) {
        const key = this._handoffKey(botNumber, customerPhone);
        const rec = this.handoffs.get(key);
        if (!rec) return false;
        if (rec.until <= now) {
            this.handoffs.delete(key);
            return false;
        }
        return true;
    }

    /**
     * Release a handoff (in-memory). Returns the removed record, or null if none.
     */
    endHandoff(botNumber, customerPhone) {
        const key = this._handoffKey(botNumber, customerPhone);
        const rec = this.handoffs.get(key) || null;
        this.handoffs.delete(key);
        return rec;
    }

    /**
     * Active handoffs on this bot owned by the given owner/manager phone — used to
     * let the owner release the bot by texting it, without a DB lookup in the
     * router. Expired entries are pruned as they're encountered.
     */
    findOwnerHandoffs(botNumber, ownerPhone, now = Date.now()) {
        const bot   = last10(botNumber);
        const owner = last10(ownerPhone);
        if (!owner) return [];

        const out = [];
        for (const [key, rec] of this.handoffs) {
            if (rec.until <= now) { this.handoffs.delete(key); continue; }
            if (rec.botLast10 === bot && rec.ownerLast10 === owner) out.push(rec);
        }
        return out;
    }

    /** Drop every expired handoff. */
    pruneHandoffs(now = Date.now()) {
        for (const [key, rec] of this.handoffs) {
            if (rec.until <= now) this.handoffs.delete(key);
        }
    }

    // ── Handoff persistence (survives a bot restart) ───────────────────────────

    /**
     * Persist a handoff to conversations.human_handling_until so a bot reboot does
     * not hand the customer straight back to the AI. Best-effort and non-blocking:
     * the in-memory lock is the source of truth for the running process.
     */
    async persistHandoff(rec) {
        if (!rec?.restaurantId) return; // nothing to key the row on
        try {
            const db  = getDbPool();
            const until = new Date(rec.until);
            const now   = new Date();

            const [rows] = await db.query(
                `SELECT id FROM conversations WHERE restaurant_id = ? AND customer_phone = ? LIMIT 1`,
                [rec.restaurantId, rec.customerPhone]
            );

            if (rows.length > 0) {
                await db.query(
                    `UPDATE conversations SET human_handling_until = ?, updated_at = ? WHERE id = ?`,
                    [until, now, rows[0].id]
                );
            } else {
                await db.query(
                    `INSERT INTO conversations (restaurant_id, customer_phone, state, human_handling_until, last_message_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)`,
                    [rec.restaurantId, rec.customerPhone, 'human_handling', until, now, now, now]
                );
            }
        } catch (e) {
            console.warn('⚠️ Handoff persist warning:', e.message);
        }
    }

    /** Clear the persisted handoff flag for a conversation. Best-effort. */
    async clearHandoffPersist(restaurantId, customerPhone) {
        if (!restaurantId) return;
        try {
            const db = getDbPool();
            await db.query(
                `UPDATE conversations SET human_handling_until = NULL, updated_at = ?
                 WHERE restaurant_id = ? AND customer_phone = ?`,
                [new Date(), restaurantId, digitsOnly(customerPhone)]
            );
        } catch (e) {
            console.warn('⚠️ Handoff clear warning:', e.message);
        }
    }

    /**
     * Reload still-active handoffs from the DB into memory. Called once at boot so
     * a restart mid-complaint keeps the AI muted. Joins restaurants to recover the
     * bot number (for the in-memory key) and the owner phone (for owner release).
     */
    async rehydrateHandoffs(now = Date.now()) {
        try {
            const db = getDbPool();
            const [rows] = await db.query(
                `SELECT c.customer_phone, c.restaurant_id, c.human_handling_until,
                        r.whatsapp_number,
                        COALESCE(r.manager_phone, r.owner_phone) AS owner_phone
                 FROM conversations c
                 JOIN restaurants r ON r.id = c.restaurant_id
                 WHERE c.human_handling_until IS NOT NULL AND c.human_handling_until > NOW()`
            );

            let loaded = 0;
            for (const row of rows) {
                const until = new Date(row.human_handling_until).getTime();
                if (!Number.isFinite(until) || until <= now) continue;
                const rec = {
                    botLast10:     last10(row.whatsapp_number),
                    customerPhone: digitsOnly(row.customer_phone),
                    ownerLast10:   last10(row.owner_phone),
                    restaurantId:  row.restaurant_id,
                    until,
                };
                this.handoffs.set(`${rec.botLast10}:${rec.customerPhone}`, rec);
                loaded++;
            }
            if (loaded > 0) console.log(`🤝 Rehydrated ${loaded} active human-handoff(s) from DB`);
        } catch (e) {
            console.warn('⚠️ Handoff rehydrate warning:', e.message);
        }
    }
}

/**
 * Shared singleton — import this, do not `new SessionManager()` in handlers.
 * (Exported class above remains available for tests that want a clean instance.)
 */
export const sessionManager = new SessionManager();
