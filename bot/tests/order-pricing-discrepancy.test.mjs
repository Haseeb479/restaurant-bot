import test from 'node:test';
import assert from 'node:assert/strict';
import { resolveItemPriceFromMenu, OrderService } from '../src/services/OrderService.js';
import { findRestaurantMenuFiles, ChatHandler } from '../src/handlers/ChatHandler.js';

test('resolveItemPriceFromMenu — exact-match takes precedence over substring', () => {
    const menuItems = [
        { id: 1, name: 'Butter Naan', price: 50 },
        { id: 2, name: 'Naan', price: 40 },
        { id: 3, name: 'Garlic Naan', price: 60 },
        { id: 4, name: 'Fresh Lime Soda', price: 50 },
    ];

    // Searching for "Naan" should match "Naan" (40), NOT "Butter Naan" (50)
    const naanResult = resolveItemPriceFromMenu('Naan', null, menuItems);
    assert.ok(naanResult, 'Naan should be resolved');
    assert.equal(naanResult.canonicalName, 'Naan');
    assert.equal(naanResult.unitPrice, 40);

    // Searching for "Butter Naan" should match "Butter Naan" (50)
    const butterResult = resolveItemPriceFromMenu('Butter Naan', null, menuItems);
    assert.ok(butterResult, 'Butter Naan should be resolved');
    assert.equal(butterResult.canonicalName, 'Butter Naan');
    assert.equal(butterResult.unitPrice, 50);

    // Searching for "Fresh Lime Soda" should match "Fresh Lime Soda" (50)
    const sodaResult = resolveItemPriceFromMenu('Fresh Lime Soda', null, menuItems);
    assert.ok(sodaResult, 'Fresh Lime Soda should be resolved');
    assert.equal(sodaResult.canonicalName, 'Fresh Lime Soda');
    assert.equal(sodaResult.unitPrice, 50);
});

test('OrderService — recalculateTotalsFromMenu computes exact 570 bill for 3x Naan and 4x Lime Soda', () => {
    const orders = new OrderService();
    const menuItems = [
        { id: 1, name: 'Naan', price: 40 },
        { id: 2, name: 'Butter Naan', price: 50 },
        { id: 3, name: 'Fresh Lime Soda', price: 50 },
    ];

    const parsed = {
        items: [
            { name: 'Naan', quantity: 3, unit_price: 50, subtotal: 150 }, // AI hallucinated 50 each
            { name: 'Fresh Lime Soda', quantity: 4, unit_price: 50, subtotal: 200 },
        ],
        subtotal: 350,
        deliveryCharge: 250,
        total: 600,
    };

    orders.recalculateTotalsFromMenu(parsed, menuItems, [], 250);

    assert.equal(parsed.subtotal, 320, 'Subtotal must be recalculated to 320 (3x40 + 4x50)');
    assert.equal(parsed.deliveryCharge, 250, 'Delivery charge must be 250');
    assert.equal(parsed.total, 570, 'Grand total must be 570 (320 + 250)');
    assert.equal(parsed.items[0].unit_price, 40);
    assert.equal(parsed.items[0].subtotal, 120);
    assert.equal(parsed.items[1].unit_price, 50);
    assert.equal(parsed.items[1].subtotal, 200);
});

test('ChatHandler.harmonizeConfirmationBill — corrects hallucinated totals in WhatsApp confirmation text', () => {
    const handler = new ChatHandler(null);

    const order = {
        subtotal: 320,
        deliveryCharge: 250,
        total: 570,
        items: [
            { name: 'Naan', quantity: 3, unit_price: 40, subtotal: 120 },
            { name: 'Fresh Lime Soda', quantity: 4, unit_price: 50, subtotal: 200 },
        ],
    };

    const hallucinatedReply =
        `Your order is placed! 🎉\n\n` +
        `🧾 *Order Summary*\n` +
        `3x Naan — Rs. 150\n` +
        `4x Fresh Lime Soda — Rs. 200\n` +
        `─────────────────\n` +
        `Subtotal: Rs. 350\n` +
        `Delivery: Rs. 250\n` +
        `*Total: Rs. 600*\n` +
        `─────────────────\n` +
        `Total payable: Rs. 600`;

    const harmonized = handler.harmonizeConfirmationBill(hallucinatedReply, order);

    assert.ok(harmonized.includes('3x Naan — Rs. 120') || harmonized.includes('3x Naan — Rs.120'), 'Item line must show authoritative 120');
    assert.ok(harmonized.includes('Subtotal: Rs. 320') || harmonized.includes('Subtotal: Rs.320'), 'Subtotal must show authoritative 320');
    assert.ok(harmonized.includes('Total: Rs. 570') || harmonized.includes('Total: Rs.570'), 'Total must show authoritative 570');
    assert.ok(harmonized.includes('Total payable: Rs. 570') || harmonized.includes('Total payable: Rs.570'), 'Total payable must show authoritative 570');
    assert.ok(!harmonized.includes('Rs. 600') && !harmonized.includes('Rs.600'), 'Hallucinated 600 must be completely removed');
});

test('findRestaurantMenuFiles — finds CSV file located under public/menus', async () => {
    const fs = await import('fs');
    const path = await import('path');
    const testDir = path.resolve(process.cwd(), 'public/menus');
    if (!fs.existsSync(testDir)) fs.mkdirSync(testDir, { recursive: true });
    const testFilePath = path.join(testDir, 'menu_1_1787037269.csv');
    if (!fs.existsSync(testFilePath)) fs.writeFileSync(testFilePath, 'Name,Price\nTest Item,100\n');

    const res = findRestaurantMenuFiles(1, 'uploads/menus/menu_1_1787037269.csv', null);
    assert.ok(res.excelPath, 'excelPath must be resolved even if DB path has uploads/menus prefix');
    assert.ok(res.excelPath.includes('menu_1_1787037269.csv'), 'Resolved path must target menu_1_1787037269.csv');
});

test('OrderService — Butter Naan is preserved with exact canonical name and Rs.50 price (Total Rs.600)', () => {
    const orders = new OrderService();
    const menuItems = [
        { id: 38, name: 'Naan', price: 40 },
        { id: 39, name: 'Butter Naan', price: 50 },
        { id: 40, name: 'Garlic Naan', price: 60 },
        { id: 45, name: 'Fresh Lime Soda', price: 50 },
    ];

    // Case 1: AI summary writes "3x Butter Naan"
    const parsed1 = {
        items: [
            { name: 'Butter Naan', quantity: 3, unit_price: 50, subtotal: 150 },
            { name: 'Fresh Lime Soda', quantity: 4, unit_price: 50, subtotal: 200 },
        ],
        subtotal: 350,
        deliveryCharge: 250,
        total: 600,
    };
    orders.recalculateTotalsFromMenu(parsed1, menuItems, [], 250);

    assert.equal(parsed1.items[0].name, 'Butter Naan', 'Must NOT be downgraded to plain Naan');
    assert.equal(parsed1.items[0].unit_price, 50, 'Butter Naan price must be 50');
    assert.equal(parsed1.items[0].subtotal, 150);
    assert.equal(parsed1.items[1].name, 'Fresh Lime Soda');
    assert.equal(parsed1.items[1].unit_price, 50);
    assert.equal(parsed1.items[1].subtotal, 200);
    assert.equal(parsed1.subtotal, 350, 'Subtotal must be 350 (3x50 + 4x50)');
    assert.equal(parsed1.deliveryCharge, 250);
    assert.equal(parsed1.total, 600, 'Grand total must be 600 (350 + 250)');

    // Case 2: AI summary writes "3x Naan (Butter)"
    const parsed2 = {
        items: [
            { name: 'Naan', size: 'Butter', quantity: 3, unit_price: 50, subtotal: 150 },
            { name: 'Fresh Lime Soda', quantity: 4, unit_price: 50, subtotal: 200 },
        ],
        subtotal: 350,
        deliveryCharge: 250,
        total: 600,
    };
    orders.recalculateTotalsFromMenu(parsed2, menuItems, [], 250);

    assert.equal(parsed2.items[0].name, 'Butter Naan', 'Composite candidate must resolve to Butter Naan');
    assert.equal(parsed2.items[0].unit_price, 50, 'Butter Naan price must be 50');
    assert.equal(parsed2.items[0].subtotal, 150);
    assert.equal(parsed2.items[0].size, null, 'Composite match clears separate size');
    assert.equal(parsed2.subtotal, 350);
    assert.equal(parsed2.total, 600);
});

