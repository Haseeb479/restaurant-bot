<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppAiBotService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const DEFAULT_MODEL = 'llama-3.3-70b-versatile';
    private const FALLBACK_MODEL = 'llama-3.1-8b-instant';

    /**
     * Process an incoming WhatsApp message and send the response.
     */
    public function handle(Restaurant $restaurant, string $customerPhone, string $recipientJid, string $messageText): void
    {
        $text = trim($messageText);
        if ($text === '') {
            return;
        }

        // 1. Check for live order tracking code (e.g. TRK1234 or FEZ1001)
        if (preg_match('/^[A-Za-z]{2,4}\d{4,6}$/', $text) || preg_match('/^(track|status|order)\s+([A-Za-z0-9-]+)$/i', $text, $m)) {
            $trackingCode = strtoupper(trim(isset($m[2]) ? $m[2] : $text));
            $this->handleTrackingInquiry($restaurant, $recipientJid, $trackingCode);
            return;
        }

        // 2. Load conversation history from cache (last 10 messages)
        $sessionKey = "wa_session_{$restaurant->id}_{$customerPhone}";
        $history = Cache::get($sessionKey, []);

        $history[] = ['role' => 'user', 'content' => $text];
        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }

        // 3. Call Groq AI to generate intelligent response
        $aiReply = $this->generateAiResponse($restaurant, $customerPhone, $history);

        // 4. Update session history with assistant reply
        $history[] = ['role' => 'assistant', 'content' => $aiReply];
        Cache::put($sessionKey, $history, now()->addMinutes(45));

        // 5. Check if the AI generated a finalized Order Confirmation
        $this->detectAndCreateOrder($restaurant, $customerPhone, $history, $aiReply);

        // 6. Send the response back via EvolutionAPI
        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $aiReply);
    }

    /**
     * Generate response via Groq API.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function generateAiResponse(Restaurant $restaurant, string $customerPhone, array $history): string
    {
        $apiKey = env('GROQ_API_KEY', config('services.groq.key'));

        if (empty($apiKey)) {
            return $this->fallbackMenuResponse($restaurant, $history);
        }

        $systemPrompt = $this->buildSystemPrompt($restaurant);

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        try {
            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->post(self::GROQ_API_URL, [
                    'model'       => self::DEFAULT_MODEL,
                    'messages'    => $messages,
                    'temperature' => 0.6,
                    'max_tokens'  => 600,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
                if ($reply !== '') {
                    return $reply;
                }
            }

            // Fallback to fast model
            $fallbackRes = Http::withToken($apiKey)
                ->timeout(8)
                ->post(self::GROQ_API_URL, [
                    'model'       => self::FALLBACK_MODEL,
                    'messages'    => $messages,
                    'temperature' => 0.6,
                    'max_tokens'  => 500,
                ]);

            if ($fallbackRes->successful()) {
                $data = $fallbackRes->json();
                $reply = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
                if ($reply !== '') {
                    return $reply;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Groq AI Error for {$restaurant->name}: " . $e->getMessage());
        }

        return $this->fallbackMenuResponse($restaurant, $history);
    }

    /**
     * Build dynamic system prompt with live restaurant menu and settings.
     */
    private function buildSystemPrompt(Restaurant $restaurant): string
    {
        $categories = $restaurant->categories()->with(['menuItems' => function ($q) {
            $q->where('is_available', true);
        }])->get();

        $menuText = "";
        foreach ($categories as $cat) {
            if ($cat->menuItems->isEmpty()) continue;
            $menuText .= "\n[Category: {$cat->name}]\n";
            foreach ($cat->menuItems as $item) {
                $desc = $item->description ? " - {$item->description}" : "";
                $menuText .= "• {$item->name} — Rs. " . number_format($item->price, 0) . "{$desc}\n";
            }
        }

        if ($menuText === "") {
            $items = $restaurant->menuItems()->where('is_available', true)->get();
            foreach ($items as $item) {
                $menuText .= "• {$item->name} — Rs. " . number_format($item->price, 0) . "\n";
            }
        }

        $deliveryCharge = (float) ($restaurant->delivery_charge ?: 0);
        $minOrder       = (float) ($restaurant->minimum_order ?: 0);
        $address        = $restaurant->address ?: $restaurant->city;

        return <<<PROMPT
You are the official WhatsApp AI Ordering Bot for "{$restaurant->name}" located in {$address}.
Language: Natural Roman Urdu & English (friendly, polite, appetizing Pakistani hospitality style).

=== RESTAURANT INFORMATION ===
Restaurant Name: {$restaurant->name}
Address: {$address}
Delivery Fee: Rs. {$deliveryCharge}
Minimum Order: Rs. {$minOrder}
Business Status: {$restaurant->isOpenText()}

=== LIVE MENU & PRICES ===
{$menuText}

=== INSTRUCTIONS FOR BOT ===
1. Welcome the customer warmly when they say hi/hello. Mention delicious food items.
2. If customer asks for menu or deals, share the items and prices with appealing emojis.
3. Help customer choose items, ask for quantities and special instructions.
4. When customer wants to order:
   - Calculate Subtotal accurately based on menu prices.
   - Add Delivery Fee (Rs. {$deliveryCharge}).
   - State Grand Total clearly.
   - Ask for Customer Name and complete Delivery Address.
5. When all details (Items, Address, Name, Phone) are provided, show an "ORDER SUMMARY" block:
   📋 *ORDER SUMMARY*
   • [Items with qty & prices]
   *Subtotal:* Rs. [Subtotal]
   *Delivery:* Rs. {$deliveryCharge}
   *Grand Total:* Rs. [Total]
   *Delivery Address:* [Address]
   *Payment:* Cash on Delivery (COD)

6. Keep responses concise, clean, and perfectly formatted for WhatsApp (use *bold* and bullet points).
PROMPT;
    }

    /**
     * Fallback response if AI service is temporarily unreachable.
     */
    private function fallbackMenuResponse(Restaurant $restaurant, array $history): string
    {
        $greeting = $restaurant->greeting_message
            ?: "👋 Welcome to *{$restaurant->name}*! How can we serve you today?";

        $items = $restaurant->menuItems()->where('is_available', true)->take(8)->get();
        $menuList = "";
        if ($items->isNotEmpty()) {
            $menuList = "\n\n📋 *Popular Menu Items:*\n";
            foreach ($items as $item) {
                $menuList .= "• *{$item->name}* — Rs. " . number_format($item->price, 0) . "\n";
            }
        }

        return "{$greeting}{$menuList}\n\n💡 Reply with your desired items and delivery address to place an order, or send your *Tracking Code* to track an existing order.";
    }

    /**
     * Parse order summary from chat and save to database.
     */
    private function detectAndCreateOrder(Restaurant $restaurant, string $customerPhone, array $history, string $latestReply): void
    {
        if (! str_contains(strtolower($latestReply), 'order summary') && ! str_contains(strtolower($latestReply), 'grand total')) {
            return;
        }

        // Extract subtotal and total
        preg_match('/grand total\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i', $latestReply, $totalMatch);
        preg_match('/subtotal\s*[:*–-]?\s*rs\.?\s*([0-9,]+)/i', $latestReply, $subMatch);

        $total = isset($totalMatch[1]) ? (float) str_replace(',', '', $totalMatch[1]) : 0;
        $subtotal = isset($subMatch[1]) ? (float) str_replace(',', '', $subMatch[1]) : $total;

        if ($total <= 0) {
            return;
        }

        // Extract address from latest messages
        $address = 'Delivery order via WhatsApp';
        foreach (array_reverse($history) as $msg) {
            if ($msg['role'] === 'user' && strlen($msg['content']) > 10 && ! preg_match('/^(hi|hello|menu|order|yes|ok|haan|theek hai)/i', $msg['content'])) {
                $address = $msg['content'];
                break;
            }
        }

        // Generate tracking code
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $restaurant->name) ?: 'ORD', 0, 3));
        $trackingCode = $prefix . random_int(1000, 9999);

        $order = Order::create([
            'restaurant_id'    => $restaurant->id,
            'tracking_code'    => $trackingCode,
            'customer_name'    => 'WhatsApp Customer',
            'customer_phone'   => $customerPhone,
            'delivery_address' => $address,
            'subtotal'         => $subtotal,
            'delivery_charge'  => (float) $restaurant->delivery_charge,
            'total'            => $total,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
            'notes'            => 'Placed via AI WhatsApp Bot',
        ]);

        Log::info("AI Bot created new order #{$order->id} ({$trackingCode}) for {$restaurant->name}");
    }

    /**
     * Send tracking status update back to customer.
     */
    private function handleTrackingInquiry(Restaurant $restaurant, string $recipientJid, string $trackingCode): void
    {
        $order = Order::where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $order) {
            $reply = "🔍 *Order Not Found*\n\nWe couldn't find order *{$trackingCode}* for *{$restaurant->name}*.\nPlease check the tracking code or reply with *menu* to start a new order.";
        } else {
            $trackUrl = url('/track/' . $order->tracking_code);
            $statusLabels = [
                'pending'          => '⏳ Received & awaiting confirmation',
                'confirmed'        => '✅ Confirmed by kitchen',
                'preparing'        => '👨‍🍳 Cooking in progress',
                'out_for_delivery' => '🛵 Dispatched & on the way',
                'delivered'        => '🎉 Delivered',
                'cancelled'        => '❌ Cancelled',
            ];
            $statusText = $statusLabels[$order->status] ?? ucfirst($order->status);

            $reply = "📦 *Order Status: {$order->tracking_code}*\n\n"
                   . "📍 *Status:* {$statusText}\n"
                   . "💰 *Total:* Rs. " . number_format($order->total, 0) . " ({$order->payment_method})\n"
                   . "🔗 *Live GPS Tracking:* {$trackUrl}\n\n"
                   . "Thank you for ordering with *{$restaurant->name}*!";
        }

        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
    }
}
