import { test, describe } from 'node:test';
import assert from 'node:assert/strict';

import { OrderService, resolveItemPriceFromMenu } from '../src/services/OrderService.js';

const svc = new OrderService();

const sampleMenuItems = [
    {
        id: 10,
        restaurant_id: 1,
        name: 'Zinger Burger',
        price: 550.00,
        sizes: null,
        is_available: 1,
    },
    {
        id: 11,
        restaurant_id: 1,
        name: 'Crown Crust Pizza',
        price: 0,
        sizes: [
            { size: 'Small', price: 650 },
            { size: 'Medium', price: 1250 },
            { size: 'Large', price: 1750 },
        ],
        is_available: 1,
    },
    {
        id: 12,
        restaurant_id: 1,
        name: 'Mineral Water',
        price: 100.00,
        sizes: null,
        is_available: 1,
    },
];

const sampleDeals = [
    {
        id: 1,
        restaurant_id: 1,
        title: 'Midnight Zinger Deal',
        discount_value: 850.00,
        is_active: 1,
    }
];

describe('resolveItemPriceFromMenu — Authoritative Price Resolution', () => {
    test('resolves standard menu item by exact or case-insensitive name', () => {
        const res = resolveItemPriceFromMenu('Zinger Burger', null, sampleMenuItems, sampleDeals);
        assert.ok(res);
        assert.equal(res.matched, true);
        assert.equal(res.menuItemId, 10);
        assert.equal(res.unitPrice, 550);
    });

    test('resolves item with size variant correctly', () => {
        const res = resolveItemPriceFromMenu('Crown Crust Pizza', 'Large', sampleMenuItems, sampleDeals);
        assert.ok(res);
        assert.equal(res.matched, true);
        assert.equal(res.menuItemId, 11);
        assert.equal(res.unitPrice, 1750);
    });

    test('resolves item with size variant when size is in lower case', () => {
        const res = resolveItemPriceFromMenu('Crown Crust Pizza', 'medium', sampleMenuItems, sampleDeals);
        assert.ok(res);
        assert.equal(res.matched, true);
        assert.equal(res.unitPrice, 1250);
    });

    test('resolves item with size abbreviation (M, L, S)', () => {
        const resM = resolveItemPriceFromMenu('Crown Crust Pizza', 'M', sampleMenuItems, sampleDeals);
        assert.ok(resM);
        assert.equal(resM.unitPrice, 1250);

        const resL = resolveItemPriceFromMenu('Crown Crust Pizza', 'L', sampleMenuItems, sampleDeals);
        assert.ok(resL);
        assert.equal(resL.unitPrice, 1750);

        const resS = resolveItemPriceFromMenu('Crown Crust Pizza', 'S', sampleMenuItems, sampleDeals);
        assert.ok(resS);
        assert.equal(resS.unitPrice, 650);
    });

    test('resolves active deal by title', () => {
        const res = resolveItemPriceFromMenu('Midnight Zinger Deal', null, sampleMenuItems, sampleDeals);
        assert.ok(res);
        assert.equal(res.matched, true);
        assert.equal(res.dealId, 1);
        assert.equal(res.unitPrice, 850);
    });

    test('returns null for nonexistent item', () => {
        const res = resolveItemPriceFromMenu('Mystery Alien Fruit', null, sampleMenuItems, sampleDeals);
        assert.equal(res, null);
    });
});

describe('OrderService.parseOrderFromHistory — AI Pricing Tampering Immunity', () => {
    test('overrides manipulated AI prices with authoritative DB menu prices', () => {
        // Customer conversation where AI mistakenly or maliciously echoed a fake price of Rs. 10
        const fakeSummary = [
            '🧾 *Order Summary*',
            '2x Zinger Burger — Rs.10', // Fake: real price is 550 each -> 1100
            '─────────────────',
            'Subtotal: Rs.10',           // Fake subtotal
            'Delivery: Rs.0',            // Fake delivery
            '*Total: Rs.10*',            // Fake total
            '─────────────────',
            'Name: Haris Khan',
            'Payment: Cash on Delivery',
            'Deliver to: House 12, Street 4, F-8, Islamabad',
        ].join('\n');

        const session = {
            restaurant: {
                id: 1,
                name: 'Burger Joint',
                delivery_charge: 150.00,
                menu_items: sampleMenuItems,
                active_deals: sampleDeals,
            },
            history: [
                { role: 'user', content: '2 zinger burgers' },
                { role: 'assistant', content: fakeSummary },
                { role: 'user', content: 'confirm' },
            ],
        };

        const parsed = svc.parseOrderFromHistory(session);

        // Subtotal must be 2 * 550 = 1100 (DB price, NOT 10)
        assert.equal(parsed.subtotal, 1100);
        // Delivery charge must be 150 (Restaurant setting, NOT 0)
        assert.equal(parsed.deliveryCharge, 150);
        // Final Total MUST be 1100 + 150 = 1250 (NOT 10)
        assert.equal(parsed.total, 1250);

        // Items must have authoritative unit_price and subtotal
        assert.equal(parsed.items.length, 1);
        assert.equal(parsed.items[0].name, 'Zinger Burger');
        assert.equal(parsed.items[0].quantity, 2);
        assert.equal(parsed.items[0].unit_price, 550);
        assert.equal(parsed.items[0].subtotal, 1100);
    });

    test('multi-item order with sizes and delivery charge adheres strictly to formula', () => {
        const multiSummary = [
            '🧾 *Order Summary*',
            '1x Crown Crust Pizza (Large) — Rs.100', // Fake price
            '2x Mineral Water — Rs.20',              // Fake price
            '─────────────────',
            'Subtotal: Rs.120',
            'Delivery: Rs.50',
            '*Total: Rs.170*',
            '─────────────────',
            'Name: Fatima',
            'Deliver to: DHA Phase 5, Lahore',
        ].join('\n');

        const session = {
            restaurant: {
                id: 1,
                name: 'Pizza Spot',
                delivery_charge: 120.00,
                menu_items: sampleMenuItems,
                active_deals: sampleDeals,
            },
            history: [
                { role: 'assistant', content: multiSummary },
            ],
        };

        const parsed = svc.parseOrderFromHistory(session);

        // Expected: 1 x 1750 (Pizza Large) + 2 x 100 (Water) = 1950 subtotal
        assert.equal(parsed.subtotal, 1950);
        // Expected delivery: 120
        assert.equal(parsed.deliveryCharge, 120);
        // Expected total: 1950 + 120 = 2070
        assert.equal(parsed.total, 2070);
    });
});
