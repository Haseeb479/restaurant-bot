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

    // Header detection
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

        if (!nameCell) continue;

        if (catCell) {
            currentCategory = catCell;
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

        items.push({
            category: currentCategory,
            name: nameCell,
            price: cleanPrice,
            sizes: parsedSizes.length > 0 ? parsedSizes : null,
            description: descCell || null,
        });
    }

    console.log(JSON.stringify(items));
} catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
}
