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
        if (!['.xlsx', '.xls', '.csv', '.tsv', '.txt'].includes(ext)) {
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

            function detectSizeFromHeader(headerStr) {
                const s = String(headerStr || '').trim().toLowerCase();
                if (!s) return null;
                if (s.includes('category') || s.includes('desc') || s.includes('detail') || s.includes('item') || s.includes('dish') || s.includes('product') || s.includes('total') || s.includes('count') || s.includes('avg')) {
                    return null;
                }
                if (/\b(extra\s*large|xlarge|xl|x-large|family|party|jumbo|monster)\b/i.test(s) || /16["”\s]/i.test(s)) return 'XL';
                if (/\b(large|lg)\b/i.test(s) || /13["”\s]/i.test(s) || s === 'l' || /^l\s*[\(\[]/i.test(s) || /[\(\[]\s*l\s*[\)\]]/i.test(s) || /^price[\s_\-]+l$/i.test(s) || /^l[\s_\-]+price$/i.test(s)) return 'L';
                if (/\b(medium|med)\b/i.test(s) || /10["”\s]/i.test(s) || s === 'm' || /^m\s*[\(\[]/i.test(s) || /[\(\[]\s*m\s*[\)\]]/i.test(s) || /^price[\s_\-]+m$/i.test(s) || /^m[\s_\-]+price$/i.test(s)) return 'M';
                if (/\b(small|sm)\b/i.test(s) || /7["”\s]/i.test(s) || s === 's' || /^s\s*[\(\[]/i.test(s) || /[\(\[]\s*s\s*[\)\]]/i.test(s) || /^price[\s_\-]+s$/i.test(s) || /^s[\s_\-]+price$/i.test(s)) return 'S';
                if (/\b(regular|reg)\b/i.test(s)) return 'REGULAR';
                if (/\b(half|single)\b/i.test(s)) return 'HALF';
                if (/\b(full|double)\b/i.test(s)) return 'FULL';
                return null;
            }

            function parseInlineSizes(rawText) {
                if (!rawText || typeof rawText !== 'string') return [];
                const text = rawText.trim();
                if (!text) return [];

                const results = [];
                const seenSizes = new Set();
                const regex = /(?:^|[\s,;|\/\n])(small|medium|large|extra\s*large|xlarge|xl|x-large|family|jumbo|party|regular|reg|half|full|single|double|s|m|l)\s*[:=\-\(]?\s*(?:rs\.?|pkr|₹)?\s*([0-9]+(?:\.[0-9]+)?)\s*\)?/gi;
                let match;
                while ((match = regex.exec(text)) !== null) {
                    const rawSize = match[1].toLowerCase().replace(/[\s\-_]+/g, ' ').trim();
                    const price = parseFloat(match[2]);
                    if (price > 0) {
                        let normSize = 'S';
                        if (rawSize.includes('extra') || rawSize.includes('xl') || rawSize.includes('family') || rawSize.includes('jumbo') || rawSize.includes('party')) normSize = 'XL';
                        else if (rawSize.includes('large') || rawSize === 'l') normSize = 'L';
                        else if (rawSize.includes('medium') || rawSize.includes('med') || rawSize === 'm') normSize = 'M';
                        else if (rawSize.includes('small') || rawSize.includes('sm') || rawSize === 's') normSize = 'S';
                        else if (rawSize.includes('regular') || rawSize === 'reg') normSize = 'REGULAR';
                        else if (rawSize.includes('half') || rawSize === 'single') normSize = 'HALF';
                        else if (rawSize.includes('full') || rawSize === 'double') normSize = 'FULL';
                        else normSize = rawSize.toUpperCase();

                        if (!seenSizes.has(normSize)) {
                            seenSizes.add(normSize);
                            results.push({ size: normSize, price });
                        }
                    }
                }
                return results;
            }

            function sortSizes(sizesList) {
                if (!sizesList || sizesList.length === 0) return null;
                const sizeOrder = { 'S': 1, 'M': 2, 'L': 3, 'XL': 4, 'REGULAR': 1.5, 'HALF': 1, 'FULL': 2 };
                return [...sizesList].sort((a, b) => (sizeOrder[a.size] || 99) - (sizeOrder[b.size] || 99));
            }

            // Find header row or use default column indices
            let headerRowIndex = -1;
            let colMap = { category: -1, name: -1, price: -1, sizes: -1, desc: -1, sizeCols: [] };

            for (let r = 0; r < Math.min(rawRows.length, 30); r++) {
                const row = rawRows[r];
                if (!Array.isArray(row)) continue;

                const rowLower = row.map(cell => String(cell || '').trim().toLowerCase());

                let nameIdx = -1;
                let priceIdx = -1;
                const detectedSizes = [];

                for (let idx = 0; idx < rowLower.length; idx++) {
                    const c = rowLower[idx];
                    if (!c) continue;

                    const detectedSize = detectSizeFromHeader(c);
                    if (detectedSize) {
                        detectedSizes.push({ idx, size: detectedSize });
                        continue;
                    }

                    if (nameIdx === -1 && (c.includes('item') || c.includes('name') || c.includes('dish') || c.includes('product') || c === 'flavor' || c === 'flavour')) {
                        nameIdx = idx;
                    }
                    if (priceIdx === -1 && (c.includes('price') || c.includes('rate') || c.includes('rs') || c.includes('₹') || c.includes('pkr') || c.includes('cost') || c.includes('amount'))) {
                        priceIdx = idx;
                    }
                }

                if (nameIdx !== -1 && (priceIdx !== -1 || detectedSizes.length > 0)) {
                    headerRowIndex = r;
                    colMap.name = nameIdx;
                    colMap.price = priceIdx;
                    colMap.sizeCols = detectedSizes;

                    for (let idx = 0; idx < rowLower.length; idx++) {
                        if (idx !== nameIdx && idx !== priceIdx && !detectedSizes.some(ds => ds.idx === idx)) {
                            const c = rowLower[idx];
                            if (c.includes('cat') || c.includes('section') || c.includes('type') || c.includes('group')) {
                                colMap.category = idx;
                            } else if (c.includes('size') || c.includes('variant') || c.includes('portion')) {
                                colMap.sizes = idx;
                            } else if (c.includes('desc') || c.includes('detail') || c.includes('info')) {
                                colMap.desc = idx;
                            }
                        }
                    }
                    break;
                }
            }

            // Fallback column positions if no named header found: 0=Category, 1=Name, 2=Price, 3=Sizes, 4=Desc
            const startRow = headerRowIndex !== -1 ? headerRowIndex + 1 : 0;
            if (colMap.name === -1) colMap.name = 1;
            if (colMap.price === -1 && colMap.sizeCols.length === 0) colMap.price = 2;
            if (colMap.category === -1) colMap.category = 0;
            if (colMap.sizes === -1) colMap.sizes = 3;
            if (colMap.desc === -1) colMap.desc = 4;

            const rawItems = [];
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
                    const cleanCat = catCell.replace(/[—─=\-\*~_\[\]]+/gu, '').trim();
                    if (cleanCat && !/^(total|average|lowest|highest|summary|restaurant\s*menu)/i.test(cleanCat)) {
                        currentCategory = cleanCat;
                    }
                }

                let cleanPrice = parseFloat(priceRaw.replace(/[^0-9.]/g, '')) || 0;
                let parsedSizes = [];

                if (colMap.sizeCols && colMap.sizeCols.length > 0) {
                    for (const sc of colMap.sizeCols) {
                        const cellVal = String(row[sc.idx] || '').trim();
                        const cellPrice = parseFloat(cellVal.replace(/[^0-9.]/g, '')) || 0;
                        if (cellPrice > 0) {
                            parsedSizes.push({ size: sc.size, price: cellPrice });
                        }
                    }
                }

                if (parsedSizes.length === 0 && sizesRaw) {
                    parsedSizes = parseInlineSizes(sizesRaw);
                }

                if (parsedSizes.length === 0 && priceRaw) {
                    const fromPrice = parseInlineSizes(priceRaw);
                    if (fromPrice.length > 0) {
                        parsedSizes = fromPrice;
                    }
                }

                parsedSizes = sortSizes(parsedSizes) || [];

                if (cleanPrice <= 0 && parsedSizes.length === 0) {
                    continue;
                }

                const effectivePrice = (parsedSizes && parsedSizes.length > 0)
                    ? parsedSizes[0].price
                    : cleanPrice;

                rawItems.push({
                    category: currentCategory,
                    name: nameCell,
                    price: effectivePrice,
                    sizes: parsedSizes.length > 0 ? parsedSizes : null,
                    description: descCell || null,
                });
            }

            if (rawItems.length === 0) {
                console.warn(`⚠️ ExcelMenuService: No valid items found in ${filePath}`);
                return null;
            }

            const items = rawItems;

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
