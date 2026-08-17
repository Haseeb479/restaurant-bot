import fs from 'fs';
import path from 'path';
import xlsx from 'xlsx';

/**
 * ExcelMenuService — reads restaurant menu from Excel sheets (.xlsx, .xls, .csv).
 *
 * Extracts categories, items, prices, and size variants with fuzzy column matching
 * (e.g., handles "Item Name", "Name", "Product", "Price", "Rate", "Rs", "Sizes", etc.)
 *
 * Caches results by restaurantId + file modification time for zero-delay lookups.
 */
export class ExcelMenuService {
    constructor() {
        this.cache = new Map(); // restaurantId -> { mtime, items, menuText }
    }

    /**
     * Parse Excel/CSV file into structured menu items & prompt-ready text.
     * @param {number} restaurantId
     * @param {string} filePath - Absolute path to .xlsx / .xls / .csv file
     * @returns {{ items: Array, menuText: string } | null}
     */
    parseExcel(restaurantId, filePath) {
        if (!filePath || !fs.existsSync(filePath)) {
            return null;
        }

        const ext = path.extname(filePath).toLowerCase();
        if (!['.xlsx', '.xls', '.csv'].includes(ext)) {
            return null;
        }

        try {
            const stats = fs.statSync(filePath);
            const cached = this.cache.get(restaurantId);
            if (cached && cached.mtime === stats.mtimeMs) {
                return cached;
            }

            console.log(`📊 ExcelMenuService: Reading ${ext} file for restaurant #${restaurantId}: ${filePath}`);

            const workbook = xlsx.readFile(filePath);
            const sheetName = workbook.SheetNames[0];
            if (!sheetName) return null;

            const worksheet = workbook.Sheets[sheetName];
            const rawRows = xlsx.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

            if (!rawRows || rawRows.length === 0) return null;

            // Find header row or use default column indices
            let headerRowIndex = -1;
            let colMap = { category: -1, name: -1, price: -1, sizes: -1, desc: -1 };

            for (let r = 0; r < Math.min(rawRows.length, 10); r++) {
                const row = rawRows[r];
                if (!Array.isArray(row)) continue;

                const rowLower = row.map(cell => String(cell || '').trim().toLowerCase());

                const nameIdx = rowLower.findIndex(c => c.includes('item') || c.includes('name') || c.includes('dish') || c.includes('product'));
                const priceIdx = rowLower.findIndex(c => c.includes('price') || c.includes('rate') || c.includes('rs') || c.includes('cost') || c.includes('amount'));

                if (nameIdx !== -1 && priceIdx !== -1) {
                    headerRowIndex = r;
                    colMap.name = nameIdx;
                    colMap.price = priceIdx;
                    colMap.category = rowLower.findIndex(c => c.includes('cat') || c.includes('section') || c.includes('type'));
                    colMap.sizes = rowLower.findIndex(c => c.includes('size') || c.includes('variant') || c.includes('portion'));
                    colMap.desc = rowLower.findIndex(c => c.includes('desc') || c.includes('detail') || c.includes('info'));
                    break;
                }
            }

            // Fallback column positions if no named header found: 0=Category, 1=Name, 2=Price, 3=Sizes, 4=Desc
            const startRow = headerRowIndex !== -1 ? headerRowIndex + 1 : 0;
            if (colMap.name === -1) colMap.name = 1;
            if (colMap.price === -1) colMap.price = 2;
            if (colMap.category === -1) colMap.category = 0;
            if (colMap.sizes === -1) colMap.sizes = 3;
            if (colMap.desc === -1) colMap.desc = 4;

            const items = [];
            let currentCategory = 'General';

            for (let r = startRow; r < rawRows.length; r++) {
                const row = rawRows[r];
                if (!Array.isArray(row) || row.length === 0) continue;

                const catCell = colMap.category !== -1 ? String(row[colMap.category] || '').trim() : '';
                const nameCell = colMap.name !== -1 ? String(row[colMap.name] || '').trim() : '';
                const priceRaw = colMap.price !== -1 ? String(row[colMap.price] || '').trim() : '';
                const sizesRaw = colMap.sizes !== -1 ? String(row[colMap.sizes] || '').trim() : '';
                const descCell = colMap.desc !== -1 ? String(row[colMap.desc] || '').trim() : '';

                if (!nameCell) continue;

                if (catCell) {
                    currentCategory = catCell;
                }

                // Clean price
                const cleanPrice = parseFloat(priceRaw.replace(/[^0-9.]/g, '')) || 0;

                // Parse sizes (e.g., "M:150, L:250" or "Small: 200 / Large: 350")
                let parsedSizes = [];
                if (sizesRaw) {
                    const parts = sizesRaw.split(/[,|\/]/);
                    for (const p of parts) {
                        if (p.includes(':')) {
                            const [sName, sPrice] = p.split(':');
                            const numPrice = parseFloat(sPrice.replace(/[^0-9.]/g, '')) || 0;
                            if (numPrice > 0) {
                                parsedSizes.push({ size: sName.trim().toUpperCase(), price: numPrice });
                            }
                        }
                    }
                }

                items.push({
                    category: currentCategory,
                    name: nameCell,
                    price: cleanPrice,
                    sizes: parsedSizes.length > 0 ? parsedSizes : null,
                    description: descCell || null,
                });
            }

            if (items.length === 0) {
                console.warn(`⚠️ ExcelMenuService: No valid items found in ${filePath}`);
                return null;
            }

            // Build menu text
            let menuText = 'MENU (Extracted from official Excel Sheet — exact items & prices):\n';
            let lastCat = '';

            items.forEach((item, idx) => {
                if (item.category && item.category !== lastCat) {
                    menuText += `\n[${item.category.toUpperCase()}]\n`;
                    lastCat = item.category;
                }

                menuText += `${idx + 1}. ${item.name}`;
                if (item.sizes && item.sizes.length > 0) {
                    const sizeText = item.sizes.map(s => `${s.size}: Rs.${s.price}`).join(' / ');
                    menuText += ` — ${sizeText}`;
                } else if (item.price > 0) {
                    menuText += ` — Rs.${item.price}`;
                }

                if (item.description) {
                    menuText += ` (${item.description})`;
                }
                menuText += '\n';
            });

            menuText += '\nIMPORTANT: Use ONLY these exact prices for bill calculation.\n';

            const result = {
                mtime: stats.mtimeMs,
                items,
                menuText,
            };

            this.cache.set(restaurantId, result);
            console.log(`✅ ExcelMenuService: Successfully parsed ${items.length} menu items from Excel for restaurant #${restaurantId}`);
            return result;

        } catch (err) {
            console.error('❌ ExcelMenuService error parsing file:', err.message);
            return null;
        }
    }
}

export const excelMenu = new ExcelMenuService();
