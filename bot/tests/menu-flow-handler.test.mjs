import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { MenuFlowHandler, STATES } from '../src/handlers/MenuFlowHandler.js';

describe('MenuFlowHandler — Deterministic Rule-Based Ordering', () => {
    const mockRestaurant = {
        id: 1,
        name: 'GrillCafe Lodhran',
        delivery_charge: 150,
        categories: [
            { id: 10, name: 'Burgers 🍔' },
            { id: 20, name: 'Naan & Roti 🫓' },
        ],
        menu_items: [
            { id: 101, category_id: 10, name: 'Zinger Burger', price: 350, is_available: true },
            { id: 102, category_id: 10, name: 'Beef Burger', price: 450, is_available: true },
            {
                id: 201,
                category_id: 20,
                name: 'Butter Naan',
                price: 50,
                sizes: [
                    { size: 'Single', price: 50 },
                    { size: 'Family (4x)', price: 180 },
                ],
                is_available: true,
            },
        ],
        active_deals: [
            { id: 501, title: 'Mega Deal (2 Zingers + 1L Drink)', price: 850, discount_value: 850, is_active: true },
        ],
    };

    function createSession() {
        return {
            restaurant: mockRestaurant,
            flowState: STATES.IDLE,
            cart: [],
            orderData: {
                customerName: null,
                contactPhone: null,
                deliveryAddress: null,
                deliveryLat: null,
                deliveryLng: null,
                paymentMethod: 'cash_on_delivery',
            },
        };
    }

    test('shows welcome and numbered categories in IDLE state', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        const result = await handler.handleMessage('Hi', session);

        assert.equal(result.orderReady, false);
        assert.match(result.reply, /Welcome to \*GrillCafe Lodhran\*/);
        assert.match(result.reply, /1️⃣ \*Special Deals & Offers 🌟\*/);
        assert.match(result.reply, /2️⃣ \*Burgers 🍔\*/);
        assert.match(result.reply, /3️⃣ \*Naan & Roti 🫓\*/);
    });

    test('selecting a category lists its items with prices', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Select category 2 (Burgers)
        const result = await handler.handleMessage('2', session);

        assert.equal(session.flowState, STATES.BROWSING_CATEGORY);
        assert.match(result.reply, /Burgers 🍔/);
        assert.match(result.reply, /1\. \*Zinger Burger\* — Rs\.350/);
        assert.match(result.reply, /2\. \*Beef Burger\* — Rs\.450/);
    });

    test('expands size variations into individual numbered items', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Select category 3 (Naan & Roti)
        const result = await handler.handleMessage('3', session);

        assert.equal(session.flowState, STATES.BROWSING_CATEGORY);
        assert.match(result.reply, /Naan & Roti 🫓/);
        assert.match(result.reply, /1\. \*Butter Naan \(Single\)\* — Rs\.50/);
        assert.match(result.reply, /2\. \*Butter Naan \(Family \(4x\)\)\* — Rs\.180/);
    });

    test('adds items to cart with accurate price and quantity calculation', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Open Burgers
        await handler.handleMessage('2', session);

        // Add 2x Zinger Burger (item 1)
        const result = await handler.handleMessage('1x2', session);

        assert.equal(session.cart.length, 1);
        assert.equal(session.cart[0].name, 'Zinger Burger');
        assert.equal(session.cart[0].quantity, 2);
        assert.equal(session.cart[0].unitPrice, 350);
        assert.equal(session.cart[0].subtotal, 700);

        // Subtotal: 700, Delivery: 150, Total: 850
        assert.match(result.reply, /Cart mein add ho gaya/);
        assert.match(result.reply, /Subtotal:\* Rs\.700/);
        assert.match(result.reply, /Total with Delivery:\* Rs\.850/);
    });

    test('cart view displays correct breakdown', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Browse Burgers and add 1 Zinger
        await handler.handleMessage('2', session);
        await handler.handleMessage('1', session);

        // Ask for cart
        const cartResult = await handler.handleMessage('cart', session);

        assert.match(cartResult.reply, /Aapka Shopping Cart/);
        assert.match(cartResult.reply, /1x\* Zinger Burger — Rs\.350/);
        assert.match(cartResult.reply, /Subtotal: Rs\.350/);
        assert.match(cartResult.reply, /Delivery Charge: Rs\.150/);
        assert.match(cartResult.reply, /Total Payable: Rs\.500/);
    });

    test('full checkout flow: Name -> Phone -> Address -> Summary -> Confirm', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Add item
        await handler.handleMessage('2', session);
        await handler.handleMessage('1x2', session); // 2 Zingers = Rs.700

        // Step 1: Checkout trigger
        const step1 = await handler.handleMessage('checkout', session);
        assert.equal(session.flowState, STATES.CHECKOUT_NAME);
        assert.match(step1.reply, /Step 1\/3/);
        assert.match(step1.reply, /Naam/);

        // Step 2: Name input
        const step2 = await handler.handleMessage('Muhammad Ali', session);
        assert.equal(session.flowState, STATES.CHECKOUT_PHONE);
        assert.equal(session.orderData.customerName, 'Muhammad Ali');
        assert.match(step2.reply, /Muhammad Ali/);
        assert.match(step2.reply, /Phone Number/);

        // Step 3: Phone input
        const step3 = await handler.handleMessage('03001234567', session);
        assert.equal(session.flowState, STATES.CHECKOUT_ADDRESS);
        assert.equal(session.orderData.contactPhone, '03001234567');
        assert.match(step3.reply, /Delivery Address/);

        // Step 4: Address input
        const step4 = await handler.handleMessage('Basti Jhok Wala, Lodhran', session);
        assert.equal(session.flowState, STATES.ORDER_CONFIRMATION);
        assert.equal(session.orderData.deliveryAddress, 'Basti Jhok Wala, Lodhran');
        assert.match(step4.reply, /Order Summary/);
        assert.match(step4.reply, /2x Zinger Burger — Rs\.700/);
        assert.match(step4.reply, /Total: Rs\.850/);
        assert.match(step4.reply, /Cash on Delivery \(COD\)/);

        // Step 5: Switch payment to JazzCash
        const jazzResult = await handler.handleMessage('jazzcash', session);
        assert.equal(session.orderData.paymentMethod, 'jazzcash');
        assert.match(jazzResult.reply, /Payment: JazzCash/);

        // Step 6: Customer confirms
        const confirmResult = await handler.handleMessage('yes', session);
        assert.equal(confirmResult.orderReady, true);
    });

    test('skips address step if customer already shared WhatsApp location pin', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        // Customer shares pin initially
        await handler.handleMessage('', session, { lat: 29.58968, lng: 71.60523, address: 'Near Railway Station, Lodhran' });

        // Customer selects item and starts checkout
        await handler.handleMessage('2', session);
        await handler.handleMessage('1', session);
        await handler.handleMessage('checkout', session);

        // Enter name
        await handler.handleMessage('Usman Tariq', session);

        // Enter phone -> Address step must be SKIPPED straight to Order Summary!
        const phoneResult = await handler.handleMessage('same', session);

        assert.equal(session.flowState, STATES.ORDER_CONFIRMATION);
        assert.match(phoneResult.reply, /Order Summary/);
        assert.match(phoneResult.reply, /Deliver to: Near Railway Station, Lodhran/);
    });

    test('clear command empties cart and resets state', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        await handler.handleMessage('2', session);
        await handler.handleMessage('1', session);
        assert.equal(session.cart.length, 1);

        const clearResult = await handler.handleMessage('clear', session);
        assert.equal(session.cart.length, 0);
        assert.equal(session.flowState, STATES.IDLE);
        assert.match(clearResult.reply, /cart khali kar diya gaya hai/i);
    });

    test('cancel command cancels order and resets state', async () => {
        const handler = new MenuFlowHandler();
        const session = createSession();

        await handler.handleMessage('2', session);
        await handler.handleMessage('1', session);
        await handler.handleMessage('checkout', session);

        const cancelResult = await handler.handleMessage('cancel', session);
        assert.equal(session.cart.length, 0);
        assert.equal(session.flowState, STATES.IDLE);
        assert.match(cancelResult.reply, /Order cancel kar diya gaya hai/i);
    });
});
