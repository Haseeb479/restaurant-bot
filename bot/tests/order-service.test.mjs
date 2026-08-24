import { test, describe } from 'node:test';
import assert from 'node:assert/strict';

import { OrderService } from '../src/services/OrderService.js';

/**
 * Customer-name capture is parsed out of the AI's Order Summary the same brittle
 * way the address and totals are — by regex over the assistant's text. That kind
 * of parsing regresses silently (see the tracking-code break that motivated
 * tracking-handler.test.mjs), so pin the behaviour down here.
 *
 * Importing OrderService is safe: getDbPool() is lazy, and parseOrderFromHistory
 * never touches it. Only save() opens a connection, which we don't call.
 */

const svc = new OrderService();

/** A realistic confirmed Order Summary, with the Name/Payment/Deliver-to block. */
function summary({ name = 'Ahmed', extra = '' } = {}) {
    return [
        '🧾 *Order Summary*',
        '1x Zinger Burger — Rs.450',
        '─────────────────',
        'Subtotal: Rs.450',
        'Delivery: Rs.50',
        '*Total: Rs.500*',
        '─────────────────',
        `Name: ${name}`,
        'Payment: Cash on Delivery',
        'Deliver to: 14-A Mall Road, Lahore',
        '',
        'Kya main aapka order confirm kar doon? ✅',
        extra,
    ].join('\n');
}

/** Build a session whose final assistant turn is `assistantContent`. */
function sessionWith(assistantContent, opts = {}) {
    return {
        restaurant: { id: 1, name: 'Test Restaurant', delivery_charge: 50 },
        customerName: opts.customerName,
        history: [
            { role: 'user', content: 'menu' },
            { role: 'user', content: '1 zinger burger' },
            { role: 'assistant', content: assistantContent },
            { role: 'user', content: 'haan confirm' },
        ],
    };
}

describe('OrderService.parseOrderFromHistory — customer name', () => {
    test('reads a plain English name from the summary', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Ahmed' })));
        assert.equal(parsed.customerName, 'Ahmed');
    });

    test('reads a two-word name', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Ali Raza' })));
        assert.equal(parsed.customerName, 'Ali Raza');
    });

    test('reads a Roman-Urdu "Naam:" label', () => {
        const content = summary({ name: 'PLACEHOLDER' }).replace('Name: PLACEHOLDER', 'Naam: Bilal Khan');
        const parsed = svc.parseOrderFromHistory(sessionWith(content));
        assert.equal(parsed.customerName, 'Bilal Khan');
    });

    test('tolerates the label being bolded as *Name:*', () => {
        const content = summary({ name: 'X' }).replace('Name: X', '*Name:* Sara');
        const parsed = svc.parseOrderFromHistory(sessionWith(content));
        assert.equal(parsed.customerName, 'Sara');
    });

    test('strips trailing punctuation from the name', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Usman.' })));
        assert.equal(parsed.customerName, 'Usman');
    });

    test('keeps only the first clause when the model appends the address', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Hamza, Gulberg' })));
        assert.equal(parsed.customerName, 'Hamza');
    });
});

describe('OrderService.parseOrderFromHistory — name is null when not real', () => {
    test('an unfilled [Customer Name] placeholder is rejected', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: '[Customer Name]' })));
        assert.equal(parsed.customerName, null);
    });

    test('the generic word "Customer" is rejected', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Customer' })));
        assert.equal(parsed.customerName, null);
    });

    test('a summary with no Name line yields null', () => {
        const content = summary({ name: 'X' }).replace('Name: X\n', '');
        const parsed = svc.parseOrderFromHistory(sessionWith(content));
        assert.equal(parsed.customerName, null);
    });

    test('a "what is your name?" question is not mistaken for an answer', () => {
        // No colon-delimited Name line anywhere — only the question.
        const content = 'Aap ka naam kya hai? Order kis naam se book karun?';
        const parsed = svc.parseOrderFromHistory(sessionWith(content));
        assert.equal(parsed.customerName, null);
    });
});

describe('OrderService.parseOrderFromHistory — session fallback', () => {
    test('falls back to a name already captured on the session', () => {
        const content = summary({ name: 'X' }).replace('Name: X\n', ''); // no name in this summary
        const parsed = svc.parseOrderFromHistory(sessionWith(content, { customerName: 'Zara' }));
        assert.equal(parsed.customerName, 'Zara');
    });

    test('a freshly parsed name wins over the session value', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Naveed' }), { customerName: 'Stale' }));
        assert.equal(parsed.customerName, 'Naveed');
    });
});

describe('OrderService.parseOrderFromHistory — name line does not disturb the rest', () => {
    test('totals and address still parse with the Name line present', () => {
        const parsed = svc.parseOrderFromHistory(sessionWith(summary({ name: 'Ahmed' })));
        assert.equal(parsed.subtotal, 450);
        assert.equal(parsed.total, 500);
        assert.equal(parsed.deliveryCharge, 50);
        assert.match(parsed.deliveryAddress, /Mall Road/);
        assert.equal(parsed.items.length, 1);
        assert.equal(parsed.items[0].quantity, 1);
    });
});
