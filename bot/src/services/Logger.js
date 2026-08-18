import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDbPool } from './Database.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);
const LOG_DIR    = path.resolve(__dirname, '../../../storage/logs/bot');

// Ensure log folder exists
if (!fs.existsSync(LOG_DIR)) {
    try {
        fs.mkdirSync(LOG_DIR, { recursive: true });
    } catch (e) {}
}

/**
 * Logger — structured logging to disk and database.
 */
export class Logger {
    static getDailyLogFile() {
        const dateStr = new Date().toISOString().slice(0, 10);
        return path.join(LOG_DIR, `bot-${dateStr}.log`);
    }

    /**
     * Write structured log entry to file
     */
    static write(level, message, meta = {}) {
        const timestamp = new Date().toISOString();
        const entry = JSON.stringify({
            timestamp,
            level,
            message,
            ...meta,
        }) + '\n';

        try {
            fs.appendFileSync(this.getDailyLogFile(), entry);
        } catch (err) {
            console.error('⚠️ Logger write error:', err.message);
        }
    }

    static info(message, meta) {
        this.write('INFO', message, meta);
    }

    static warn(message, meta) {
        this.write('WARN', message, meta);
    }

    static error(message, meta) {
        this.write('ERROR', message, meta);
    }

    /**
     * Log chat exchange directly to conversations table for owner review
     */
    static async logToDb(restaurantId, customerPhone, customerText, botReply, intent = 'chat') {
        try {
            const db = getDbPool();
            const now = new Date();

            // Check if conversation row exists for today
            const [rows] = await db.query(
                `SELECT id FROM conversations WHERE restaurant_id = ? AND customer_phone = ? LIMIT 1`,
                [restaurantId || 1, customerPhone]
            );

            if (rows.length > 0) {
                await db.query(
                    `UPDATE conversations 
                     SET last_message_at = ?, state = ?, updated_at = ? 
                     WHERE id = ?`,
                    [now, intent, now, rows[0].id]
                );
            } else {
                await db.query(
                    `INSERT INTO conversations (restaurant_id, customer_phone, state, last_message_at, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?)`,
                    [restaurantId || 1, customerPhone, intent, now, now, now]
                );
            }
        } catch (e) {
            // Non-blocking log failure
            console.warn('⚠️ DB conversation log warning:', e.message);
        }
    }
}
