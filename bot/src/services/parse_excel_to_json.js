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

    // Size detection helpers - Canonical naming: Small, Medium, Large, XL, Regular, Family, Personal, Jumbo, Half, Full
    const sizeOrderMap = {
        'Personal': 0.5,
        'Small': 1,
        'Regular': 1.5,
        'Half': 1.8,
        'Medium': 2,
        'Full': 2.8,
        'Large': 3,
        'XL': 4,
        'Family': 5,
        'Jumbo': 6
    };

    function normalizeSizeName(raw) {
        if (!raw || typeof raw !== 'string') return '';
        const s = raw.trim().toLowerCase().replace(/[\s\-_]+/g, ' ');
        if (s.includes('family')) return 'Family';
        if (s.includes('jumbo') || s.includes('party')) return 'Jumbo';
        if (s.includes('personal')) return 'Personal';
        if (/\b(extra\s*large|xlarge|xl|x-large)\b/i.test(s) || /(?:16["”]|16\s*inch\b)/i.test(s)) return 'XL';
        if (/\b(large|lg)\b/i.test(s) || /(?:13["”]|13\s*inch\b)/i.test(s) || s === 'l') return 'Large';
        if (/\b(medium|med)\b/i.test(s) || /(?:10["”]|10\s*inch\b)/i.test(s) || s === 'm') return 'Medium';
        if (/\b(small|sm)\b/i.test(s) || /(?:7["”]|7\s*inch\b)/i.test(s) || s === 's') return 'Small';
        if (/\b(regular|reg)\b/i.test(s)) return 'Regular';
        if (/\b(half|single)\b/i.test(s)) return 'Half';
        if (/\b(full|double)\b/i.test(s)) return 'Full';
        return raw.trim().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
    }

    function detectSizeFromHeader(headerStr) {
        const s = String(headerStr || '').trim().toLowerCase();
        if (!s) return null;
        if (s.includes('category') || s.includes('desc') || s.includes('detail') || s.includes('item') || s.includes('dish') || s.includes('product') || s.includes('total') || s.includes('count') || s.includes('avg')) {
            return null;
        }
        if (/\b(family)\b/i.test(s)) return 'Family';
        if (/\b(jumbo|monster|party)\b/i.test(s)) return 'Jumbo';
        if (/\b(personal)\b/i.test(s)) return 'Personal';
        if (/\b(extra\s*large|xlarge|xl|x-large)\b/i.test(s) || /(?:16["”]|16\s*inch\b)/i.test(s)) return 'XL';
        if (/\b(large|lg)\b/i.test(s) || /(?:13["”]|13\s*inch\b)/i.test(s) || s === 'l' || /^l\s*[\(\[]/i.test(s) || /[\(\[]\s*l\s*[\)\]]/i.test(s) || /^price[\s_\-]+l$/i.test(s) || /^l[\s_\-]+price$/i.test(s)) return 'Large';
        if (/\b(medium|med)\b/i.test(s) || /(?:10["”]|10\s*inch\b)/i.test(s) || s === 'm' || /^m\s*[\(\[]/i.test(s) || /[\(\[]\s*m\s*[\)\]]/i.test(s) || /^price[\s_\-]+m$/i.test(s) || /^m[\s_\-]+price$/i.test(s)) return 'Medium';
        if (/\b(small|sm)\b/i.test(s) || /(?:7["”]|7\s*inch\b)/i.test(s) || s === 's' || /^s\s*[\(\[]/i.test(s) || /[\(\[]\s*s\s*[\)\]]/i.test(s) || /^price[\s_\-]+s$/i.test(s) || /^s[\s_\-]+price$/i.test(s)) return 'Small';
        if (/\b(regular|reg)\b/i.test(s)) return 'Regular';
        if (/\b(half|single)\b/i.test(s)) return 'Half';
        if (/\b(full|double)\b/i.test(s)) return 'Full';
        return null;
    }

    function parseInlineSizes(rawText) {
        if (!rawText || typeof rawText !== 'string') return [];
        const text = rawText.trim();
        if (!text) return [];

        const results = [];
        const seenSizes = new Set();
        const regex = /(?:^|[\s,;|\/\n])(small|medium|large|extra\s*large|xlarge|xl|x-large|family|jumbo|party|personal|regular|reg|half|full|single|double|s|m|l)\s*[:=\-\(]?\s*(?:rs\.?|pkr|₹)?\s*([0-9]+(?:\.[0-9]+)?)\s*\)?/gi;
        let match;
        while ((match = regex.exec(text)) !== null) {
            const rawSize = match[1].toLowerCase().replace(/[\s\-_]+/g, ' ').trim();
            const price = parseFloat(match[2]);
            if (price > 0) {
                const normSize = normalizeSizeName(rawSize);
                if (!seenSizes.has(normSize)) {
                    seenSizes.add(normSize);
                    results.push({
                        name: normSize,
                        size: normSize,
                        price,
                        sort_order: sizeOrderMap[normSize] || 99,
                        is_active: true
                    });
                }
            }
        }
        return results;
    }

    function sortSizes(sizesList) {
        if (!sizesList || sizesList.length === 0) return null;
        return [...sizesList].sort((a, b) => {
            const orderA = a.sort_order ?? (sizeOrderMap[a.size || a.name] || 99);
            const orderB = b.sort_order ?? (sizeOrderMap[b.size || b.name] || 99);
            return orderA - orderB;
        });
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
                    } else if (c.includes('size') || c.includes('variant') || c.includes('portion') || c.includes('option') || c.includes('serving')) {
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

        // A. Extract from dedicated size columns (e.g. Small, Medium, Large, XL columns)
        if (colMap.sizeCols && colMap.sizeCols.length > 0) {
            for (const sc of colMap.sizeCols) {
                const cellVal = String(row[sc.idx] || '').trim();
                const cellPrice = parseFloat(cellVal.replace(/[^0-9.]/g, '')) || 0;
                if (cellPrice > 0) {
                    parsedSizes.push({
                        name: sc.size,
                        size: sc.size,
                        price: cellPrice,
                        sort_order: sizeOrderMap[sc.size] || 99,
                        is_active: true
                    });
                }
            }
        }

        // B. Extract from Sizes column text if no size columns found
        if (parsedSizes.length === 0 && sizesRaw) {
            const inline = parseInlineSizes(sizesRaw);
            if (inline.length > 0) {
                parsedSizes = inline;
            } else {
                const singleNorm = normalizeSizeName(sizesRaw);
                if (singleNorm && cleanPrice > 0) {
                    parsedSizes = [{
                        name: singleNorm,
                        size: singleNorm,
                        price: cleanPrice,
                        sort_order: sizeOrderMap[singleNorm] || 99,
                        is_active: true
                    }];
                }
            }
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

    // Consolidate row-based size items (e.g. "Bonfire Pizza - Small", "Bonfire Pizza - Medium", stacked rows, repeated rows)
    const consolidatedItems = [];
    const itemMap = new Map();

    for (const item of rawItems) {
        let baseName = item.name;

        // Check if name has size suffix like "Item Name (Small)" or "Item Name - Large"
        const suffixMatch = item.name.match(/^(.+?)[\s\-_(\[]+(small|medium|large|extra\s*large|xlarge|xl|x-large|family|jumbo|personal|regular|half|full|s|m|l)[)\s\]]*$/i);
        if (suffixMatch && (!item.sizes || item.sizes.length === 0)) {
            baseName = suffixMatch[1].trim();
            const normSize = normalizeSizeName(suffixMatch[2]);
            const variantObj = {
                name: normSize,
                size: normSize,
                price: item.price,
                sort_order: sizeOrderMap[normSize] || 99,
                is_active: true
            };
            item.sizes = [variantObj];
        }

        item.name = baseName;
        const key = `${item.category.toLowerCase()}:::${baseName.toLowerCase()}`;

        if (itemMap.has(key)) {
            const existing = itemMap.get(key);

            if (item.sizes && item.sizes.length > 0) {
                if (!existing.sizes || existing.sizes.length === 0) {
                    existing.sizes = [];
                }
                for (const s of item.sizes) {
                    const sName = s.size || s.name;
                    const existingIdx = existing.sizes.findIndex(es => (es.size || es.name).toLowerCase() === sName.toLowerCase());
                    if (existingIdx !== -1) {
                        if (s.price > 0) existing.sizes[existingIdx].price = s.price;
                    } else {
                        existing.sizes.push(s);
                    }
                }
                existing.sizes = sortSizes(existing.sizes);
                if (existing.sizes && existing.sizes.length > 0) {
                    existing.price = existing.sizes[0].price;
                }
            } else if (item.price > 0 && existing.price !== item.price) {
                // Repeated row with different price: create variants
                if (!existing.sizes || existing.sizes.length === 0) {
                    existing.sizes = [
                        { name: 'Regular', size: 'Regular', price: existing.price, sort_order: 1.5, is_active: true }
                    ];
                }
                existing.sizes.push({
                    name: 'Variant ' + (existing.sizes.length + 1),
                    size: 'Variant ' + (existing.sizes.length + 1),
                    price: item.price,
                    sort_order: 10 + existing.sizes.length,
                    is_active: true
                });
                existing.sizes = sortSizes(existing.sizes);
                existing.price = existing.sizes[0].price;
            }

            if (item.description && !existing.description) {
                existing.description = item.description;
            }
        } else {
            if (item.sizes && item.sizes.length > 0) {
                item.sizes = sortSizes(item.sizes);
                item.price = item.sizes[0].price;
            }
            itemMap.set(key, item);
            consolidatedItems.push(item);
        }
    }

    console.log(JSON.stringify(consolidatedItems));
} catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
}
