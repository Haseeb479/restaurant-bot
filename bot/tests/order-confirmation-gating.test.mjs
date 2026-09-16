import { test, describe } from 'node:test';
import assert from 'node:assert/strict';

import { ChatHandler } from '../src/handlers/ChatHandler.js';

describe('ChatHandler — Order Confirmation Gating Flow', () => {
    test('isOrderConfirmed correctly identifies confirmed replies vs questions', () => {
        const handler = new ChatHandler(null);

        assert.equal(handler.isOrderConfirmed('Your order is placed! We are preparing it.'), true);
        assert.equal(handler.isOrderConfirmed('Aap ka order placed ho gaya hai.'), true);
        assert.equal(handler.isOrderConfirmed('Order has been placed successfully.'), true);

        // Asking user for confirmation must return false
        assert.equal(handler.isOrderConfirmed('Kya main aapka order confirm kar doon?'), false);
        assert.equal(handler.isOrderConfirmed('Shall I place your order now?'), false);
    });

    test('when order save succeeds, confirmation reply and tracking message are sent in sequence', async () => {
        const handler = new ChatHandler(null);

        const repliesSent = [];
        const fakeMsg = {
            reply: async (text) => {
                repliesSent.push(text);
                return true;
            },
        };

        const session = {
            restaurant: { id: 1, name: 'Foodio Burger', _closed: false },
            history: [],
        };

        handler.restaurants.getByBotNumber = async () => session.restaurant;
        handler.sessions.get = () => session;
        handler.sessions.trim = () => {};
        handler.groq.chat = async () => 'Your order is placed! Thank you.';
        
        let saveCalled = false;
        handler.orders.save = async (phone, sess) => {
            saveCalled = true;
            // Verify that when save() is called, NO confirmation reply has been sent yet
            assert.equal(repliesSent.length, 0, 'Confirmation message must NOT be sent before order save succeeds');
            return 'FB1234';
        };
        handler.orders.markOwnerNotified = async () => {};
        handler.notifier.notifyOwner = async () => true;

        await handler.handle(fakeMsg, '923001112233', '923009998877', 'Yes, confirm order');

        assert.equal(saveCalled, true);
        assert.equal(repliesSent.length, 2);
        assert.equal(repliesSent[0], 'Your order is placed! Thank you.');
        assert.match(repliesSent[1], /Your tracking code is: FB1234/);
    });

    test('when order save or cart validation fails, confirmation reply is NOT sent and failure message is sent', async () => {
        const handler = new ChatHandler(null);

        const repliesSent = [];
        const fakeMsg = {
            reply: async (text) => {
                repliesSent.push(text);
                return true;
            },
        };

        const session = {
            restaurant: { id: 1, name: 'Foodio Burger', _closed: false },
            history: [],
        };

        handler.restaurants.getByBotNumber = async () => session.restaurant;
        handler.sessions.get = () => session;
        handler.sessions.trim = () => {};
        // AI generated a confirmation text, but cart is invalid or DB save failed
        handler.groq.chat = async () => 'Your order is placed! Thank you.';

        let saveCalled = false;
        handler.orders.save = async () => {
            saveCalled = true;
            return null; // Cart validation failed or DB error
        };

        await handler.handle(fakeMsg, '923001112233', '923009998877', 'Confirm order');

        assert.equal(saveCalled, true);
        assert.equal(repliesSent.length, 1);
        // Confirmation reply was SUPPRESSED
        assert.notEqual(repliesSent[0], 'Your order is placed! Thank you.');
        // Failure message was sent instead
        assert.match(repliesSent[0], /something went wrong saving your order/i);
    });

    test('when conversation is normal chat, reply is sent and save is never called', async () => {
        const handler = new ChatHandler(null);

        const repliesSent = [];
        const fakeMsg = {
            reply: async (text) => {
                repliesSent.push(text);
                return true;
            },
        };

        const session = {
            restaurant: { id: 1, name: 'Foodio Burger', _closed: false },
            history: [],
        };

        handler.restaurants.getByBotNumber = async () => session.restaurant;
        handler.sessions.get = () => session;
        handler.sessions.trim = () => {};
        handler.groq.chat = async () => 'Our special today is the Zinger Burger for Rs. 550.';

        let saveCalled = false;
        handler.orders.save = async () => {
            saveCalled = true;
            return 'TRACK123';
        };

        await handler.handle(fakeMsg, '923001112233', '923009998877', 'What is your special?');

        assert.equal(saveCalled, false, 'save() must not be called on ordinary chat');
        assert.equal(repliesSent.length, 1);
        assert.equal(repliesSent[0], 'Our special today is the Zinger Burger for Rs. 550.');
    });
});
