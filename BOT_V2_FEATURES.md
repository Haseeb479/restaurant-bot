# WhatsApp Restaurant Bot v2 - Complete System Guide

## 🎯 What's New

### 1. **Language Detection (Urdu/English)**
- Bot automatically detects which language customer is using
- Responds ONLY in that language (no mixed responses)
- Detects Urdu characters automatically

**How it works:**
```
Customer: "السلام علیکم" (Urdu) → Bot responds in Urdu only
Customer: "Hello" (English) → Bot responds in English only
```

### 2. **Real Restaurant Menu Fetching**
Bot now:
✅ Queries database for customer's restaurant by phone number
✅ Fetches LIVE menu items with prices
✅ Shows menu without emojis (clean and professional)
✅ Handles multiple restaurants (each gets their own menu)

**Example:**
```
Customer Phone: 03001234567
↓
Database Lookup: Which restaurant owns this number?
↓
Restaurant: "Pizza Palace" found
↓
Fetch Menu: Pizza (800), Burger (400), Pasta (600)
↓
Display to customer: Clean menu with prices
```

### 3. **Smart Order Taking**
✅ Follows natural conversation flow
✅ Detects when customer is ready to order
✅ Collects: Name, Address, Phone, Items, Quantity
✅ Confirms total before finalizing
✅ No more confusion after menu display

**Conversation Flow:**
```
Bot: "Hello! Welcome to Pizza Palace"
Customer: "Show me menu"
Bot: "Here's our menu: Pizza, Burger, Pasta"
Customer: "I want 2 pizzas"
Bot: "Great! 2 pizzas for Rs. 1600. What's your address?"
Customer: "Mall Road, Lahore"
Bot: "Perfect! Order confirmation: 2 pizzas, total Rs. 1600. Ready?"
```

### 4. **Order Storage to Database**
When customer confirms order:
- ✅ Saves to `orders` table
- ✅ Stores: Customer phone, items, total, address, status
- ✅ Records: Timestamp, payment method, notes

**Database fields captured:**
```php
[
    'customer_phone' => '03001234567',
    'restaurant_id' => 1,
    'customer_name' => 'Ahmed',
    'customer_address' => 'Mall Road',
    'items' => ['Pizza', 'Burger'],
    'subtotal' => 1200,
    'total' => 1250,
    'payment_method' => 'cash',
    'status' => 'pending' // pending → confirmed → preparing → ready → on_way → delivered
]
```

### 5. **Order Tracking System**
✅ Customers can track order status anytime
✅ Shows progress: Order Placed → Confirmed → Preparing → Ready → On The Way → Delivered
✅ Displays estimated delivery time
✅ Shows current stage with visual indicator

**Customer access:**
```
Visit: http://localhost:8000/order-tracking.html
Enter: Phone number
See: All orders with real-time status
```

### 6. **Admin Notifications**
Restaurant owner is notified when:
✅ New order received (email + logs)
✅ Can see all pending orders in dashboard
✅ Can update order status
✅ Customer receives notification on status change

**Admin Dashboard:**
```
URL: http://localhost:8000/dashboard/{id}/orders
Shows:
- Pending orders count
- Orders in preparation
- Deliveries in progress
- All order details
- One-click status update
```

### 7. **Free Chat (No Restrictions)**
✅ Bot can talk about ANYTHING
✅ Not limited to menu/orders
✅ Natural, human-like conversations
✅ Full Gemini AI capabilities

**Examples:**
```
Customer: "What's the weather like?"
Bot: "I don't have real-time weather data, but I can help you with menu!"

Customer: "Tell me a joke"
Bot: "Why did the pizza maker go to the bank? To get more dough!"

Customer: "سب سے اچھی چیز کیا ہے؟"
Bot: "ہمارے پیتزے بہت لذیذ ہیں! آپ کو کیا چاہیے؟"
```

---

## 🔌 API Endpoints

### Get Restaurant by Phone
```
GET /api/restaurant-by-phone/{phone}

Response:
{
  "id": 1,
  "name": "Pizza Palace",
  "address": "Mall Road",
  "city": "Lahore",
  "delivery_charge": 50,
  "menu_items": [
    {"name": "Pizza", "price": 800},
    {"name": "Burger", "price": 400}
  ]
}
```

### Create Order
```
POST /api/orders/create

Body:
{
  "customer_phone": "03001234567",
  "restaurant_id": 1,
  "customer_name": "Ahmed",
  "customer_address": "Mall Road",
  "items": ["Pizza", "Burger"],
  "subtotal": 1200,
  "delivery_charge": 50,
  "total": 1250,
  "payment_method": "cash"
}
```

### Get Customer Orders
```
GET /api/orders/phone/{phone}

Response:
{
  "phone": "03001234567",
  "total_orders": 5,
  "orders": [{...}, {...}]
}
```

### Track Order
```
GET /api/orders/track/{orderId}

Response:
{
  "order_id": 1,
  "status": "preparing",
  "items": ["Pizza", "Burger"],
  "total": 1250,
  "current_stage": "In Kitchen",
  "estimated_time": "30-45 minutes"
}
```

### Get Restaurant Orders (Admin)
```
GET /api/restaurant/{restaurantId}/orders

Shows: All pending + in-progress orders
```

### Update Order Status (Admin)
```
PATCH /api/orders/{orderId}/status

Body:
{
  "status": "confirmed|preparing|ready|on_way|delivered|cancelled"
}
```

---

## 🚀 How to Use

### For Customers (WhatsApp):
1. Scan the QR code with WhatsApp
2. Send a message (Urdu or English)
3. Browse menu naturally
4. Place order by saying "I want..."
5. Bot will take address and confirm
6. Receive order confirmation

**Example Conversation (Urdu):**
```
You: "السلام علیکم"
Bot: "السلام علیکم! خوش آمدید"

You: "منو دکھاؤ"
Bot: "Menu: پیتزا 800, برگر 400, پاستا 600"

You: "2 پیتزا دینا"
Bot: "بہت خوب! 2 پیتزا - 1600 روپے۔ آپ کا ایڈریس کیا ہے؟"

You: "14-A, غازی روڈ، لاہور"
Bot: "آرڈر تیار ہے: 2 پیتزا، کل 1600 روپے۔ تصدیق ہے؟"

You: "جی، ہاں"
Bot: "آپ کا آرڈر نمبر: #1234۔ 30-45 منٹ میں پہنچ جائے گا"
```

### For Admins (Dashboard):
1. Go to: `http://localhost:8000/dashboard/{id}/orders`
2. See all incoming orders
3. Click "Update" to change status
4. Customer gets notified automatically
5. Track order fulfillment in real-time

### For Customers (Order Tracking):
1. Go to: `http://localhost:8000/order-tracking.html`
2. Enter phone number
3. See all orders
4. Click order to see full details
5. View live status and progress

---

## 📊 Order Status Flow

```
pending (Order received)
   ↓
confirmed (Restaurant confirmed)
   ↓
preparing (In kitchen)
   ↓
ready (Ready for delivery)
   ↓
on_way (Rider has it)
   ↓
delivered (Reached customer)
```

At each stage, customer gets a notification.

---

## 🤖 Bot Behavior

### What the Bot Does:
✅ Detects language automatically
✅ Shows restaurant-specific menu
✅ Takes orders step-by-step
✅ Saves orders to database
✅ Chats naturally (no restrictions)
✅ Handles any topic
✅ Professional, friendly tone

### What the Bot Doesn't Do:
❌ Mix languages (not responding in both Urdu + English)
❌ Show emojis in menu (clean display)
❌ Force menu topics (free to chat)
❌ Confuse after menu display (smooth order taking)
❌ Lose conversation context (remembers everything)

---

## 💾 Database Tables

### orders table
```sql
- id (primary)
- restaurant_id (foreign)
- customer_phone
- customer_name
- customer_address
- items (JSON array)
- subtotal
- delivery_charge
- total
- payment_method
- status (enum)
- notes
- created_at
- updated_at
```

### restaurants table
```sql
- id (primary)
- name
- owner_phone
- whatsapp_number
- address
- city
- menu_items (relationship)
- delivery_charge
- minimum_order
```

---

## 🔧 Configuration

> ⚠️ **Stale section.** This document describes an earlier Gemini-based bot
> (`bot-waiter-v2.js` + `.env.llm`), neither of which exists any more. The bot now
> uses Groq and reads the single project `.env` — see `.env.example` for the
> authoritative list of keys.
>
> A live `GEMINI_API_KEY` was previously pasted here in plain text. It has been
> removed, but **anything committed to git history stays there**: revoke that key
> in Google Cloud (APIs & Services → Credentials) rather than relying on this
> edit.

### API keys
Never write a real key into a document. Keys live in `.env` only, which is
git-ignored:
```
GROQ_API_KEY=<your key from https://console.groq.com/keys>
```

### Bot Configuration (bot-waiter-v2.js)
```javascript
- Language detection: Automatic (Urdu characters)
- Menu items limit: 15 items per restaurant
- Conversation history: Last 20 messages per user
- API timeout: 5 seconds
- Session ID: waiter-bot-v2
```

---

## 🎯 Next Steps (Optional)

1. **SMS Notifications** - Send SMS when order status changes
2. **Payment Integration** - Add Stripe/JazzCash payment
3. **Rating System** - Customers rate orders
4. **Loyalty Program** - Reward repeat customers
5. **Analytics Dashboard** - See sales trends
6. **WhatsApp Business API** - Replace whatsapp-web.js for production

---

## ⚙️ Running the System

### Start Laravel Server:
```bash
cd restaurant-bot
php artisan serve --port=8000
```

### Start WhatsApp Bot:
```bash
cd restaurant-bot
node bot-waiter-v2.js
```

### Access Points:
- Bot: Scan QR code in terminal
- Admin Orders: http://localhost:8000/dashboard/{id}/orders
- Customer Tracking: http://localhost:8000/order-tracking.html
- API: http://localhost:8000/api/...

---

## 🐛 Troubleshooting

**Bot not responding?**
- Check if Laravel server is running on :8000
- Check if WhatsApp QR has been scanned
- Check .env.llm has API key

**Order not saving?**
- Verify restaurant phone number in database
- Check Laravel logs: storage/logs/laravel.log

**Language detection not working?**
- Ensure message contains actual Urdu characters
- Bot defaults to English if mixed or no text

**Admin not getting notifications?**
- Check restaurant owner_email in database
- Verify Laravel mail configuration

---

**Bot Status:** ✅ RUNNING AND READY
**Language Detection:** ✅ ACTIVE
**Order Management:** ✅ ACTIVE
**Database Integration:** ✅ ACTIVE
**Admin Notifications:** ✅ ACTIVE
