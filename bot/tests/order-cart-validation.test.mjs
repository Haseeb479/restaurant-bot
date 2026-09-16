import { test, describe } from 'node:test';
import assert from 'node:assert/strict';

import { OrderService } from '../src/services/OrderService.js';

const svc = new OrderService();

describe('OrderService.validateCart — Cart validation logic', () => {
    test('returns false for null or undefined parsed object', () => {
        assert.equal(svc.validateCart(null), false);
        assert.equal(svc.validateCart(undefined), false);
    });

    test('returns false for empty items array', () => {
        const parsed = {
            items: [],
            subtotal: 500,
            deliveryCharge: 50,
            total: 550,
        };
        assert.equal(svc.validateCart(parsed), false);
    });

    test('returns false for items with 0 quantity or missing name', () => {
        const parsedWithZeroQty = {
            items: [{ name: 'Burger', quantity: 0, unit_price: 500, subtotal: 0 }],
            subtotal: 500,
            total: 550,
        };
        assert.equal(svc.validateCart(parsedWithZeroQty), false);

        const parsedWithoutName = {
            items: [{ name: '', quantity: 1, unit_price: 500, subtotal: 500 }],
            subtotal: 500,
            total: 550,
        };
        assert.equal(svc.validateCart(parsedWithoutName), false);
    });

    test('returns false if subtotal <= 0', () => {
        const parsedZeroSub = {
            items: [{ name: 'Burger', quantity: 1, unit_price: 0, subtotal: 0 }],
            subtotal: 0,
            deliveryCharge: 100,
            total: 100,
        };
        assert.equal(svc.validateCart(parsedZeroSub), false);

        const parsedNegSub = {
            items: [{ name: 'Burger', quantity: 1, unit_price: -50, subtotal: -50 }],
            subtotal: -50,
            deliveryCharge: 100,
            total: 50,
        };
        assert.equal(svc.validateCart(parsedNegSub), false);
    });

    test('returns false if final total <= 0', () => {
        const parsedZeroTotal = {
            items: [{ name: 'Burger', quantity: 1, unit_price: 100, subtotal: 100 }],
            subtotal: 100,
            deliveryCharge: -100,
            total: 0,
        };
        assert.equal(svc.validateCart(parsedZeroTotal), false);
    });

    test('returns true for a valid cart with items and positive subtotal and total', () => {
        const validCart = {
            items: [
                { name: 'Zinger Burger', quantity: 2, unit_price: 550, subtotal: 1100 },
                { name: 'Mineral Water', quantity: 1, unit_price: 100, subtotal: 100 },
            ],
            subtotal: 1200,
            deliveryCharge: 150,
            total: 1350,
            deliveryAddress: 'House 123, Street 4, Karachi',
            contactPhone: '03001234567',
        };
        assert.equal(svc.validateCart(validCart), true);
    });
});
