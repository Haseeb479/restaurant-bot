import axios from 'axios';

const LARAVEL_API = process.env.LARAVEL_API || 'http://127.0.0.1:8000/api';

/**
 * OrderService — saves a confirmed order to the Laravel database.
 * Called by ChatHandler once the AI confirms the order is placed.
 * Returns the tracking code on success, null on failure.
 */
export class OrderService {
    async save(customerPhone, session) {
        try {
            // Extract the last 2 AI messages to use as order notes
            const notes = session.history
                .filter(h => h.role === 'assistant')
                .slice(-2)
                .map(h => h.content)
                .join('\n')
                .substring(0, 500)
                || 'Order taken via WhatsApp bot';

            const res = await axios.post(
                `${LARAVEL_API}/orders/create`,
                {
                    customer_phone:   customerPhone,
                    restaurant_id:    session.restaurant?.id || 1,
                    delivery_address: 'Collected via chat',
                    notes,
                    status:           'pending',
                    subtotal:         0,
                    delivery_charge:  session.restaurant?.delivery_charge || 0,
                    total:            0,
                    payment_method:   'cash_on_delivery',
                },
                { timeout: 5000 }
            );

            const trackingCode = res.data?.tracking_code;
            if (trackingCode) {
                console.log(`✅ Order saved — Tracking: ${trackingCode}`);
                return trackingCode;
            }

            console.log('⚠️  Order saved but no tracking code returned');
            return null;

        } catch (err) {
            console.log('⚠️  Could not save order:', err.message);
            return null;
        }
    }
}
