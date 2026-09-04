<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppAiBotService
 *
 * Full AI ordering bot:
 * - Live menu from DB (categories + items with sizes)
 * - Conversational ordering flow with Groq AI
 * - Conversation history per customer (45-min session cache)
 * - Order confirmation detection -> saves Order & OrderItem records to DB
 * - Live tracking code replies & order status lookups
 * - Automatic menu flyer photo sending via EvolutionAPI
 */
class WhatsAppAiBotService
{
    private const GROQ_API_URL  = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODELS        = [
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
        'mixtral-8x7b-32768',
        'gemma2-9b-it',
    ];
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

        // 1. Handle tracking inquiries (e.g. "FEZ1010", "track FEZ1010", "track id ?", "status", etc.)
        if (preg_match('/^[A-Za-z]{2,4}\d{3,6}$/', $text) ||
            preg_match('/^(?:track|status|order)\s+([A-Za-z0-9-]+)$/i', $text, $m) ||
            preg_match('/track\s*(?:id|code|\?)/i', $text) ||
            preg_match('/^(?:track|tracking|status|kahan hai|order kahan)$/i', $text)) {
            
            $explicitCode = isset($m[1]) ? strtoupper(trim($m[1])) : (preg_match('/^[A-Za-z]{2,4}\d{3,6}$/', $text) ? strtoupper($text) : null);
            $reply = $this->buildTrackingReply($restaurant, $explicitCode, $customerPhone);
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
            // AI unavailable — smart fallback
            $reply = $this->smartFallback($text, $restaurant);
            Log::warning("WhatsApp AI: Groq unavailable, using fallback for {$restaurant->name} — customer: {$customerPhone}");
        } else {
            $history[] = ['role' => 'assistant', 'content' => $reply];
            if (count($history) > 20) {
                $history = array_slice($history, -20);
            }
        }

        // 5. Detect order confirmation and save to database
        if ($this->isOrderConfirmed($reply, $history)) {
            $trackingCode = $this->saveOrderFromHistory($restaurant, $customerPhone, $history);
            if ($trackingCode) {
                $trackUrl = url('/track/' . $trackingCode);
                $reply .= "\n\n🎉 *Order Confirmed!*\n📦 *Your Tracking Code:* *{$trackingCode}*\n🔗 *Live Order Tracking:* {$trackUrl}\n\nSend this code anytime to check your live order & rider status!";
                
                // Reset session for fresh future conversations
                Cache::forget($sessionKey);
            } else {
                Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
            }
        } else {
            Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
        }

        // 6. If customer asked for menu and a visual menu flyer/image exists, send it!
        $isMenuRequest = (bool) preg_match('/menu|dikhao|prices|kya hai|list|card|items|منو|مینو|pdf|sheet|flyer|photo|document|picture/i', $text);
        if ($isMenuRequest) {
            $menuFile = $restaurant->menu_image ?: $restaurant->menu_file;
            if ($menuFile) {
                $fullMenuPath = public_path(ltrim($menuFile, '/'));
                if (file_exists($fullMenuPath)) {
                    $ext = strtolower(pathinfo($fullMenuPath, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'], true)) {
                        BotEvolutionClient::sendMedia(
                            $restaurant,
                            $recipientJid,
                            $fullMenuPath,
                            "📋 *{$restaurant->name} Menu*"
                        );
                    }
                }
            }
        }

        // 7. Send text reply back through EvolutionAPI
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
        $apiKey = config('services.groq.key') ?: env('GROQ_API_KEY');

        if (empty($apiKey)) {
            Log::error('WhatsApp AI: GROQ_API_KEY is not set in .env — bot cannot function properly!');
            return null;
        }

        $preferred = env('GROQ_MODEL');
        $models    = $preferred ? array_unique(array_merge([$preferred], self::MODELS)) : self::MODELS;

        foreach ($models as $model) {
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
    //  System Prompt
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

3. LANGUAGE HANDLING:
- Urdu script message -> Reply in Urdu script
- Roman Urdu message (e.g. "kya deal hai", "khana chahiye") -> Reply in Roman Urdu
- English message -> Reply in English
- Mixed -> Match their natural Pakistani casual tone.

4. STEP-BY-STEP ORDERING & DOUBLE-CHECK CONFIRMATION:
- Step 1: Clarify items, size variants, and quantity.
- Step 2: Ask for customer's name and contact phone number. If they say "same number", use their WhatsApp number.
- Step 3: Ask for complete delivery address.
- Step 4: Ask payment method: Cash on Delivery / JazzCash / EasyPaisa.
- Step 5: Show full itemized Order Summary with exact subtotal, delivery fee, and grand total.
- Step 6: Ask clearly: "Kya main aapka order confirm kar doon? ✅"
- Step 7: ONLY when customer confirms (e.g. "haan", "yes", "confirm", "theek hai", "kr do", "kar do"), say: "Your order is placed!" and state the total.

5. ORDER SUMMARY FORMAT (CRITICAL):
When you have collected all info, always output the summary in this EXACT structure:
─────────────────
🧾 *Order Summary*
1x [Item Name] — Rs.[Line Total]
2x [Item Name] — Rs.[Line Total]
─────────────────
Subtotal: Rs.[Subtotal]
Delivery: Rs.{$delivery}
*Total: Rs.[Grand Total]*
─────────────────
Name: [Customer Name]
Phone: [Contact Phone]
Payment: [Payment Method]
Deliver to: [Delivery Address]

Kya main aapka order confirm kar doon? ✅

6. STRICT STYLE RULES:
- Short, crisp replies (2-5 lines max).
- Friendly, warm emojis 😊.
- When final confirmation is given, ALWAYS include: "Your order is placed!"
PROMPT;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Menu text builder
    // ──────────────────────────────────────────────────────────────────────────

    private function buildMenuText(Restaurant $restaurant): string
    {
        $name = $restaurant->name ?: 'this restaurant';

        $categories = $restaurant->categories()
            ->with(['items' => fn ($q) => $q->where('is_available', true)])
            ->get();

        $menuLines = '';
        foreach ($categories as $cat) {
            $catItems = $cat->items ?? collect();
            if ($catItems->isEmpty()) {
                continue;
            }
            $menuLines .= "\n[Category: {$cat->name}]\n";
            foreach ($catItems as $item) {
                $line = "{$item->name}";
                if (!empty($item->sizes) && is_array($item->sizes)) {
                    $parts = array_map(fn ($s) => "{$s['size']}: Rs." . number_format($s['price'] ?? 0, 0), $item->sizes);
                    $line .= " — " . implode(' / ', $parts);
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
            $items = $restaurant->menuItems()->where('is_available', true)->get();
            if ($items->isNotEmpty()) {
                $menuLines = "\nMENU:\n";
                foreach ($items as $item) {
                    $line = "{$item->name}";
                    if (!empty($item->sizes) && is_array($item->sizes)) {
                        $parts = array_map(fn ($s) => "{$s['size']}: Rs." . number_format($s['price'] ?? 0, 0), $item->sizes);
                        $line .= " — " . implode(' / ', $parts);
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
                   "- Always use these exact prices when calculating subtotals and grand totals.\n\n";
        }

        return "MENU:\n- No menu items set up yet for {$name}.\n\n";
    }

    private function buildDealsText(Restaurant $restaurant): string
    {
        if (method_exists($restaurant, 'deals')) {
            $deals = $restaurant->deals()
                ->where('is_active', true)
                ->get();

            if ($deals->isNotEmpty()) {
                $text = "ACTIVE DEALS:\n";
                foreach ($deals as $i => $deal) {
                    $text .= ($i + 1) . ". {$deal->title}: {$deal->description}\n";
                }
                return $text . "\n";
            }
        }

        return '';
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Order confirmation detection
    // ──────────────────────────────────────────────────────────────────────────

    private function isOrderConfirmed(string $reply, array $history = []): bool
    {
        $lower = strtolower($reply);

        // Not confirmed yet — still asking for confirmation
        if (str_contains($lower, 'confirm kar doon') ||
            str_contains($lower, 'shall i place') ||
            str_contains($lower, 'kya main aapka order confirm') ||
            str_contains($lower, 'order summary')) {
            return false;
        }

        if (str_contains($lower, 'your order is placed') ||
            str_contains($lower, 'order has been placed') ||
            str_contains($lower, 'order placed') ||
            str_contains($lower, 'order is confirmed') ||
            str_contains($lower, 'order confirmed') ||
            str_contains($lower, 'آرڈر ہو گیا') ||
            str_contains($lower, 'آرڈر ہوگیا') ||
            str_contains($lower, 'order ho gya') ||
            str_contains($lower, 'order ho gaya')) {
            return true;
        }

        // Check if user confirmed after summary
        if (!empty($history)) {
            $lastUserMsg = '';
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if ($history[$i]['role'] === 'user') {
                    $lastUserMsg = strtolower($history[$i]['content']);
                    break;
                }
            }
            if (preg_match('/^(?:ha|haa|haan|yes|yep|yeah|ok|theek hai|thk hai|kr do|kar do|confirm|done|jee|ji)\b/i', $lastUserMsg)) {
                foreach ($history as $m) {
                    if ($m['role'] === 'assistant' && stripos($m['content'], 'order summary') !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Order saving to database & creating OrderItem records
    // ──────────────────────────────────────────────────────────────────────────

    private function saveOrderFromHistory(Restaurant $restaurant, string $customerPhone, array $history): ?string
    {
        // 1. Locate the assistant message containing the Order Summary
        $summaryMsg = '';
        foreach (array_reverse($history) as $msg) {
            if ($msg['role'] === 'assistant' &&
                (stripos($msg['content'], 'order summary') !== false ||
                 (stripos($msg['content'], 'deliver to') !== false && stripos($msg['content'], 'total') !== false))) {
                $summaryMsg = $msg['content'];
                break;
            }
        }

        if ($summaryMsg === '') {
            Log::warning("WhatsApp AI: Order confirmed but no summary message found in history for {$customerPhone}");
            return null;
        }

        // 2. Parse Total
        preg_match('/(?:total|grand\s*total)\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.\d+)?)/i', $summaryMsg, $totalMatch);
        $total = isset($totalMatch[1]) ? (float) str_replace(',', '', $totalMatch[1]) : 0;

        // 3. Parse Subtotal
        preg_match('/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+(?:\.\d+)?)/i', $summaryMsg, $subMatch);
        $subtotal = isset($subMatch[1]) ? (float) str_replace(',', '', $subMatch[1]) : $total;

        if ($total <= 0 && $subtotal <= 0) {
            // Fallback: search for any "Rs. XXX" in summary
            if (preg_match_all('/rs\.?\s*([0-9,]+)/i', $summaryMsg, $allPrices)) {
                $maxPrice = max(array_map(fn($p) => (float)str_replace(',', '', $p), $allPrices[1]));
                $total = $maxPrice;
                $subtotal = $maxPrice;
            }
        }

        if ($total <= 0) {
            Log::warning("WhatsApp AI: Could not parse non-zero total for {$customerPhone}");
            return null;
        }

        // 4. Parse Customer Name
        preg_match('/name\s*[:*–-]?\s*([^\n\r*]+)/i', $summaryMsg, $nameMatch);
        $customerName = isset($nameMatch[1]) ? trim(str_replace(['*', '`'], '', $nameMatch[1])) : 'WhatsApp Customer';

        // 5. Parse Phone
        preg_match('/phone\s*[:*–-]?\s*([0-9+ ]+)/i', $summaryMsg, $phoneMatch);
        $contactPhone = isset($phoneMatch[1]) ? trim(preg_replace('/[^0-9]/', '', $phoneMatch[1])) : $customerPhone;
        if (empty($contactPhone)) {
            $contactPhone = $customerPhone;
        }

        // 6. Parse Delivery Address
        preg_match('/deliver\s*to\s*[:*–-]?\s*([^\n\r*]+)/i', $summaryMsg, $addrMatch);
        $address = isset($addrMatch[1]) ? trim(str_replace(['*', '`'], '', $addrMatch[1])) : 'Delivery order via WhatsApp';

        // 7. Parse Payment Method
        preg_match('/payment\s*[:*–-]?\s*([^\n\r*]+)/i', $summaryMsg, $payMatch);
        $paymentRaw = strtolower(trim($payMatch[1] ?? 'cash on delivery'));
        $paymentMethod = match(true) {
            str_contains($paymentRaw, 'jazzcash') => 'jazzcash',
            str_contains($paymentRaw, 'easypaisa') => 'easypaisa',
            str_contains($paymentRaw, 'bank') || str_contains($paymentRaw, 'transfer') => 'bank_transfer',
            default => 'cash_on_delivery',
        };

        // 8. Generate Tracking Code
        $trackingCode = Order::generateTrackingCode($restaurant);

        // 9. Parse Itemized Products
        $lines = explode("\n", $summaryMsg);
        $parsedItems = [];
        $dbMenuItems = $restaurant->menuItems()->get();

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));
            if (preg_match('/^[-*•\s]*(\d+)\s*x\s*(.+)/i', $cleanLine, $m)) {
                $qty = (int) $m[1];
                $rest = trim($m[2], " *–—-\t\n\r\0\x0B");

                // Extract price from line
                $linePrice = 0;
                if (preg_match_all('/(?:rs\.?|pkr\.?|₹)\s*([0-9,]+(?:\.\d+)?)/i', $rest, $pMatches)) {
                    $lastMatch = end($pMatches[1]);
                    $linePrice = (float) str_replace(',', '', $lastMatch);
                }

                // Extract clean item name
                $itemName = preg_replace('/(?:—|-|–|:|@|\(|→|Rs\.|PKR|₹).*$/iu', '', $rest);
                $itemName = trim($itemName, " *–—-\t\n\r\0\x0B");

                // Look up matching MenuItem in DB
                $matchedDbItem = $dbMenuItems->first(function ($mi) use ($itemName) {
                    return stripos($mi->name, $itemName) !== false || stripos($itemName, $mi->name) !== false;
                });

                if ($matchedDbItem) {
                    $dbPrice = (float) $matchedDbItem->price;
                    if ($linePrice <= 0 && $dbPrice > 0) {
                        $linePrice = $dbPrice * $qty;
                    }
                    $itemName = $matchedDbItem->name;
                    $menuItemId = $matchedDbItem->id;
                } else {
                    $menuItemId = null;
                }

                $unitPrice = ($qty > 0 && $linePrice > 0) ? ($linePrice / $qty) : ($linePrice ?: 100);

                if ($itemName !== '') {
                    $parsedItems[] = [
                        'menu_item_id' => $menuItemId,
                        'name'         => $itemName,
                        'quantity'     => $qty,
                        'unit_price'   => $unitPrice,
                        'subtotal'     => $linePrice > 0 ? $linePrice : ($unitPrice * $qty),
                    ];
                }
            }
        }

        try {
            $deliveryCharge = (float) ($restaurant->delivery_charge ?? 0);

            $order = Order::create([
                'restaurant_id'    => $restaurant->id,
                'tracking_code'    => $trackingCode,
                'customer_name'    => $customerName,
                'customer_phone'   => $contactPhone,
                'delivery_address' => $address,
                'subtotal'         => $subtotal,
                'delivery_charge'  => $deliveryCharge,
                'total'            => $total,
                'status'           => 'pending',
                'payment_method'   => $paymentMethod,
                'notes'            => 'Placed via AI WhatsApp Bot',
            ]);

            // Save order items
            foreach ($parsedItems as $it) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $it['menu_item_id'],
                    'name'         => $it['name'],
                    'quantity'     => $it['quantity'],
                    'unit_price'   => $it['unit_price'],
                    'subtotal'     => $it['subtotal'],
                ]);
            }

            Log::info("WhatsApp AI: Order #{$trackingCode} saved successfully for {$restaurant->name} (ID: {$order->id}, Total: Rs.{$total}, Items: " . count($parsedItems) . ")");
            return $trackingCode;
        } catch (\Throwable $e) {
            Log::error("WhatsApp AI: Failed to save order for {$restaurant->name}: " . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Tracking reply
    // ──────────────────────────────────────────────────────────────────────────

    private function buildTrackingReply(Restaurant $restaurant, ?string $trackingCode, string $customerPhone = ''): string
    {
        $query = Order::where('restaurant_id', $restaurant->id);

        if ($trackingCode) {
            $order = (clone $query)->where('tracking_code', $trackingCode)->first();
        } else {
            $order = null;
        }

        // If not found by tracking code, find the customer's latest order by phone
        if (! $order && ! empty($customerPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
            $shortPhone = substr($cleanPhone, -9);
            $order = (clone $query)
                ->where(function ($q) use ($cleanPhone, $shortPhone) {
                    $q->where('customer_phone', 'like', "%{$shortPhone}%")
                      ->orWhere('customer_phone', $cleanPhone);
                })
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if (! $order) {
            return "🔍 *Order Not Found*\n\nWe couldn't find any recent orders for *{$restaurant->name}*.\nPlease check your tracking code or reply with *menu* to start a new order!";
        }

        $statusLabels = [
            'pending'          => '⏳ Received & awaiting confirmation',
            'confirmed'        => '✅ Confirmed by kitchen',
            'preparing'        => '👨‍🍳 Cooking in progress',
            'out_for_delivery' => '🛵 Dispatched & on the way with rider',
            'delivered'        => '🎉 Delivered — Enjoy your meal!',
            'cancelled'        => '❌ Cancelled',
        ];

        $statusText = $statusLabels[$order->status] ?? ucfirst($order->status);
        $trackUrl   = url('/track/' . $order->tracking_code);

        $riderText = '';
        if ($order->rider_name || $order->rider_phone) {
            $riderText = "\n🛵 *Rider:* {$order->rider_name}" . ($order->rider_phone ? " ({$order->rider_phone})" : '');
        }

        $itemsSummary = '';
        if ($order->items()->exists()) {
            $itemsSummary = "\n🍽️ *Items:* " . $order->items->map(fn($i) => "{$i->quantity}x {$i->name}")->implode(', ');
        }

        return "📦 *Order Status: #{$order->tracking_code}*\n\n" .
               "📍 *Status:* {$statusText}{$riderText}{$itemsSummary}\n" .
               "💰 *Total:* Rs. " . number_format($order->total, 0) . " (" . ucwords(str_replace('_', ' ', $order->payment_method)) . ")\n" .
               "🔗 *Live Delivery Map:* {$trackUrl}\n\n" .
               "Thank you for ordering with *{$restaurant->name}*! 🙏";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Smart fallback
    // ──────────────────────────────────────────────────────────────────────────

    private function smartFallback(string $text, Restaurant $restaurant): string
    {
        $name  = $restaurant->name ?: 'our restaurant';
        $lower = strtolower($text);

        $categories = $restaurant->categories()
            ->with(['items' => fn($q) => $q->where('is_available', true)])
            ->orderBy('sort_order')
            ->get();

        $menuSnippet = "\n\n📋 *Menu:*";
        $hasItems    = false;

        foreach ($categories as $cat) {
            $catItems = $cat->items;
            if ($catItems->isEmpty()) continue;
            $hasItems = true;
            $menuSnippet .= "\n\n📂 *{$cat->name}*";
            foreach ($catItems as $item) {
                $priceStr = 'Rs. ' . number_format((float) $item->price, 0);
                if (!empty($item->sizes) && is_array($item->sizes)) {
                    $parts    = array_map(fn($s) => "{$s['size']}: Rs." . number_format($s['price'] ?? 0, 0), $item->sizes);
                    $priceStr = implode(' / ', $parts);
                }
                $menuSnippet .= "\n• *{$item->name}* — {$priceStr}";
            }
        }

        if (! $hasItems) {
            $items = $restaurant->menuItems()->where('is_available', true)->get();
            foreach ($items as $item) {
                $menuSnippet .= "\n• *{$item->name}* — Rs. " . number_format((float) $item->price, 0);
            }
        }

        if (preg_match('/hi|hello|hey|salam|assalam/i', $lower)) {
            return "👋 Welcome to *{$name}*! I'm Zain, your ordering assistant 😊{$menuSnippet}\n\nWhat would you like to order today? Please reply with your items and delivery address!";
        }

        if (preg_match('/menu|kya hai|what.*have|items|list|dikhao|prices|card/i', $lower)) {
            return "📋 Here is the complete menu for *{$name}*:{$menuSnippet}\n\nKya mangwana chahein ge? 😊 Reply with your item name & quantity to place your order!";
        }

        if (preg_match('/track|tracking|status/i', $lower)) {
            return "Please send your *tracking code* (e.g. FEZ1010) and I'll check your order status right away!";
        }

        return "Hey! 😊 I'm here to help you order from *{$name}*!{$menuSnippet}\n\nPlease reply with what you'd like to order, your name, and delivery address!";
    }
}
