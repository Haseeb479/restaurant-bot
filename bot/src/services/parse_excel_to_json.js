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

    // 1. Scan first 30 rows for table header
    let headerRowIndex = -1;
    let colMap = { category: -1, name: -1, price: -1, sizes: -1, desc: -1 };

    for (let r = 0; r < Math.min(rawRows.length, 30); r++) {
        const row = rawRows[r];
        if (!Array.isArray(row)) continue;

        const rowLower = row.map(cell => String(cell || '').trim().toLowerCase());

        let nameIdx = -1;
        let priceIdx = -1;

        for (let idx = 0; idx < rowLower.length; idx++) {
            const c = rowLower[idx];
            if (c.includes('item') || c.includes('dish') || c.includes('product') || c === 'name' || c.endsWith(' name')) {
                nameIdx = idx;
            }
            if (c.includes('price') || c.includes('rate') || c.includes('rs') || c.includes('₹') || c.includes('pkr') || c.includes('cost') || c.includes('amount')) {
                priceIdx = idx;
            }
        }

        if (nameIdx !== -1 && priceIdx !== -1) {
            headerRowIndex = r;
            colMap.name = nameIdx;
            colMap.price = priceIdx;

            for (let idx = 0; idx < rowLower.length; idx++) {
                if (idx !== nameIdx && idx !== priceIdx) {
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

    // Default column indices if no named header: 0=Cat, 1=Name, 2=Price, 3=Sizes, 4=Desc
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

        const cleanPrice = parseFloat(priceRaw.replace(/[^0-9.]/g, '')) || 0;

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

        if (cleanPrice <= 0 && parsedSizes.length === 0) {
            // Might be a category banner without decoration
            if (nameCell.length <= 30 && !nameCell.includes('Rs') && !nameCell.includes('₹')) {
                currentCategory = nameCell.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
            }
            continue;
        }

        items.push({
            category: currentCategory,
            name: nameCell,
            price: (parsedSizes.length > 0 && cleanPrice <= 0) ? parsedSizes[0].price : cleanPrice,
            sizes: parsedSizes.length > 0 ? parsedSizes : null,
            description: descCell || null,
        });
    }

    console.log(JSON.stringify(items));
} catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
}
