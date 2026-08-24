import { test, describe } from 'node:test';
import assert from 'node:assert/strict';

import { TrackingHandler } from '../src/handlers/TrackingHandler.js';

/**
 * These tests exist because of a real break: hardening the tracking-code
 * generator (finding H-04) changed the code shape from `TRK-FEZ-8379` to
 * `F-7VAWB4PGKWV5J9DP`, and `isTrackingCode` still only matched the old shapes.
 * The bot handed out codes it could not recognise — a customer pasting their own
 * code got small talk from the AI instead of an order status.
 *
 * So: whenever the generator changes, this file must fail.
 */
describe('TrackingHandler.isTrackingCode', () => {
    // Sampled from the real alphabet `0123456789ABCDEFGHJKMNPQRSTVWXYZ`.
    const CURRENT = [
        'F-7VAWB4PGKWV5J9DP',   // an actual code from the orders table
        'FEZ-0123456789ABCDEF',
        'ORD-ZZZZZZZZZZZZZZZZ',
        'ABCDE-HJKMNPQRSTVWXYZ0',
    ];

    for (const code of CURRENT) {
        test(`recognises the current format: ${code}`, () => {
            assert.equal(TrackingHandler.isTrackingCode(code), true);
        });
    }

    test('recognises the current format typed in lower case', () => {
        assert.equal(TrackingHandler.isTrackingCode('f-7vawb4pgkwv5j9dp'), true);
    });

    test('recognises it with surrounding whitespace', () => {
        assert.equal(TrackingHandler.isTrackingCode('  F-7VAWB4PGKWV5J9DP \n'), true);
    });

    // Orders placed before the change still carry these.
    const LEGACY = ['TRK-FEZ-8379', 'TRK-FEZ-1000', 'JC-2026-00042', 'FEZ-001'];

    for (const code of LEGACY) {
        test(`still recognises the legacy format: ${code}`, () => {
            assert.equal(TrackingHandler.isTrackingCode(code), true);
        });
    }

    // Anything here would be stolen from the AI chat handler.
    const NOT_CODES = [
        ['ordinary chat',        'hello, do you have zinger burger?'],
        ['a bare word',          'MENU'],
        ['a phone number',       '03001234567'],
        ['a price',              'Rs. 1200'],
        ['an address',           'House 12, Gulberg, Lahore'],
        ['too short a suffix',   'FEZ-7VAWB4PGKWV5J9D'],
        ['too long a suffix',    'FEZ-7VAWB4PGKWV5J9DPQ'],
        ['a prefix that is too long', 'ABCDEF-7VAWB4PGKWV5J9DP'],
        ['empty',                ''],
        ['whitespace only',      '   '],
    ];

    for (const [why, text] of NOT_CODES) {
        test(`does not claim ${why}`, () => {
            assert.equal(TrackingHandler.isTrackingCode(text), false);
        });
    }

    test('excludes I, L, O and U — the alphabet omits them deliberately', () => {
        // Substituting a single excluded letter must stop it matching, otherwise
        // a typo'd code would be looked up instead of answered as chat.
        for (const letter of ['I', 'L', 'O', 'U']) {
            const code = `FEZ-${letter}VAWB4PGKWV5J9DP`;
            assert.equal(
                TrackingHandler.isTrackingCode(code), false,
                `${code} should not match — ${letter} is not in the alphabet`
            );
        }
    });

    test('survives a null or undefined body', () => {
        assert.equal(TrackingHandler.isTrackingCode(null), false);
        assert.equal(TrackingHandler.isTrackingCode(undefined), false);
    });
});

/**
 * A tracking code is a bearer token — it can be forwarded, and the bot answers
 * whoever sends it, from any number. The reply must therefore redact the same
 * things the public web page does (see Order::showsRiderContact()).
 */
describe('TrackingHandler.formatReplyFromDb redaction', () => {
    const handler = new TrackingHandler();

    const order = (overrides = {}) => ({
        tracking_code:   'F-7VAWB4PGKWV5J9DP',
        restaurant_name: 'Fezio',
        status:          'preparing',
        total:           1250,
        rider_name:      'Bilal Ahmed Khan',
        rider_phone:     '923451112223',
        ...overrides,
    });

    test('names the rider by first name only', () => {
        const reply = handler.formatReplyFromDb(order());
        assert.match(reply, /Bilal/);
        assert.doesNotMatch(reply, /Ahmed/);
        assert.doesNotMatch(reply, /Khan/);
    });

    test('withholds the rider phone until the order is in transit', () => {
        for (const status of ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled']) {
            const reply = handler.formatReplyFromDb(order({ status }));
            assert.doesNotMatch(
                reply, /923451112223/,
                `rider phone leaked while status was ${status}`
            );
        }
    });

    test('publishes the rider phone once out for delivery', () => {
        const reply = handler.formatReplyFromDb(order({ status: 'out_for_delivery' }));
        assert.match(reply, /923451112223/);
    });

    test('omits the rider block entirely when none is assigned', () => {
        const reply = handler.formatReplyFromDb(order({ rider_name: null, rider_phone: null }));
        assert.doesNotMatch(reply, /Rider/);
    });

    test('still shows the essentials a customer needs', () => {
        const reply = handler.formatReplyFromDb(order());
        assert.match(reply, /F-7VAWB4PGKWV5J9DP/);
        assert.match(reply, /Fezio/);
        assert.match(reply, /Preparing in Kitchen/);
        assert.match(reply, /1,250/);
        assert.match(reply, /\/track\/F-7VAWB4PGKWV5J9DP/);
    });
});
