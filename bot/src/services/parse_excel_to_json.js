import xlsx from 'xlsx';
import fs from 'fs';
import path from 'path';

const filePath = process.argv[2];

if (!filePath || !fs.existsSync(filePath)) {
    console.error(JSON.stringify({ error: 'File not found' }));
    process.exit(1);
}

try {
    const workbook = xlsx.readFile(filePath);
    const sheetName = workbook.SheetNames[0];
    if (!sheetName) {
        console.error(JSON.stringify({ error: 'No sheet found' }));
        process.exit(1);
    }

    const worksheet = workbook.Sheets[sheetName];
    const rawRows = xlsx.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

    if (!rawRows || rawRows.length === 0) {
        console.log(JSON.stringify([]));
        process.exit(0);
    }

    // Size detection helpers
    function detectSizeFromHeader(headerStr) {
        const s = String(headerStr || '').trim().toLowerCase();
        if (!s) return null;
        if (s.includes('category') || s.includes('desc') || s.includes('detail') || s.includes('item') || s.includes('dish') || s.includes('product') || s.includes('total') || s.includes('count') || s.includes('avg')) {
            return null;
        }
        if (/\b(extra\s*large|xlarge|xl|x-large|family|party|jumbo|monster)\b/i.test(s) || /16["”\s]/i.test(s)) {
            return 'XL';
        }
        if (/\b(large|lg)\b/i.test(s) || /13["”\s]/i.test(s) || s === 'l' || /^l\s*[\(\[]/i.test(s) || /[\(\[]\s*l\s*[\)\]]/i.test(s) || /^price[\s_\-]+l$/i.test(s) || /^l[\s_\-]+price$/i.test(s)) {
            return 'L';
        }
        if (/\b(medium|med)\b/i.test(s) || /10["”\s]/i.test(s) || s === 'm' || /^m\s*[\(\[]/i.test(s) || /[\(\[]\s*m\s*[\)\]]/i.test(s) || /^price[\s_\-]+m$/i.test(s) || /^m[\s_\-]+price$/i.test(s)) {
            return 'M';
        }
        if (/\b(small|sm)\b/i.test(s) || /7["”\s]/i.test(s) || s === 's' || /^s\s*[\(\[]/i.test(s) || /[\(\[]\s*s\s*[\)\]]/i.test(s) || /^price[\s_\-]+s$/i.test(s) || /^s[\s_\-]+price$/i.test(s)) {
            return 'S';
        }
        if (/\b(regular|reg)\b/i.test(s)) {
            return 'REGULAR';
        }
        if (/\b(half|single)\b/i.test(s)) {
            return 'HALF';
        }
        if (/\b(full|double)\b/i.test(s)) {
            return 'FULL';
        }
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

    // 1. Scan first 30 rows for table header
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

            if (nameIdx === -1 && (c.includes('item') || c.includes('dish') || c.includes('product') || c === 'name' || c.endsWith(' name') || c === 'flavor' || c === 'flavour')) {
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
                    } else if (c.includes('desc') || c.includes('detail') || c.includes('info') || c.includes('ingredient')) {
                        colMap.desc = idx;
                    }
                }
            }
            break;
        }
    }

    // Default column indices if no named header: 0=Cat, 1=Name, 2=Price, 3=Sizes, 4=Desc
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

        const rowJoined = row.map(c => String(c || '').trim()).filter(Boolean).join(' ');

        // Check for section banner row (e.g. ── STARTERS ──, — TANDOORI —, === MAIN COURSE ===, [DRINKS])
        const bannerMatch = rowJoined.match(/^[—─=\-\*~_\[\s]+(.+?)[—─=\-\*~_\]\s]+$/u) ||
                            nameCell.match(/^[—─=\-\*~_\[\s]+(.+?)[—─=\-\*~_\]\s]+$/u);
        if (bannerMatch) {
            const bannerTitle = bannerMatch[1].trim();
            if (!/^(restaurant\s*menu|good\s*food|menu|summary|total|overview)/i.test(bannerTitle) && bannerTitle.length >= 2) {
                currentCategory = bannerTitle.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
            }
            continue;
        }

        // Check for metadata / summary rows to skip
        const checkText = (nameCell + ' ' + catCell).toLowerCase();
        if (/\b(total\s*items?|total\s*menu|average\s*item|avg\s*price|lowest\s*price|highest\s*price|summary|restaurant\s*menu|good\s*food|item\s*count|count\b)/i.test(checkText)) {
            continue;
        }

        if (!nameCell) continue;

        // If explicit category is given in category column and not decorative
        if (catCell) {
            const cleanCat = catCell.replace(/[—─=\-\*~_\[\]]+/gu, '').trim();
            if (cleanCat && !/^(total|average|lowest|highest|summary|restaurant\s*menu)/i.test(cleanCat)) {
                currentCategory = cleanCat.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
            }
        }

        let cleanPrice = parseFloat(priceRaw.replace(/[^0-9.]/g, '')) || 0;
        let parsedSizes = [];

        // A. Extract from dedicated size columns (e.g. S, M, L, XL columns)
        if (colMap.sizeCols && colMap.sizeCols.length > 0) {
            for (const sc of colMap.sizeCols) {
                const cellVal = String(row[sc.idx] || '').trim();
                const cellPrice = parseFloat(cellVal.replace(/[^0-9.]/g, '')) || 0;
                if (cellPrice > 0) {
                    parsedSizes.push({ size: sc.size, price: cellPrice });
                }
            }
        }

        // B. Extract from Sizes column text if no size columns found
        if (parsedSizes.length === 0 && sizesRaw) {
            parsedSizes = parseInlineSizes(sizesRaw);
        }

        // C. Check if priceRaw contains multiple size variants (e.g. "S: 650, M: 1150, L: 1750")
        if (parsedSizes.length === 0 && priceRaw) {
            const fromPrice = parseInlineSizes(priceRaw);
            if (fromPrice.length > 0) {
                parsedSizes = fromPrice;
            }
        }

        parsedSizes = sortSizes(parsedSizes) || [];

        if (cleanPrice <= 0 && parsedSizes.length === 0) {
            // Might be a category banner without decoration
            if (nameCell.length <= 30 && !nameCell.includes('Rs') && !nameCell.includes('₹')) {
                currentCategory = nameCell.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
            }
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

    // Consolidate row-based size items (e.g. "Bonfire Pizza - Small", "Bonfire Pizza - Medium")
    const consolidatedItems = [];
    const itemMap = new Map();

    for (const item of rawItems) {
        // Check if name has size suffix like "Item Name (Small)" or "Item Name - Large"
        const suffixMatch = item.name.match(/^(.+?)[\s\-_(\[]+(small|medium|large|extra\s*large|xlarge|xl|x-large|family|jumbo|regular|half|full|s|m|l)[)\s\]]*$/i);
        
        if (suffixMatch && !item.sizes) {
            const baseName = suffixMatch[1].trim();
            const rawSize = suffixMatch[2].toLowerCase().replace(/[\s\-_]+/g, ' ').trim();
            let normSize = 'S';
            if (rawSize.includes('extra') || rawSize.includes('xl') || rawSize.includes('family') || rawSize.includes('jumbo')) normSize = 'XL';
            else if (rawSize.includes('large') || rawSize === 'l') normSize = 'L';
            else if (rawSize.includes('medium') || rawSize.includes('med') || rawSize === 'm') normSize = 'M';
            else if (rawSize.includes('small') || rawSize.includes('sm') || rawSize === 's') normSize = 'S';
            else if (rawSize.includes('regular')) normSize = 'REGULAR';
            else if (rawSize.includes('half')) normSize = 'HALF';
            else if (rawSize.includes('full')) normSize = 'FULL';
            else normSize = rawSize.toUpperCase();

            const key = `${item.category}:::${baseName.toLowerCase()}`;
            if (itemMap.has(key)) {
                const existing = itemMap.get(key);
                if (!existing.sizes) {
                    existing.sizes = [];
                }
                if (!existing.sizes.some(s => s.size === normSize)) {
                    existing.sizes.push({ size: normSize, price: item.price });
                    existing.sizes = sortSizes(existing.sizes);
                }
                if (item.description && !existing.description) {
                    existing.description = item.description;
                }
                continue;
            } else {
                item.name = baseName;
                item.sizes = [{ size: normSize, price: item.price }];
                itemMap.set(key, item);
                consolidatedItems.push(item);
                continue;
            }
        }

        // If not a suffixed row, check if already in map
        const key = `${item.category}:::${item.name.toLowerCase()}`;
        if (itemMap.has(key)) {
            const existing = itemMap.get(key);
            if (item.sizes && existing.sizes) {
                // Merge sizes
                for (const s of item.sizes) {
                    if (!existing.sizes.some(es => es.size === s.size)) {
                        existing.sizes.push(s);
                    }
                }
                existing.sizes = sortSizes(existing.sizes);
            }
        } else {
            itemMap.set(key, item);
            consolidatedItems.push(item);
        }
    }

    console.log(JSON.stringify(consolidatedItems));
} catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
}
