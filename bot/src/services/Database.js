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

        // 2. If no match and only 1 active restaurant exists, bind and use that sole restaurant!
        if (!rows.length) {
            const [allActive] = await db.query('SELECT * FROM restaurants WHERE is_active = 1');
            if (allActive.length === 1) {
                rows = allActive;
                // Auto-update number in DB
                await db.query('UPDATE restaurants SET whatsapp_number = ? WHERE id = ?', [digits, rows[0].id]).catch(() => {});
            }
        }

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

        const [deals] = await db.query(
            'SELECT * FROM deals WHERE restaurant_id = ? AND is_active = 1',
            [r.id]
        ).catch(() => [[]]);

        const parsedItems = items.map(item => ({
            ...item,
            sizes: typeof item.sizes === 'string' ? JSON.parse(item.sizes || 'null') : item.sizes,
        }));

        return {
            ...r,
            categories,
            menu_items:   parsedItems,
            active_deals: deals || [],
            is_open:      Boolean(r.is_open),
        };

    } catch (err) {
        console.warn('⚠️ Direct DB query warning:', err.message);
        return null;
    }
}
