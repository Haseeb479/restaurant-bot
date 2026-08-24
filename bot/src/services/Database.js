import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);

dotenv.config({ path: path.resolve(__dirname, '../../../.env') });

let pool = null;

export function getDbPool() {
    if (!pool) {
        pool = mysql.createPool({
            host:            process.env.DB_HOST || '127.0.0.1',
            port:            parseInt(process.env.DB_PORT || '3306'),
            user:            process.env.DB_USERNAME || 'root',
            password:        process.env.DB_PASSWORD || '',
            database:        process.env.DB_DATABASE || 'restaurant_bot',
            waitForConnections: true,
            connectionLimit: 10,
            queueLimit:      0,
        });
    }
    return pool;
}

/**
 * Direct MySQL query helper to fetch a restaurant and its menu directly from DB.
 */
export async function getRestaurantDirectFromDb(botNumber) {
    try {
        const digits = botNumber.replace(/[^0-9]/g, '');
        const last10 = digits.slice(-10);
        const last9  = digits.slice(-9);

        const db = getDbPool();

        // 1. Try matching by phone number
        let [rows] = await db.query(
            `SELECT * FROM restaurants 
             WHERE is_active = 1 
               AND (
                   whatsapp_number = ? 
                   OR whatsapp_number LIKE ? 
                   OR whatsapp_number LIKE ?
               ) 
             LIMIT 1`,
            [digits, `%${last10}`, `%${last9}`]
        );

        if (!rows.length) return null;

        const r = rows[0];

        // Fetch categories & menu items
        const [categories] = await db.query(
            'SELECT * FROM categories WHERE restaurant_id = ? AND is_active = 1 ORDER BY sort_order',
            [r.id]
        );

        const [items] = await db.query(
            'SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = 1 ORDER BY sort_order',
            [r.id]
        );

        const [allDeals] = await db.query(
            'SELECT * FROM deals WHERE restaurant_id = ? AND is_active = 1',
            [r.id]
        ).catch(() => [[]]);

        // Filter deals by current Pakistan Standard Time (PKT, UTC+5)
        const nowUtc = new Date();
        const pktDate = new Date(nowUtc.getTime() + (5 * 60 + nowUtc.getTimezoneOffset()) * 60000);
        const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const currentDay = dayNames[pktDate.getDay()];
        const currentTimeStr = pktDate.toTimeString().slice(0, 8); // "HH:MM:SS"

        const activeDeals = (allDeals || []).filter(deal => {
            // 1. Check valid_days
            if (deal.valid_days) {
                let validDays = [];
                try {
                    validDays = typeof deal.valid_days === 'string' ? JSON.parse(deal.valid_days) : deal.valid_days;
                } catch (e) {}

                if (Array.isArray(validDays) && validDays.length > 0) {
                    const daysLower = validDays.map(d => String(d).toLowerCase());
                    if (!daysLower.includes(currentDay)) {
                        return false; // Not valid today
                    }
                }
            }

            // 2. Check time window
            if (deal.valid_from && currentTimeStr < deal.valid_from) {
                return false; // Deal hasn't started yet today
            }
            if (deal.valid_until && currentTimeStr > deal.valid_until) {
                return false; // Deal ended for today
            }

            return true;
        });

        const parsedItems = items.map(item => {
            let sizes = item.sizes;
            if (typeof sizes === 'string') {
                try {
                    sizes = JSON.parse(sizes || 'null');
                } catch (e) {
                    // A single malformed `sizes` cell must not null the whole
                    // restaurant load (which would look like "not linked").
                    console.warn(`⚠️ Bad sizes JSON for menu item ${item.id} — ignoring sizes.`);
                    sizes = null;
                }
            }
            return { ...item, sizes };
        });

        const isOpen = Boolean(r.is_open);

        return {
            ...r,
            categories,
            menu_items:   parsedItems,
            active_deals: activeDeals,
            is_open:      isOpen,
            // `_closed` is what ChatHandler checks; set it on this primary path
            // (previously only the dead HTTP-fallback path set it → bot ordered 24/7).
            _closed:      !isOpen,
        };

    } catch (err) {
        console.warn('⚠️ Direct DB query warning:', err.message);
        return null;
    }
}
