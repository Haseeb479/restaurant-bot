<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppAiBotService
 *
 * Full AI ordering bot — mirrors the original JS ChatHandler mechanics:
 * - Live menu from DB (categories + items with sizes)
 * - Full conversational ordering flow with Groq AI
 * - Conversation history per customer (45-min session cache)
 * - Order confirmation detection → saves order to DB
 * - Tracking code replies
 * - Graceful fallback when AI is unavailable
 */
class WhatsAppAiBotService
{
    private const GROQ_API_URL  = 'https://api.groq.com/openai/v1/chat/completions';
    private const PRIMARY_MODEL = 'llama-3.3-70b-versatile';
    private const FAST_MODEL    = 'llama-3.1-8b-instant';
    private const SESSION_TTL   = 45; // minutes

    // ──────────────────────────────────────────────────────────────────────────
    //  Public entry point
    // ──────────────────────────────────────────────────────────────────────────

    public function handle(Restaurant $restaurant, string $customerPhone, string $recipientJid, string $messageText): void
    {
        $text = trim($messageText);
        if ($text === '') {
            return;
        }

        // 1. Handle explicit tracking code request
        if (preg_match('/^[A-Za-z]{2,4}\d{4,6}$/', $text) ||
            preg_match('/^(track|status|order)\s+([A-Za-z0-9-]+)$/i', $text, $m)) {
            $trackingCode = strtoupper(trim(isset($m[2]) ? $m[2] : $text));
            $reply = $this->buildTrackingReply($restaurant, $trackingCode);
            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
            return;
        }

        // 2. Load / create session history (max 20 messages)
        $sessionKey = "wa_session_{$restaurant->id}_{$customerPhone}";
        $history    = Cache::get($sessionKey, []);

        $history[] = ['role' => 'user', 'content' => $text];
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // 3. Build system prompt from live DB menu
        $systemPrompt = $this->buildSystemPrompt($restaurant);

        // 4. Call Groq AI
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        $reply = $this->callGroq($messages);

        if ($reply === null) {
            // AI unavailable — smart fallback (don't pop user message so history remains intact)
            $reply = $this->smartFallback($text, $restaurant);
            Log::warning("WhatsApp AI: Groq unavailable, using fallback for {$restaurant->name} — customer: {$customerPhone}");
        } else {
            $history[] = ['role' => 'assistant', 'content' => $reply];
            if (count($history) > 20) {
                $history = array_slice($history, -20);
            }
        }

        // 5. Persist updated session
        Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));

        // 6. Detect order confirmation and save to DB
        if ($this->isOrderConfirmed($reply)) {
            $trackingCode = $this->saveOrderFromHistory($restaurant, $customerPhone, $history);
            if ($trackingCode) {
                $reply .= "\n\n🎉 *Your Tracking Code: {$trackingCode}*\nSend this code anytime to check your order status!";
            }
        }

        // 7. Send reply back through EvolutionAPI
        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Groq API Call
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function callGroq(array $messages): ?string
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            Log::error('WhatsApp AI: GROQ_API_KEY is not set in .env — bot cannot function properly!');
            return null;
        }

        foreach ([self::PRIMARY_MODEL, self::FAST_MODEL] as $model) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(15)
                    ->post(self::GROQ_API_URL, [
                        'model'       => $model,
                        'messages'    => $messages,
                        'temperature' => 0.7,
                        'max_tokens'  => 700,
                    ]);

                if ($response->successful()) {
                    $data  = $response->json();
                    $reply = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
                    if ($reply !== '') {
                        return $reply;
                    }
                }

                Log::warning("WhatsApp AI: Groq [{$model}] returned status " . $response->status() . " — " . $response->body());

            } catch (\Throwable $e) {
                Log::warning("WhatsApp AI: Groq [{$model}] exception: " . $e->getMessage());
            }
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  System Prompt — mirrors PromptBuilder.js exactly
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(Restaurant $restaurant): string
    {
        $name    = $restaurant->name ?: 'Our Restaurant';
        $address = $restaurant->address ?: ($restaurant->city ?: 'City Center');
        $hours   = $restaurant->hours ?: '10 AM – 11 PM';
        $delivery = (float) ($restaurant->delivery_charge ?? 50);
        $minOrder = (float) ($restaurant->minimum_order ?? 0);

        $menuText  = $this->buildMenuText($restaurant);
        $dealsText = $this->buildDealsText($restaurant);

        return <<<PROMPT
You are Zain, a warm, polite, and professional WhatsApp ordering waiter at "{$name}" restaurant in Pakistan.

RESTAURANT INFO:
- Name: {$name}
- Address: {$address}
- Delivery Charge: Rs. {$delivery}
- Minimum Order: Rs. {$minOrder}
- Hours: {$hours}

{$menuText}{$dealsText}CORE PILLARS & STRICT OPERATING RULES:

1. MENU IS THE ONLY SOURCE OF TRUTH (STRICT ZERO HALLUCINATION):
- Sell ONLY items and sizes listed in the MENU section above.
- If a customer asks for an item NOT in our menu, politely inform them:
  "Yeh item hamare menu mein available nahi hai. Hamare paas [mention 2-3 available items] available hain! 😊"
- NEVER invent, assume, or hallucinate food items, prices, extra discounts, or deals not listed above.

2. SCOPE & DOMAIN BOUNDARY:
- You are STRICTLY a restaurant waiter. You ONLY discuss food, menu, deals, restaurant timings, delivery, payment, and taking orders.
- If customer asks off-topic questions, politely deflect:
  "Main to sirf {$name} ka waiter hoon aur aapke liye mazedar khana deliver karwa sakta hoon! 🍔 Aaj kya khana pasand karein ge?"

3. LANGUAGE HANDLING (MIRROR THE CUSTOMER'S EXACT STYLE):
- Urdu script message → Reply in Urdu script
- Roman Urdu message (e.g. "kya deal hai", "khana chahiye") → Reply in Roman Urdu
- English message → Reply in English
- Mixed → Match their natural Pakistani casual tone.
- NEVER switch language unless the customer changes first.

4. STEP-BY-STEP ORDERING & DOUBLE-CHECK CONFIRMATION:
- Step 1: Clarify items, size variants (Small/Medium/Large), and quantity.
- Step 2: Ask for the customer's name and contact number. If they say "same number", use their WhatsApp number automatically.
- Step 3: Ask for complete delivery address.
- Step 4: Ask payment method: Cash on Delivery / JazzCash / EasyPaisa.
- Step 5: Show a full itemized Order Summary with exact subtotal, delivery fee, and grand total.
- Step 6: DOUBLE-CHECK: Ask clearly: "Kya main aapka order confirm kar doon? ✅"
- Step 7: ONLY when the customer confirms (e.g. "haan", "yes", "confirm", "theek hai"), say: "Your order is placed!" and state the final total.

5. BILL CALCULATION RULES (CRITICAL):
- YOU calculate all subtotals and totals yourself — NEVER tell the customer to add it up.
- Order Summary format:
─────────────────
🧾 *Order Summary*
1x [Item Name] — Rs.X
2x [Item Name] (Size) — Rs.X
─────────────────
Subtotal: Rs.X
Delivery: Rs.{$delivery}
*Total: Rs.X*
─────────────────
Name: [Customer Name]
Phone: [Contact Number]
Payment: [Method]
Deliver to: [Address]

Kya main aapka order confirm kar doon? ✅

- The Name, Phone, Payment and Deliver to lines are read by the system to save the order — always fill them with real values, NEVER leave bracket placeholders.

6. ESCALATION RULE:
- If customer asks for a human or sounds frustrated: "I'm connecting you with our team right away — please hold! 🙏"

7. STRICT STYLE RULES:
- Short, crisp replies (2-5 lines max) — this is WhatsApp chat, not an email.
- Friendly, warm emojis 😊
- NEVER repeat the same sentence twice.
- When final confirmation is given, ALWAYS include: "Your order is placed!"
PROMPT;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Menu text builder — mirrors PromptBuilder.buildMenuText()
    // ──────────────────────────────────────────────────────────────────────────

    private function buildMenuText(Restaurant $restaurant): string
    {
        $name = $restaurant->name ?: 'this restaurant';

        // Priority 1: Items from DB (with categories and sizes)
        $categories = $restaurant->categories()
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)->with('sizes')])
            ->get();

        $menuLines = '';
        foreach ($categories as $cat) {
            if ($cat->menuItems->isEmpty()) {
                continue;
            }
            $menuLines .= "\n[Category: {$cat->name}]\n";
            foreach ($cat->menuItems as $item) {
                $line = "{$item->name}";
                // Include size variants if they exist
                if ($item->sizes && $item->sizes->isNotEmpty()) {
                    $parts = $item->sizes->map(fn ($s) => "{$s->size}: Rs." . number_format($s->price, 0))->join(' / ');
                    $line .= " — {$parts}";
                } else {
                    $line .= " — Rs." . number_format((float) $item->price, 0);
                }
                if ($item->description) {
                    $line .= " ({$item->description})";
                }
                $menuLines .= "• {$line}\n";
            }
        }

        // Fallback: flat list without categories
        if ($menuLines === '') {
            $items = $restaurant->menuItems()->where('is_available', true)->with('sizes')->get();
            if ($items->isNotEmpty()) {
                $menuLines = "\nMENU:\n";
                foreach ($items as $item) {
                    $line = "{$item->name}";
                    if ($item->sizes && $item->sizes->isNotEmpty()) {
                        $parts = $item->sizes->map(fn ($s) => "{$s->size}: Rs." . number_format($s->price, 0))->join(' / ');
                        $line .= " — {$parts}";
                    } else {
                        $line .= " — Rs." . number_format((float) $item->price, 0);
                    }
                    if ($item->description) {
                        $line .= " ({$item->description})";
                    }
                    $menuLines .= "• {$line}\n";
                }
            }
        }

        if ($menuLines !== '') {
            return "MENU (REAL ITEMS & PRICES — DO NOT INVENT ANYTHING ELSE):\n{$menuLines}\n" .
                   "CALCULATION INSTRUCTIONS:\n" .
                   "- Always use these exact prices when calculating subtotals and grand totals.\n" .
                   "- If an item has size options, confirm the customer's size choice and use that size's price.\n\n";
        }

        // No menu configured
        return "MENU:\n- No menu items have been set up yet for {$name}.\n" .
               "- If the customer asks for the menu, say: \"Our menu is being updated. Please contact us directly for today's items!\"\n" .
               "- Do NOT invent, guess, or fabricate any food items or prices.\n\n";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Deals text builder — mirrors PromptBuilder.buildDealsText()
    // ──────────────────────────────────────────────────────────────────────────

    private function buildDealsText(Restaurant $restaurant): string
    {
        // Support active_deals relationship if it exists
        if (method_exists($restaurant, 'deals')) {
            $deals = $restaurant->deals()
                ->where('is_active', true)
                ->get();

            if ($deals->isNotEmpty()) {
                $text = "ACTIVE DEALS — mention these when the customer asks about deals or when they fit:\n";
                foreach ($deals as $i => $deal) {
                    $text .= ($i + 1) . ". {$deal->title}: {$deal->description}\n";
                }
                return $text . "\n";
            }
        }

        return '';
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Order confirmation detection — mirrors ChatHandler.isOrderConfirmed()
    // ──────────────────────────────────────────────────────────────────────────

    private function isOrderConfirmed(string $reply): bool
    {
        $lower = strtolower($reply);

        // Not confirmed yet — still asking for confirmation
        if (str_contains($lower, 'confirm kar doon') ||
            str_contains($lower, 'shall i place') ||
            str_contains($lower, 'kya main aapka order confirm')) {
            return false;
        }

        return str_contains($lower, 'your order is placed') ||
               str_contains($lower, 'order has been placed') ||
               str_contains($lower, 'order placed') ||
               str_contains($lower, 'آرڈر ہو گیا') ||
               str_contains($lower, 'آرڈر ہوگیا') ||
               (str_contains($lower, 'total') && str_contains($lower, 'placed'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Order saving — mirrors OrderService.save()
    // ──────────────────────────────────────────────────────────────────────────

    private function saveOrderFromHistory(Restaurant $restaurant, string $customerPhone, array $history): ?string
    {
        // Find the last assistant message with the order summary
        $summaryMsg = '';
        foreach (array_reverse($history) as $msg) {
            if ($msg['role'] === 'assistant' &&
                (str_contains(strtolower($msg['content']), 'order summary') ||
                 str_contains(strtolower($msg['content']), 'total'))) {
                $summaryMsg = $msg['content'];
                break;
            }
        }

        if ($summaryMsg === '') {
            return null;
        }

        // Extract total
        preg_match('/\*?total\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i', $summaryMsg, $totalMatch);
        $total = isset($totalMatch[1]) ? (float) str_replace(',', '', $totalMatch[1]) : 0;

        // Extract subtotal
        preg_match('/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i', $summaryMsg, $subMatch);
        $subtotal = isset($subMatch[1]) ? (float) str_replace(',', '', $subMatch[1]) : $total;

        if ($total <= 0) {
            return null;
        }

        // Extract delivery address
        preg_match('/deliver\s*to\s*[:*–-]?\s*(.+)/i', $summaryMsg, $addrMatch);
        $address = isset($addrMatch[1]) ? trim($addrMatch[1]) : 'Delivery order via WhatsApp';

        // Extract customer name
        preg_match('/name\s*[:*–-]?\s*(.+)/i', $summaryMsg, $nameMatch);
        $customerName = isset($nameMatch[1]) ? trim($nameMatch[1]) : 'WhatsApp Customer';

        // Extract payment method
        preg_match('/payment\s*[:*–-]?\s*(.+)/i', $summaryMsg, $payMatch);
        $paymentRaw = strtolower(trim($payMatch[1] ?? 'cash on delivery'));
        $paymentMethod = match(true) {
            str_contains($paymentRaw, 'jazzcash') => 'jazzcash',
            str_contains($paymentRaw, 'easypaisa') => 'easypaisa',
            default => 'cash_on_delivery',
        };

        // Generate tracking code
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $restaurant->name) ?: 'ORD', 0, 3));
        $trackingCode = $prefix . random_int(1000, 9999);

        try {
            Order::create([
                'restaurant_id'    => $restaurant->id,
                'tracking_code'    => $trackingCode,
                'customer_name'    => $customerName,
                'customer_phone'   => $customerPhone,
                'delivery_address' => $address,
                'subtotal'         => $subtotal,
                'delivery_charge'  => (float) ($restaurant->delivery_charge ?? 0),
                'total'            => $total,
                'status'           => 'pending',
                'payment_method'   => $paymentMethod,
                'notes'            => 'Placed via AI WhatsApp Bot',
            ]);

            Log::info("WhatsApp AI: Order #{$trackingCode} saved for {$restaurant->name} from {$customerPhone}");
            return $trackingCode;
        } catch (\Throwable $e) {
            Log::error("WhatsApp AI: Failed to save order for {$restaurant->name}: " . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Tracking reply — mirrors handleTrackingInquiry()
    // ──────────────────────────────────────────────────────────────────────────

    private function buildTrackingReply(Restaurant $restaurant, string $trackingCode): string
    {
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $order) {
            return "🔍 *Order Not Found*\n\nWe couldn't find order *{$trackingCode}* for *{$restaurant->name}*.\nPlease check your tracking code or reply with *menu* to start a new order.";
        }

        $statusLabels = [
            'pending'          => '⏳ Received & awaiting confirmation',
            'confirmed'        => '✅ Confirmed by kitchen',
            'preparing'        => '👨‍🍳 Cooking in progress',
            'out_for_delivery' => '🛵 Dispatched & on the way',
            'delivered'        => '🎉 Delivered — Enjoy your meal!',
            'cancelled'        => '❌ Cancelled',
        ];

        $statusText = $statusLabels[$order->status] ?? ucfirst($order->status);
        $trackUrl   = url('/track/' . $order->tracking_code);

        return "📦 *Order Status: {$order->tracking_code}*\n\n" .
               "📍 *Status:* {$statusText}\n" .
               "💰 *Total:* Rs. " . number_format($order->total, 0) . " ({$order->payment_method})\n" .
               "🔗 *Live Tracking:* {$trackUrl}\n\n" .
               "Thank you for ordering with *{$restaurant->name}*! 🙏";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Smart fallback — mirrors ChatHandler.fallback()
    // ──────────────────────────────────────────────────────────────────────────

    private function smartFallback(string $text, Restaurant $restaurant): string
    {
        $name  = $restaurant->name ?: 'our restaurant';
        $lower = strtolower($text);

        // Build a basic menu list for inline display
        $items = $restaurant->menuItems()->where('is_available', true)->take(6)->get();
        $menuSnippet = '';
        if ($items->isNotEmpty()) {
            $menuSnippet = "\n\n📋 *Quick Menu:*\n";
            foreach ($items as $item) {
                $menuSnippet .= "• *{$item->name}* — Rs. " . number_format((float) $item->price, 0) . "\n";
            }
        }

        if (preg_match('/hi|hello|hey|salam|assalam/i', $lower)) {
            return "👋 Welcome to *{$name}*! I'm Zain, your ordering assistant 😊{$menuSnippet}\n\nWhat would you like to order today?";
        }

        if (preg_match('/menu|kya hai|what.*have|items|list|dikhao|prices/i', $lower)) {
            return "📋 Here's what we have at *{$name}*:{$menuSnippet}\n\nKya lena chahein ge? 😊";
        }

        if (preg_match('/order|chahiye|want|lena/i', $lower)) {
            return "Sure! Tell me what you'd like from *{$name}* and your delivery address 🙂{$menuSnippet}";
        }

        if (preg_match('/track|tracking|status/i', $lower)) {
            return "Please send your *tracking code* (e.g. FEZ1234) and I'll check your order status right away!";
        }

        return "Hey! 😊 I'm here to help you order from *{$name}*! Type *menu* to see our items, or just tell me what you'd like to eat!{$menuSnippet}";
    }
}
