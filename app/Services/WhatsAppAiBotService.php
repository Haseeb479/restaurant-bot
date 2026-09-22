<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        'groq/compound-mini',
        'qwen/qwen3.8-27b',
        'openai/gpt-oss-120b',
        'qwen/qwen3.6-27b',
        'openai/gpt-oss-20b',
        'groq/compound',
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
    ];
    private const SESSION_TTL   = 45; // minutes

    // ──────────────────────────────────────────────────────────────────────────
    //  Public entry point
    // ──────────────────────────────────────────────────────────────────────────

    public function handle(Restaurant $restaurant, string $customerPhone, string $recipientJid, string $messageText, ?array $locationCoords = null): void
    {
        $text = trim($messageText);
        if ($text === '' && empty($locationCoords)) {
            return;
        }

        // ── GAP 1: Per-customer rate limiting ─────────────────────────────────
        // Max 12 messages per 60 seconds; burst protection of 1.2 s between
        // consecutive messages. Mirrors the Node.js RateLimiter exactly.
        $nowMs      = (int) (microtime(true) * 1000);
        $rateCacheKey = "wa_rate_{$restaurant->id}_{$customerPhone}";
        $rateData   = Cache::get($rateCacheKey, ['ts' => [], 'last_warn' => 0]);

        // Drop timestamps older than 60 s
        $rateData['ts'] = array_values(array_filter($rateData['ts'], fn ($t) => $nowMs - $t < 60_000));

        // Burst protection: drop only if excessive rapid spam (> 4 messages within 1.5s)
        $recentBurst = count(array_filter($rateData['ts'], fn ($t) => $nowMs - $t < 1500));
        if ($recentBurst >= 4) {
            Cache::put($rateCacheKey, $rateData, now()->addMinutes(2));
            return;
        }

        $maskedPhone = \App\Support\LogSanitizer::maskPhone($customerPhone);
        $redactedText = \App\Support\LogSanitizer::redactMessage($text);
        Log::info("WhatsApp AI: Processing message for [{$restaurant->name}] from [{$maskedPhone}]: {$redactedText}");

        // Minute limit: >= 12 messages → warn once per 30 s then drop
        if (count($rateData['ts']) >= 12) {
            if ($nowMs - ($rateData['last_warn'] ?? 0) > 30_000) {
                $rateData['last_warn'] = $nowMs;
                BotEvolutionClient::sendMessage(
                    $restaurant,
                    $recipientJid,
                    "⚠️ Bohat zyada messages aa rahe hain! Barah-e-karam thora intezar farmayein 😊\n" .
                    "Please wait a few seconds before sending another message."
                );
            }
            Cache::put($rateCacheKey, $rateData, now()->addMinutes(2));
            return;
        }

        // Allowed — record this message
        $rateData['ts'][] = $nowMs;
        Cache::put($rateCacheKey, $rateData, now()->addMinutes(2));

        // ── GAP 2: Restaurant closed check ────────────────────────────────────
        // Don't accept orders or chat when the restaurant has toggled itself
        // closed from the dashboard. Allow tracking queries through regardless.
        if (! $restaurant->is_open) {
            $isTrackingQuery = preg_match('/^[A-Za-z]{2,4}\d{3,6}$/', $text) ||
                preg_match('/^(?:track|status|order)\s+/i', $text) ||
                preg_match('/track\s*(?:id|code|\?)/i', $text) ||
                preg_match('/^(?:track|tracking|status|kahan hai|order kahan)$/i', $text);

            if (! $isTrackingQuery) {
                $hours = $restaurant->hours ?: 'check back soon';
                BotEvolutionClient::sendMessage(
                    $restaurant,
                    $recipientJid,
                    "Sorry, *{$restaurant->name}* is currently closed 🔴\n" .
                    "Please try again during opening hours: {$hours}."
                );
                return;
            }
        }

        // ── Check if restaurant subscription/trial is active (H1, H2) ────────
        if (! $restaurant->isPlanActive()) {
            $isTrackingQuery = preg_match('/^[A-Za-z]{2,4}\d{3,6}$/', $text) ||
                preg_match('/^(?:track|status|order)\s+/i', $text) ||
                preg_match('/track\s*(?:id|code|\?)/i', $text) ||
                preg_match('/^(?:track|tracking|status|kahan hai|order kahan)$/i', $text);

            if (! $isTrackingQuery) {
                BotEvolutionClient::sendMessage(
                    $restaurant,
                    $recipientJid,
                    "⚠️ Online ordering is temporarily paused for *{$restaurant->name}*.\nPlease contact the restaurant directly."
                );
                return;
            }
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

        // 2. Handle customer order cancellation (e.g. "cancel order", "cancel FEZ1010", "order cancel karna hai")
        if (preg_match('/^(?:cancel|order\s+cancel|cancel\s+order|radd|order\s+radd|khatam|cancel\s+karna)\b/i', $text) ||
            preg_match('/(?:cancel|radd)\s+([A-Za-z]{2,4}\d{3,6})/i', $text, $cancelMatch)) {
            $explicitCancelCode = isset($cancelMatch[1]) ? strtoupper(trim($cancelMatch[1])) : null;
            $cancelReply = $this->handleOrderCancellation($restaurant, $customerPhone, $explicitCancelCode);
            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $cancelReply);
            return;
        }

        // 3. Handle post-delivery star rating & feedback
        if ($this->handleDeliveredFeedback($restaurant, $customerPhone, $recipientJid, $text)) {
            return;
        }

        // 4. Handle GPS location pin natively if received
        if ($locationCoords && isset($locationCoords['lat'], $locationCoords['lng'])) {
            $engine = new OrderingStateEngine($restaurant, $customerPhone);
            $reply = $engine->handleLocationPin((float) $locationCoords['lat'], (float) $locationCoords['lng']);
            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
            return;
        }

        // 5. Deterministic Ordering State Engine with LLM NLU extraction
        $nlu = $this->extractNlu($restaurant, $text);

        // If customer requested menu, send flyer photo if available
        if (($nlu['intent'] ?? '') === 'SHOW_MENU') {
            $menuFile = $restaurant->menu_image ?: $restaurant->menu_file;
            if (! $menuFile || (! file_exists($menuFile) && ! file_exists(public_path(ltrim($menuFile, '/'))))) {
                $defaultFlyer = public_path('menus/menu_flyer.jpg');
                if (file_exists($defaultFlyer)) {
                    $menuFile = $defaultFlyer;
                }
            }
            if ($menuFile) {
                $ext = strtolower(pathinfo($menuFile, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'jfif', 'png', 'webp', 'gif', 'pdf'], true)) {
                    BotEvolutionClient::sendMedia(
                        $restaurant,
                        $recipientJid,
                        $menuFile,
                        "📋 *{$restaurant->name} — Official Menu Flyer*"
                    );
                }
            }
        }

        $engine = new OrderingStateEngine($restaurant, $customerPhone);
        $reply = $engine->process($nlu);

        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
    }

    /**
     * Extract structured NLU intent and entities using Groq with deterministic regex fallback.
     */
    public function extractNlu(Restaurant $restaurant, string $text): array
    {
        $clean = trim($text);

        // Fast path: obvious global intents can bypass Groq API latency
        if (preg_match('/^(?:cancel|radd|order\s+cancel|cancel\s+order|stop|nahi\s+chahiye)$/i', $clean)) {
            return ['intent' => 'CANCEL_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/^(?:confirm|yes|haan|theek|ok|jee|g|order\s+kar\s+do|done)$/i', $clean)) {
            return ['intent' => 'CONFIRM_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/^(?:menu|rate\s*list|kya\s*items\s*hain)$/i', $clean)) {
            return ['intent' => 'SHOW_MENU', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/^(?:new\s*order|another\s*order|make\s*(?:a\s*|another\s*)?order|naya\s*order|alag\s*order)$/i', $clean)) {
            return ['intent' => 'START_NEW_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/^(?:small|medium|large|xl|s|m|l)$/i', $clean)) {
            return ['intent' => 'SELECT_VARIANT', 'variant' => ucfirst(strtolower($clean)), 'raw_text' => $clean];
        }

        // Groq NLU Extraction
        try {
            $systemPrompt = <<<SYS
You are an expert NLU intent and entity extractor for a Pakistani restaurant WhatsApp ordering system.
Analyze the customer's message and output ONLY a valid JSON object matching this schema:
{
  "intent": "SHOW_MENU" | "ADD_ITEM" | "MODIFY_EXISTING_ORDER" | "START_NEW_ORDER" | "REMOVE_ITEM" | "CHANGE_QUANTITY" | "SELECT_VARIANT" | "VIEW_CART" | "CHECKOUT" | "CONFIRM_ORDER" | "CANCEL_ORDER" | "ASK_ORDER_STATUS" | "UNKNOWN",
  "items": [
    {
      "name": "Item name without size or price",
      "quantity": 1,
      "size": "Small" | "Medium" | "Large" | "XL" | null
    }
  ],
  "variant": "Small" | "Medium" | "Large" | "XL" | null,
  "name": "Customer name if provided or null",
  "address": "Customer delivery address if provided or null",
  "tracking_code": "Order tracking code if provided or null"
}
Rules:
- NEVER calculate prices or totals.
- Extract quantities as integers (default 1).
- Detect Pakistani size variations: S, M, L, XL, chota, bara, darmiyana.
- If customer says "confirm", "yes", "haan", "theek hai", "order kar do", intent is CONFIRM_ORDER.
- If customer says "cancel", "radd", intent is CANCEL_ORDER.
- If customer asks for menu or rates, intent is SHOW_MENU.
- If customer wants to add items to or modify their existing/previous order (e.g. "is me add kar do", "is order me add karo", "isme 2 wrap kr do", "same order me", "add 2 wraps", "order me aur add karo", "add this to my order"), intent is MODIFY_EXISTING_ORDER.
- If customer says "new order", "naya order", "another order", "make another order", intent is START_NEW_ORDER.
- Output valid, raw JSON only. No markdown formatting, no backticks, no extra text.
SYS;

            $groqResult = $this->callGroqNlu([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $clean],
            ]);

            if ($groqResult !== null) {
                $cleanJson = trim(preg_replace('/^```(?:json)?|```$/i', '', trim($groqResult)));
                $decoded = json_decode($cleanJson, true);
                if (is_array($decoded) && isset($decoded['intent'])) {
                    $decoded['raw_text'] = $clean;
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("WhatsApp AI: Groq NLU exception: " . $e->getMessage());
        }

        // Resilient Fallback Parser if Groq is unavailable, times out, or fails
        return $this->extractNluFallback($clean);
    }

    /**
     * Fast deterministic regex keyword & entity extractor fallback.
     */
    public function extractNluFallback(string $text): array
    {
        $clean = trim($text);

        if (preg_match('/\b(cancel|radd|rehne do|khatam|stop|nahi chahiye)\b/i', $clean)) {
            return ['intent' => 'CANCEL_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/\b(yes|confirm|theek|haan|jee|ok|order kar do|done)\b/i', $clean) && ! preg_match('/\b(pizza|burger|roll|biryani|bottle|coke|deal|wrap)\b/i', $clean)) {
            return ['intent' => 'CONFIRM_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/\b(new\s*order|another\s*order|make\s*(?:a\s*|another\s*)?order|naya\s*order|alag\s*order)\b/i', $clean)) {
            return ['intent' => 'START_NEW_ORDER', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/\b(status|track|kahan hai|order status)\b/i', $clean)) {
            preg_match('/\b([A-Za-z0-9-]{6,25})\b/', $clean, $m);
            return ['intent' => 'ASK_ORDER_STATUS', 'tracking_code' => $m[1] ?? null, 'raw_text' => $clean];
        }

        if (preg_match('/\b(menu|rate list|kya items|list bhejo)\b/i', $clean) && ! preg_match('/\b(chahiye|bhej do|pack)\b/i', $clean)) {
            return ['intent' => 'SHOW_MENU', 'items' => [], 'raw_text' => $clean];
        }

        if (preg_match('/^(small|medium|large|xl|s|m|l)$/i', $clean)) {
            return ['intent' => 'SELECT_VARIANT', 'variant' => ucfirst(strtolower($clean)), 'raw_text' => $clean];
        }

        $isModifyPhrase = (bool) preg_match('/\b(?:is\s*me|isme|is\s*order\s*me|same\s*order\s*me|order\s*me\s*(?:aur\s*)?add|add\s*(?:this\s*)?(?:to\s*)?(?:my\s*)?order|pichle\s*order|usi\s*order)\b|(?:\b(?:is\s*me|isme)\b.*?\b(?:kr\s*do|kardo|kar\s*do|add|bhej\s*do|daal\s*do)\b)|(?:^add\s+\d+\s+)/iu', $clean);

        $name = null;
        if (preg_match('/(?:naam|name|im|i am)\s*(?:hai|is|:)?\s*([A-Za-z\s]{2,30}?)(?:aur|address|,|$)/i', $clean, $nm)) {
            $name = trim(preg_replace('/\b(mera|hai|my|is)\b/i', '', $nm[1]));
        }

        $address = null;
        if (preg_match('/(?:address|deliver to|location|pata|ghar)\s*(?:hai|is|:)?\s*([A-Za-z0-9\s,\-\/]{3,60}?)(?:aur|,|$)/i', $clean, $am)) {
            $address = trim(preg_replace('/\b(hai|is|mera)\b/i', '', $am[1]));
        }

        // Strip price tampering attempt before extracting items
        $cleanWithoutPrices = preg_replace('/(?:for\s*)?(?:rs\.?|pkr\.?)\s*\d+/i', '', $clean);

        $items = [];
        $itemSearchStr = $cleanWithoutPrices;
        if ($name) {
            $itemSearchStr = str_ireplace($name, '', $itemSearchStr);
        }
        if ($address) {
            $itemSearchStr = str_ireplace($address, '', $itemSearchStr);
        }

        if (preg_match_all('/(?:(?:add\s+)?(\d+)\s*(?:x\s*)?)?(?:\b(small|medium|large|xl|chota|bara)\b)?\s*([a-zA-Z\s]+?)(?:aur|and|,|$)/i', $itemSearchStr, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $qty = ! empty($match[1]) ? (int) $match[1] : 1;
                $size = ! empty($match[2]) ? ucfirst(strtolower($match[2])) : null;
                $rawItem = trim($match[3] ?? '');
                $rawItem = preg_replace('/\b(?:chahiye|mangwana|bhej\s*do|pack\s*kar\s*do|mera|naam|address|hai|deliver|for|acha|is\s*me|isme|add|kr\s*do|kardo|kar\s*do|daal\s*do|karo|aur|bhi|kr)\b/iu', '', $rawItem);
                $rawItem = trim($rawItem);
                if (strlen($rawItem) >= 3 && ! in_array(strtolower($rawItem), ['pizza', 'burger', 'deal', 'large', 'small', 'medium', 'menu', 'yes', 'no', 'karo', 'wrap_stop'])) {
                    $items[] = ['name' => $rawItem, 'quantity' => $qty, 'size' => $size];
                }
            }
        }

        if ($isModifyPhrase) {
            $intent = 'MODIFY_EXISTING_ORDER';
        } else {
            $intent = ! empty($items) ? 'ADD_ITEM' : ($name || $address ? 'COLLECT_CUSTOMER_INFO' : 'UNKNOWN');
        }

        return [
            'intent' => $intent,
            'items' => $items,
            'variant' => $items[0]['size'] ?? null,
            'name' => $name,
            'address' => $address,
            'raw_text' => $clean,
        ];
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

        $caPath = class_exists(\Composer\CaBundle\CaBundle::class)
            ? \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()
            : null;

        foreach ($models as $model) {
            try {
                $req = Http::withToken($apiKey)->timeout(15);
                if ($caPath && file_exists($caPath)) {
                    $req = $req->withOptions(['verify' => $caPath]);
                }

                $response = $req->post(self::GROQ_API_URL, [
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

    /**
     * Fast single-model Groq call for NLU extraction (3s timeout).
     */
    private function callGroqNlu(array $messages): ?string
    {
        $apiKey = config('services.groq.key') ?: env('GROQ_API_KEY');
        if (empty($apiKey)) {
            return null;
        }

        $caPath = class_exists(\Composer\CaBundle\CaBundle::class)
            ? \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()
            : null;

        try {
            $req = Http::withToken($apiKey)->timeout(3);
            if ($caPath && file_exists($caPath)) {
                $req = $req->withOptions(['verify' => $caPath]);
            }

            $response = $req->post(self::GROQ_API_URL, [
                'model'       => 'llama-3.1-8b-instant',
                'messages'    => $messages,
                'temperature' => 0.0,
                'max_tokens'  => 300,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
                if ($reply !== '') {
                    return $reply;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("WhatsApp AI: Fast Groq NLU fallback triggered: " . $e->getMessage());
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  System Prompt
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(Restaurant $restaurant, ?string $sessionKey = null): string
    {
        $name    = $restaurant->name ?: 'Our Restaurant';
        $address = $restaurant->address ?: ($restaurant->city ?: 'City Center');
        $hours   = $restaurant->hours ?: '10 AM – 11 PM';
        $delivery = (float) ($restaurant->delivery_charge ?? 50);
        $minOrder = (float) ($restaurant->minimum_order ?? 0);
        $city     = $restaurant->city ?: 'Local Area';
        $radius   = $restaurant->maxDeliveryRadiusKm();
        $areas    = trim($restaurant->delivery_areas ?: '');
        $areasNotice = $areas !== '' ? "- Operational Delivery Areas Whitelist: {$areas}\n" : '';

        $menuText  = $this->buildMenuText($restaurant);
        $dealsText = $this->buildDealsText($restaurant);

        $hasVerifiedGps = $sessionKey ? (bool) Cache::get("verified_delivery_coords_{$sessionKey}") : false;
        $cachedAddr = $sessionKey ? Cache::get("verified_delivery_address_{$sessionKey}") : '';
        $verifiedNotice = '';
        if ($hasVerifiedGps) {
            $verifiedNotice = "  • CUSTOMER PIN STATUS: Customer's exact location pin has ALREADY been verified on the map! Accept their delivery address and proceed directly to Order Summary.\n";
            if ($cachedAddr) {
                $verifiedNotice .= "  • IMPORTANT: You MUST use exactly 'Deliver to: {$cachedAddr}' in the Order Summary. NEVER change or invent another address.\n";
            }
        }

        $deliveryZoneRule = implode("\n", array_filter([
            "- DELIVERY & ADDRESS INSTRUCTIONS:",
            "  • Base City: {$city}. Operating Delivery Radius: {$radius} km.",
            "  • The backend system automatically verifies distance and GPS radius before messages reach you.",
            $areas !== '' ? "  • Whitelisted operational neighborhoods: {$areas}." : null,
            $verifiedNotice ?: null,
            "  • When the customer gives their name, delivery address, and payment method, accept the address and immediately output the complete itemized Order Summary (Step 5). Do not refuse or question local addresses.",
            "  • ONLY refuse an address if the customer explicitly demands delivery to a completely different distant major city (e.g. asking to deliver to Karachi or Islamabad when restaurant is in {$city}).",
        ]));

        return <<<PROMPT
You are Zain, a warm, polite, and professional WhatsApp ordering waiter at "{$name}" restaurant in Pakistan.

RESTAURANT INFO:
- Name: {$name}
- City: {$city}
- Address: {$address}
- Delivery Radius: {$radius} km (Strict Foodpanda-style limit)
- Delivery Charge: Rs. {$delivery}
- Minimum Order: Rs. {$minOrder}
- Hours: {$hours}
{$areasNotice}
{$menuText}{$dealsText}CORE PILLARS & STRICT OPERATING RULES:

1. MENU IS THE ONLY SOURCE OF TRUTH (STRICT ZERO HALLUCINATION):
- Sell ONLY items and sizes listed in the MENU section above.
- If a customer asks for an item NOT in our menu, politely inform them:
  "Yeh item hamare menu mein available nahi hai. Hamare paas [mention 2-3 available items] available hain! 😊"
- NEVER invent, assume, or hallucinate food items, prices, extra discounts, or deals not listed above.

2. SCOPE & DOMAIN BOUNDARY:
- You are STRICTLY a restaurant waiter. You ONLY discuss food, menu, deals, restaurant timings, delivery, payment, and taking orders.
{$deliveryZoneRule}
- If customer asks off-topic questions, politely deflect:
  "Main to sirf {$name} ka waiter hoon aur aapke liye mazedar khana deliver karwa sakta hoon! 🍔 Aaj kya khana pasand karein ge?"

3. LANGUAGE HANDLING:
- Urdu script message -> Reply in Urdu script
- Roman Urdu message (e.g. "kya deal hai", "khana chahiye") -> Reply in Roman Urdu
- English message -> Reply in English
- Mixed -> Match their natural Pakistani casual tone.

4. STEP-BY-STEP ORDERING & DOUBLE-CHECK CONFIRMATION:
- Step 1: Clarify items, size variants, and quantity (CRITICAL MANDATORY SIZE RULE):
  • Whenever a customer asks for or orders an item that has multiple sizes in the menu (such as Pizza, Deals, Drinks, etc. with Small/Medium/Large/XL or S/M/L/XL):
    YOU MUST PROACTIVELY ASK the customer which size they want BEFORE creating the Order Summary!
    Example: If customer asks for "Pizza", reply: "Aapko pizza mein kaunsa size chahiye? Hamare paas Small (Rs. X), Medium (Rs. Y), aur Large (Rs. Z) available hain! 🍕"
  • Do NOT assume or guess the size. Always clarify the size variant with the customer.
  • Pick and apply the EXACT price according to the specific size chosen by the customer.
- Step 2: Ask for customer's name and contact phone number. If they say "same number", use their WhatsApp number.
- Step 3: Ask for complete delivery address (skip if customer has already shared location pin or address).
- Step 4: Payment defaults to Cash on Delivery (COD) automatically! Do NOT ask customer to choose payment method. If customer explicitly requests JazzCash, use JazzCash.
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
Payment: Cash on Delivery (COD) 💵
Deliver to: [Delivery Address]

Kya main aapka order confirm kar doon? ✅
- Payment line defaults to "Cash on Delivery (COD) 💵". If customer explicitly asked for JazzCash, use "JazzCash".
- In Order Summary line items, always write the exact full item name as listed in the MENU (e.g. write "3x Butter Naan", "1x Garlic Naan", "2x Butter Roti"). Never shorten or split item names into parenthetical variants unless the item has explicit size options.

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
                $activeSizes = $item->getActiveSizesList();
                if (!empty($activeSizes)) {
                    $parts = array_map(fn ($s) => ($s['name'] ?? $s['size']) . ": Rs." . number_format($s['price'] ?? 0, 0), $activeSizes);
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
                    $activeSizes = $item->getActiveSizesList();
                    if (!empty($activeSizes)) {
                        $parts = array_map(fn ($s) => ($s['name'] ?? $s['size']) . ": Rs." . number_format($s['price'] ?? 0, 0), $activeSizes);
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

        // Fallback: parse menu file (CSV/Excel) if items are still not found
        if ($menuLines === '' && !empty($restaurant->menu_file)) {
            $menuPath = $this->resolveMenuFilePath($restaurant);
            if ($menuPath && file_exists($menuPath)) {
                try {
                    $ext = strtolower(pathinfo($menuPath, PATHINFO_EXTENSION));
                    $ctrl = new \App\Http\Controllers\DashboardController();
                    $refExtract = new \ReflectionMethod($ctrl, 'extractMenuItemsFromFile');
                    $refExtract->setAccessible(true);
                    $fileItems = $refExtract->invoke($ctrl, $menuPath, $ext);

                    if (!empty($fileItems)) {
                        $menuLines = "\nMENU (from official menu sheet):\n";
                        foreach ($fileItems as $it) {
                            $price = number_format((float) ($it['price'] ?? 0), 0);
                            $menuLines .= "• {$it['name']} — Rs.{$price}\n";
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("WhatsApp AI: Could not parse menu file for {$restaurant->name}: " . $e->getMessage());
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

    private function resolveMenuFilePath(Restaurant $restaurant): ?string
    {
        $file = $restaurant->menu_file;
        if (empty($file)) return null;

        $publicPath = public_path();
        $filename   = basename($file);

        $candidates = [
            $publicPath . '/' . ltrim($file, '/'),
            $publicPath . '/menus/' . $filename,
            $publicPath . '/uploads/menus/' . $filename,
            $publicPath . '/uploads/' . $filename,
            $publicPath . '/' . $filename,
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        return null;
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

    public function saveOrderFromHistory(Restaurant $restaurant, string $customerPhone, array $history): ?string
    {
        // ── C5: Enforce Monthly Order Limits ─────────────────────────────────
        if ($restaurant->hasExceededMonthlyOrders()) {
            Log::warning("WhatsApp AI: Monthly order limit reached for {$restaurant->name} (Limit: {$restaurant->maxMonthlyOrders()})");
            if (! empty($restaurant->owner_phone)) {
                BotEvolutionClient::sendMessage(
                    $restaurant,
                    $restaurant->owner_phone,
                    "⚠️ *Order Limit Reached!*\n\nYour restaurant has reached its monthly order limit of {$restaurant->maxMonthlyOrders()} orders. Please upgrade your subscription plan to continue accepting orders online."
                );
            }
            return null;
        }

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

        // 6. Parse Delivery Address (Apply Location Priority)
        preg_match('/deliver\s*to\s*[:*–-]?\s*([^\n\r*]+)/i', $summaryMsg, $addrMatch);
        $address = isset($addrMatch[1]) ? trim(str_replace(['*', '`'], '', $addrMatch[1])) : 'Delivery order via WhatsApp';
        
        $sessionKey = "wa_session_{$restaurant->id}_{$customerPhone}";
        $cachedGps  = Cache::get("verified_delivery_coords_{$sessionKey}");
        $cachedAddr = Cache::get("verified_delivery_address_{$sessionKey}");
        
        if ($cachedGps && $cachedAddr) {
            $address = $cachedAddr; // Override AI hallucination completely
        }

        // 7. Parse Payment Method (COD default, JazzCash/EasyPaisa if explicitly requested)
        preg_match('/payment\s*[:*–-]?\s*([^\n\r*]+)/i', $summaryMsg, $payMatch);
        $paymentRaw = strtolower(trim($payMatch[1] ?? 'cash on delivery'));

        $allUserText = '';
        foreach ($history as $m) {
            if (($m['role'] ?? '') === 'user') {
                $allUserText .= ' ' . ($m['content'] ?? '');
            }
        }
        $userLower = strtolower($allUserText);

        $paymentMethod = match(true) {
            str_contains($paymentRaw, 'jazzcash') || str_contains($userLower, 'jazzcash') || str_contains($userLower, 'jazz cash') => 'jazzcash',
            str_contains($paymentRaw, 'easypaisa') || str_contains($userLower, 'easypaisa') || str_contains($userLower, 'easy paisa') => 'easypaisa',
            str_contains($paymentRaw, 'bank') || str_contains($userLower, 'bank') => 'bank_transfer',
            default => 'cash_on_delivery',
        };

        // 8. Generate Tracking Code
        $trackingCode = Order::generateTrackingCode($restaurant);

        // ── C2: Duplicate order prevention ────────────────────────────────────
        // A network retry or double-confirmation message would otherwise create
        // two identical orders seconds apart. If we already saved an order for
        // this session in the last 5 minutes, return that tracking code.
        $sessionKey        = "wa_session_{$restaurant->id}_{$customerPhone}";
        $dedupKey          = "order_saved_{$sessionKey}";
        $existingTrackCode = Cache::get($dedupKey);
        if ($existingTrackCode) {
            Log::info("WhatsApp AI: Duplicate order prevented for {$customerPhone} — returning existing {$existingTrackCode}");
            return $existingTrackCode;
        }

        // ── C1: Server-side price validation from the database ────────────────
        // The AI output is used only for item names and quantities. Prices are
        // always taken from the authoritative MenuItem records in the database.
        // This prevents a customer from manipulating the conversation to make
        // the AI write a lower price in the summary.
        $lines = explode("\n", $summaryMsg);
        $parsedItems = [];
        $dbMenuItems = $restaurant->menuItems()->get();
        $dbDeals     = $restaurant->deals()->get();
        $dbSubtotal  = 0.0; // Will be recalculated from DB prices

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));
            if (preg_match('/^[-*•\s]*(\d+)\s*x\s*(.+)/i', $cleanLine, $m)) {
                $qty  = (int) $m[1];
                $rest = trim($m[2], " *–—-\t\n\r\0\x0B");

                // Check for size variation in parentheses or brackets e.g. (Large) or (Small)
                $itemSize = null;
                if (preg_match('/\(([^)]+)\)/', $rest, $sizeMatch)) {
                    $potentialSize = trim($sizeMatch[1]);
                    if (!preg_match('/(?:rs\.?|pkr\.?|₹|\d{2,})/i', $potentialSize)) {
                        $itemSize = MenuItem::normalizeSizeName($potentialSize);
                    }
                } elseif (preg_match('/\b(small|medium|large|extra\s*large|xl|regular|family|personal|jumbo|half|full)\b/i', $rest, $sizeMatch)) {
                    $itemSize = MenuItem::normalizeSizeName($sizeMatch[1]);
                }

                // Extract clean item name (strip price suffixes and size tags for matching)
                $itemName = preg_replace('/(?:—|-|–|:|@|\(|→|Rs\.|PKR|₹).*$/iu', '', $rest);
                if ($itemSize !== null) {
                    $itemName = preg_replace('/\b' . preg_quote($itemSize, '/') . '\b/i', '', $itemName);
                }
                $itemName = trim($itemName, " *–—-\t\n\r\0\x0B");
                $normItemName = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $itemName));
                $normItemName = trim(preg_replace('/\s+/', ' ', $normItemName));

                $candidates = [];
                if ($itemSize !== null && $itemSize !== '') {
                    $cand1 = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', "{$itemSize} {$normItemName}"))));
                    $cand2 = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', "{$normItemName} {$itemSize}"))));
                    if ($cand1 !== '') $candidates[] = ['name' => $cand1, 'is_composite' => true];
                    if ($cand2 !== '') $candidates[] = ['name' => $cand2, 'is_composite' => true];
                }
                if ($normItemName !== '') {
                    $candidates[] = ['name' => $normItemName, 'is_composite' => false];
                }

                $matchedDbItem = null;
                $matchedCandidateIsComposite = false;

                // Look up authoritative price from database menu items (C1) — exact match first
                foreach ($candidates as $cand) {
                    $found = $dbMenuItems->first(function ($mi) use ($cand) {
                        $normMi = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $mi->name))));
                        return $normMi === $cand['name'];
                    });
                    if ($found) {
                        $matchedDbItem = $found;
                        $matchedCandidateIsComposite = $cand['is_composite'];
                        break;
                    }
                }

                if (!$matchedDbItem) {
                    foreach ($candidates as $cand) {
                        $found = $dbMenuItems->first(function ($mi) use ($cand) {
                            $normMi = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $mi->name))));
                            return $normMi !== '' && stripos($normMi, $cand['name']) !== false;
                        });
                        if ($found) {
                            $matchedDbItem = $found;
                            $matchedCandidateIsComposite = $cand['is_composite'];
                            break;
                        }
                    }
                }

                if (!$matchedDbItem) {
                    $sortedDbMenuItems = $dbMenuItems->sortByDesc(function ($mi) {
                        return mb_strlen($mi->name ?? '');
                    });
                    foreach ($candidates as $cand) {
                        $found = $sortedDbMenuItems->first(function ($mi) use ($cand) {
                            $normMi = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $mi->name))));
                            return $normMi !== '' && stripos($cand['name'], $normMi) !== false;
                        });
                        if ($found) {
                            $matchedDbItem = $found;
                            $matchedCandidateIsComposite = $cand['is_composite'];
                            break;
                        }
                    }
                }

                if (!$matchedDbItem && !empty($restaurant->menu_file)) {
                    // Fallback to menu file items if DB items missed it
                    $menuPath = $this->resolveMenuFilePath($restaurant);
                    if ($menuPath && file_exists($menuPath)) {
                        try {
                            $ext = strtolower(pathinfo($menuPath, PATHINFO_EXTENSION));
                            $ctrl = new \App\Http\Controllers\DashboardController();
                            $refExtract = new \ReflectionMethod($ctrl, 'extractMenuItemsFromFile');
                            $refExtract->setAccessible(true);
                            $fileItems = $refExtract->invoke($ctrl, $menuPath, $ext);

                            foreach ($candidates as $cand) {
                                $foundFileItem = collect($fileItems)->first(function($fi) use ($cand) {
                                    $n = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $fi['name'] ?? ''))));
                                    return $n === $cand['name'];
                                });
                                if ($foundFileItem) {
                                    $matchedDbItem = new \App\Models\MenuItem([
                                        'name'  => $foundFileItem['name'],
                                        'price' => (float) ($foundFileItem['price'] ?? 0),
                                        'sizes' => $foundFileItem['sizes'] ?? null,
                                    ]);
                                    $matchedCandidateIsComposite = $cand['is_composite'];
                                    break;
                                }
                            }

                            if (!$matchedDbItem) {
                                foreach ($candidates as $cand) {
                                    $foundFileItem = collect($fileItems)->first(function($fi) use ($cand) {
                                        $n = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $fi['name'] ?? ''))));
                                        return $n !== '' && stripos($n, $cand['name']) !== false;
                                    });
                                    if ($foundFileItem) {
                                        $matchedDbItem = new \App\Models\MenuItem([
                                            'name'  => $foundFileItem['name'],
                                            'price' => (float) ($foundFileItem['price'] ?? 0),
                                            'sizes' => $foundFileItem['sizes'] ?? null,
                                        ]);
                                        $matchedCandidateIsComposite = $cand['is_composite'];
                                        break;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}
                    }
                }

                $matchedDeal = null;
                if (!$matchedDbItem) {
                    // Check active deals — exact match first
                    foreach ($candidates as $cand) {
                        $foundDeal = $dbDeals->first(function ($deal) use ($cand) {
                            $dealTitle = $deal->title ?? $deal->name ?? '';
                            $normDeal = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $dealTitle))));
                            return $normDeal === $cand['name'];
                        });
                        if ($foundDeal) {
                            $matchedDeal = $foundDeal;
                            break;
                        }
                    }

                    if (!$matchedDeal) {
                        foreach ($candidates as $cand) {
                            $foundDeal = $dbDeals->first(function ($deal) use ($cand) {
                                $dealTitle = $deal->title ?? $deal->name ?? '';
                                $normDeal = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $dealTitle))));
                                return $normDeal !== '' && (stripos($normDeal, $cand['name']) !== false || stripos($cand['name'], $normDeal) !== false);
                            });
                            if ($foundDeal) {
                                $matchedDeal = $foundDeal;
                                break;
                            }
                        }
                    }
                }

                if ($matchedDbItem) {
                    $unitPrice = 0.0;
                    $matchedSize = null;

                    // Handle size variations if defined on MenuItem
                    $activeSizes = $matchedDbItem->getActiveSizesList();
                    if (!$matchedCandidateIsComposite && !empty($activeSizes)) {
                        if ($itemSize) {
                            $normSize = MenuItem::normalizeSizeName($itemSize);
                            foreach ($activeSizes as $s) {
                                $sName = MenuItem::normalizeSizeName($s['name'] ?? $s['size'] ?? '');
                                if (strcasecmp($sName, $normSize) === 0) {
                                    $unitPrice   = (float) ($s['price'] ?? 0);
                                    $matchedSize = $sName;
                                    break;
                                }
                            }
                        }

                        // Default to first active size if not matched or no size specified
                        if ($unitPrice === 0.0 && isset($activeSizes[0]['price'])) {
                            $unitPrice   = (float) $activeSizes[0]['price'];
                            $matchedSize = MenuItem::normalizeSizeName($activeSizes[0]['name'] ?? $activeSizes[0]['size'] ?? '');
                        }
                    }

                    if ($unitPrice === 0.0) {
                        $unitPrice = (float) $matchedDbItem->price;
                    }

                    $lineTotal  = $unitPrice * $qty;
                    $itemName   = $matchedDbItem->name;
                    $itemSize   = $matchedCandidateIsComposite ? null : ($matchedSize ?: $itemSize);
                    $menuItemId = $matchedDbItem->id;
                } elseif ($matchedDeal) {
                    $unitPrice  = (float) ($matchedDeal->discount_value ?? 0);
                    $lineTotal  = $unitPrice * $qty;
                    $itemName   = $matchedDeal->title;
                    $menuItemId = null;
                } else {
                    // SECURITY: Database menu pricing is the ONLY price authority.
                    // If an item cannot be matched to the DB menu or deals, STOP order creation.
                    Log::warning("WhatsApp AI: Item '{$itemName}' not found in DB menu or deals for {$restaurant->name} — aborting order creation.");
                    return null;
                }

                if ($itemName !== '' && $qty > 0) {
                    $parsedItems[] = [
                        'menu_item_id' => $menuItemId,
                        'name'         => $itemName,
                        'size'         => $itemSize,
                        'quantity'     => $qty,
                        'unit_price'   => $unitPrice,
                        'subtotal'     => $lineTotal,
                    ];
                    $dbSubtotal += $lineTotal;
                }
            }
        }

        // Recalculate totals from authoritative DB prices (C1)
        $deliveryCharge = (float) ($restaurant->delivery_charge ?? 0);
        if ($dbSubtotal > 0) {
            // DB prices are authoritative; use them even if they differ from AI
            $subtotal = $dbSubtotal;
            $total    = $subtotal + $deliveryCharge;
            Log::info("WhatsApp AI: Prices recalculated from DB — subtotal: Rs.{$subtotal}, total: Rs.{$total}");
        } else {
            // No DB items matched — enforce backend delivery charge and calculated total
            $subtotal = max(0.0, (float) $subtotal);
            $total    = $subtotal + $deliveryCharge;
        }

        // Cart validation: must have at least 1 line item and a positive subtotal (> 0)
        if (empty($parsedItems) || $subtotal <= 0) {
            Log::warning("WhatsApp AI: Order cart validation failed for {$customerPhone} — empty cart or non-positive subtotal.");
            return null;
        }

        try {
            // Atomic transaction: Order header + OrderItems must succeed together
            $order = DB::transaction(function () use (
                $restaurant, $trackingCode, $customerName, $contactPhone,
                $address, $subtotal, $deliveryCharge, $total, $paymentMethod, $parsedItems
            ) {
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
                        'size'         => $it['size'] ?? null,
                        'quantity'     => $it['quantity'],
                        'unit_price'   => $it['unit_price'],
                        'subtotal'     => $it['subtotal'],
                    ]);
                }

                return $order;
            });

            // Persist verified location data for live tracking and order records
            $cachedGps       = Cache::get("verified_delivery_coords_{$sessionKey}");
            $cachedAddr      = Cache::get("verified_delivery_address_{$sessionKey}");
            $cachedPlaceName = Cache::get("verified_delivery_place_name_{$sessionKey}");
            $cachedPlaceId   = Cache::get("verified_delivery_place_id_{$sessionKey}");
            $cachedSource    = Cache::get("verified_delivery_source_{$sessionKey}");
            $isPinnedLocation = (bool) ($cachedGps && in_array($cachedSource, ['whatsapp_pin', 'customer_pin', 'google_places', 'reverse_geocode'], true));

            if ($cachedAddr && ($address === 'Delivery order via WhatsApp' || empty($address) || $isPinnedLocation)) {
                $address = $cachedAddr;
            }

            // Location Safety: Never replace valid WhatsApp or map pin coordinates with geocoded or guessed coordinates
            $gpsCoords = $isPinnedLocation ? $cachedGps : ($cachedGps ?: $this->geocodeAddress($address, $restaurant->city ?? ''));

            $orderUpdates = [];
            if ($address) {
                $orderUpdates['delivery_address'] = $address;
            }
            if ($gpsCoords) {
                $orderUpdates['delivery_lat'] = $gpsCoords[0];
                $orderUpdates['delivery_lng'] = $gpsCoords[1];
            }
            if ($cachedPlaceName) {
                $orderUpdates['delivery_place_name'] = $cachedPlaceName;
            }
            if ($cachedPlaceId) {
                $orderUpdates['delivery_place_id'] = $cachedPlaceId;
            }
            if ($cachedSource) {
                $orderUpdates['location_source'] = $cachedSource;
            }
            if (! empty($orderUpdates)) {
                $order->update($orderUpdates);
            }

            // Geocode restaurant address if not already set
            if (! $restaurant->restaurant_lat && ($restaurant->address || $restaurant->city)) {
                $restAddr   = trim(($restaurant->address ?? '') . ' ' . ($restaurant->city ?? ''));
                $restCoords = $this->geocodeAddress($restAddr, $restaurant->city ?? '');
                if ($restCoords) {
                    $restaurant->update([
                        'restaurant_lat' => $restCoords[0],
                        'restaurant_lng' => $restCoords[1],
                    ]);
                }
            }

            // ── C2: Mark this session as having a saved order (5-min dedup window) ──
            Cache::put($dedupKey, $trackingCode, now()->addMinutes(5));

            Log::info("WhatsApp AI: Order #{$trackingCode} saved successfully for {$restaurant->name} (ID: {$order->id}, Total: Rs.{$total}, Items: " . count($parsedItems) . ")");
            return $trackingCode;
        } catch (\Throwable $e) {
            Log::error("WhatsApp AI: Failed to save order for {$restaurant->name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Geocode an address string using Nominatim (OpenStreetMap).
     * Returns [lat, lng] or null on failure.
     * Comprehensive structured logging for queries, responses, and place IDs.
     *
     * @return array{0: float, 1: float}|null
     */
    private function geocodeAddress(string $address, string $city = ''): ?array
    {
        $clean = trim($address);
        if ($clean === '' || $clean === 'Delivery order via WhatsApp') {
            return null;
        }

        // Clean query of punctuation/markdown noise
        $clean = preg_replace('/[#*`~_]/', ' ', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        $queriesToTry = [];
        $queriesToTry[] = trim($clean . ($city ? ", {$city}" : '') . ', Pakistan');

        // If query has commas (e.g. "Model Town B, House 12"), try primary area + city
        if (str_contains($clean, ',')) {
            $parts = array_map('trim', explode(',', $clean));
            if (!empty($parts[0]) && strlen($parts[0]) > 3) {
                $queriesToTry[] = trim($parts[0] . ($city ? ", {$city}" : '') . ', Pakistan');
            }
        }

        foreach ($queriesToTry as $query) {
            try {
                $encoded = urlencode($query);
                $url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q={$encoded}";

                Log::info("Geocoding Request", [
                    'original_address' => $address,
                    'city'             => $city,
                    'query'            => $query,
                    'url'              => $url,
                ]);

                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Foodio-RestaurantBot/1.0'])
                    ->get($url);

                $status = $response->status();
                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data[0]['lat']) && ! empty($data[0]['lon'])) {
                        $lat = (float) $data[0]['lat'];
                        $lon = (float) $data[0]['lon'];

                        Log::info("Geocoding Found Match", [
                            'original_address' => $address,
                            'query'            => $query,
                            'status'           => $status,
                            'place_id'         => $data[0]['place_id'] ?? null,
                            'osm_type'         => $data[0]['osm_type'] ?? null,
                            'class'            => $data[0]['class'] ?? null,
                            'type'             => $data[0]['type'] ?? null,
                            'display_name'     => $data[0]['display_name'] ?? null,
                            'lat'              => $lat,
                            'lon'              => $lon,
                        ]);

                        return [$lat, $lon];
                    } else {
                        Log::info("Geocoding Empty Result", [
                            'original_address' => $address,
                            'query'            => $query,
                            'status'           => $status,
                        ]);
                    }
                } else {
                    Log::warning("Geocoding HTTP Error", [
                        'query'  => $query,
                        'status' => $status,
                        'body'   => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Geocoding Exception for [{$query}]: " . $e->getMessage());
            }
        }

        Log::warning("Geocoding Failed: No coordinates found for address [{$address}] (City: [{$city}])");
        return null;
    }

    /**
     * Reverse-geocode latitude and longitude into human-readable street/locality address using Nominatim with caching.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $roundLat = round($lat, 5);
        $roundLng = round($lng, 5);
        $cacheKey = "rev_geo_{$roundLat}_{$roundLng}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($roundLat, $roundLng) {
            try {
                $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$roundLat}&lon={$roundLng}&zoom=18&addressdetails=1";
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Foodio-RestaurantBot/1.0'])
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data['display_name'])) {
                        // Extract concise recognizable locality parts (e.g. "Street 4, Sector G-9/1, Islamabad")
                        $parts = array_map('trim', explode(',', $data['display_name']));
                        // Take up to first 4 meaningful parts, omitting redundant country/postcode tail if present
                        $meaningful = array_slice($parts, 0, min(count($parts), 4));
                        return implode(', ', $meaningful);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Reverse geocoding failed for [{$roundLat}, {$roundLng}]: " . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Extract the core street address / locality from a conversational message.
     */
    private function extractAddressFromText(string $text): string
    {
        $cleaned = $text;
        $remove = [
            '/^(?:mera\s+)?address\s*(?:hai|ye\s*hai|is)?\s*[:*–-]?/iu',
            '/^(?:deliver\s*to|delivery\s*address)\s*[:*–-]?/iu',
            '/^(?:ghar\s+ka\s+pata|pata|location)\s*[:*–-]?/iu',
            '/\b(?:bhejein|bhej\s*do|deliver\s*kardein|order\s*karo)\b/iu',
            '/\b(?:near|opposite|behind)\b/iu',
            '/\b03\d{9}\b/',
            '/\+92\d{10}/',
        ];
        foreach ($remove as $pattern) {
            $cleaned = preg_replace($pattern, ' ', $cleaned);
        }
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
        return $cleaned ?: $text;
    }

    /**
     * Retrieve or generate location confirmation token for WhatsApp session.
     */
    public static function getOrCreateLocationToken(Restaurant $restaurant, string $customerPhone, string $recipientJid): string
    {
        $sessionKey = "wa_session_{$restaurant->id}_{$customerPhone}";
        $cachedToken = Cache::get("loc_token_for_{$sessionKey}");
        if ($cachedToken) {
            return $cachedToken;
        }

        $token = substr(hash('sha256', "loc_{$restaurant->id}_{$customerPhone}_" . (config('app.key') ?: 'foodio')), 0, 16);
        Cache::put("loc_token_{$token}", [
            'restaurant_id'  => $restaurant->id,
            'customer_phone' => $customerPhone,
            'recipient_jid'  => $recipientJid,
        ], now()->addHours(6));
        Cache::put("loc_token_for_{$sessionKey}", $token, now()->addHours(6));

        return $token;
    }

    /**
     * Calculate straight-line distance in kilometers between two GPS coordinates (Haversine formula).
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }

    /**
     * Retrieve or resolve restaurant kitchen GPS coordinates.
     *
     * @return array{0: float, 1: float}|null
     */
    private function getRestaurantCoords(Restaurant $restaurant): ?array
    {
        if ($restaurant->restaurant_lat && $restaurant->restaurant_lng) {
            return [(float) $restaurant->restaurant_lat, (float) $restaurant->restaurant_lng];
        }

        // Fallback to known city coords
        $city = mb_strtolower(trim($restaurant->city ?? ''));
        $knownCities = [
            'lodhran' => [29.5405, 71.6336], 'multan' => [30.1575, 71.5249],
            'bahawalpur' => [29.3544, 71.6911], 'lahore' => [31.5204, 74.3587],
            'faisalabad' => [31.4504, 73.1350], 'rawalpindi' => [33.5651, 73.0169],
            'islamabad' => [33.6844, 73.0479], 'karachi' => [24.8607, 67.0011],
            'peshawar' => [34.0151, 71.5249], 'quetta' => [30.1798, 66.9750],
            'gujranwala' => [32.1877, 74.1945], 'sialkot' => [32.4945, 74.5229],
            'sargodha' => [32.0836, 72.6711], 'dera ghazi khan' => [30.0561, 70.6403],
            'sahiwal' => [30.6682, 73.1114], 'okara' => [30.8081, 73.4458],
            'khanewal' => [30.3017, 71.9321], 'vehari' => [30.0452, 72.3489],
            'rahim yar khan' => [28.4212, 70.2989], 'hyderabad' => [25.3960, 68.3578],
            'sukkur' => [27.7052, 68.8574],
        ];

        foreach ($knownCities as $kCity => $coords) {
            if ($city !== '' && (mb_strpos($city, $kCity) !== false || mb_strpos($kCity, $city) !== false)) {
                return $coords;
            }
        }

        // Try geocoding address
        if (!empty($restaurant->address) || !empty($restaurant->city)) {
            $addr = trim(($restaurant->address ?? '') . ' ' . ($restaurant->city ?? ''));
            $coords = $this->geocodeAddress($addr, $restaurant->city ?? '');
            if ($coords) {
                $restaurant->update([
                    'restaurant_lat' => $coords[0],
                    'restaurant_lng' => $coords[1],
                ]);
                return $coords;
            }
        }

        return null;
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
    //  Order Cancellation Handling
    // ──────────────────────────────────────────────────────────────────────────

    private function handleOrderCancellation(Restaurant $restaurant, string $customerPhone, ?string $explicitCode): string
    {
        $query = Order::where('restaurant_id', $restaurant->id);

        if ($explicitCode) {
            $order = (clone $query)->where('tracking_code', $explicitCode)->first();
        } else {
            $order = null;
        }

        if (! $order && ! empty($customerPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
            $shortPhone = substr($cleanPhone, -9);
            $order = (clone $query)
                ->where(function ($q) use ($cleanPhone, $shortPhone) {
                    $q->where('customer_phone', 'like', "%{$shortPhone}%")
                      ->orWhere('customer_phone', $cleanPhone);
                })
                ->whereNotIn('status', ['cancelled', 'delivered'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if (! $order) {
            return "🔍 *No Active Order Found to Cancel*\n\nWe couldn't find an active order for your number.\nIf you have a tracking code, reply like *cancel [CODE]* or call us directly at " . ($restaurant->phone ?: 'our direct number') . ".";
        }

        // Check cancellation eligibility based on current status
        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);

            // Alert restaurant owner / kitchen manager immediately via WhatsApp
            $notifyPhone = $restaurant->manager_phone ?: $restaurant->owner_phone;
            if ($notifyPhone) {
                $cancelAlert = "⚠️ *ORDER CANCELLED BY CUSTOMER*\n\n" .
                    "📦 *Order:* #{$order->tracking_code}\n" .
                    "📱 *Customer:* {$order->customer_phone}\n" .
                    "👤 *Name:* {$order->customer_name}\n" .
                    "💰 *Total:* Rs. " . number_format((float) $order->total, 0) . "\n\n" .
                    "This pending order was cancelled by the customer via WhatsApp.";
                BotEvolutionClient::sendMessage($restaurant, $notifyPhone, $cancelAlert);
            }

            Log::info("WhatsApp AI: Order #{$order->tracking_code} cancelled by customer {$customerPhone} for restaurant {$restaurant->name}");

            return "❌ *Order Cancelled Successfully*\n\nYour order *#{$order->tracking_code}* has been cancelled.\nIf you change your mind, feel free to check our *menu* anytime to order again! 🙏";
        }

        if (in_array($order->status, ['confirmed', 'preparing', 'out_for_delivery'], true)) {
            $statusNote = $order->status === 'out_for_delivery'
                ? "is already out for delivery with our rider"
                : "is already being prepared fresh in the kitchen";

            $contactPhone = $restaurant->phone ?: ($restaurant->manager_phone ?: $restaurant->owner_phone);
            $phoneSnippet = $contactPhone ? " at *{$contactPhone}*" : "";

            return "⚠️ *Cannot Cancel Order #{$order->tracking_code} via Chat*\n\nYour order {$statusNote}.\nPlease call the restaurant directly{$phoneSnippet} right away to request urgent changes or assistance.";
        }

        if ($order->status === 'delivered') {
            return "ℹ️ Order *#{$order->tracking_code}* has already been marked as *delivered*. We hope you enjoyed it! ⭐";
        }

        return "ℹ️ Order *#{$order->tracking_code}* has already been cancelled.";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Post-Delivery Feedback & Star Rating Handling
    // ──────────────────────────────────────────────────────────────────────────

    private function handleDeliveredFeedback(Restaurant $restaurant, string $customerPhone, string $recipientJid, string $text): bool
    {
        // 1. Check if the message resembles a rating or star feedback
        // Patterns: single digit 1-5, "5 star", "5/5", "4 stars - great food", "⭐ 5", "⭐⭐⭐⭐⭐"
        $rating = null;
        $comment = '';

        if (preg_match('/^([1-5])\s*(?:\/|out\s+of)?\s*5?(?:\s*stars?)?(?:\s*[-–—:]\s*(.*))?$/i', $text, $m)) {
            $rating = (int) $m[1];
            $comment = trim($m[2] ?? '');
        } elseif (preg_match('/^([1-5])\s*star\b(?:\s*[-–—:]\s*(.*))?$/i', $text, $m)) {
            $rating = (int) $m[1];
            $comment = trim($m[2] ?? '');
        } elseif (preg_match('/^[⭐*]{1,5}$/u', $text)) {
            $rating = mb_substr_count($text, '⭐') ?: strlen($text);
            $comment = '';
        }

        if (! $rating || $rating < 1 || $rating > 5) {
            return false;
        }

        // 2. Find if this customer had a recently delivered order (within 48 hours)
        $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
        $shortPhone = substr($cleanPhone, -9);

        $deliveredOrder = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'delivered')
            ->where('updated_at', '>=', now()->subHours(48))
            ->where(function ($q) use ($cleanPhone, $shortPhone) {
                $q->where('customer_phone', 'like', "%{$shortPhone}%")
                  ->orWhere('customer_phone', $cleanPhone);
            })
            ->latest('updated_at')
            ->first();

        if (! $deliveredOrder) {
            // Not a post-delivery context — let it flow to AI or normal conversation
            return false;
        }

        // 3. Check if feedback already recorded for this delivered order timeframe
        $alreadySubmitted = Feedback::where('restaurant_id', $restaurant->id)
            ->where(function ($q) use ($cleanPhone, $shortPhone) {
                $q->where('user_phone', 'like', "%{$shortPhone}%")
                  ->orWhere('user_phone', $cleanPhone);
            })
            ->where('created_at', '>=', $deliveredOrder->updated_at->subMinutes(5))
            ->exists();

        if ($alreadySubmitted) {
            return false;
        }

        // 4. Save feedback
        try {
            $customerName = $deliveredOrder->customer_name ?: 'Valued Customer';
            Feedback::create([
                'restaurant_id' => $restaurant->id,
                'user_name'     => $customerName,
                'user_phone'    => $customerPhone,
                'rating'        => $rating,
                'comment'       => $comment !== '' ? $comment : "Rated {$rating}/5 stars via WhatsApp",
                'category'      => 'food',
                'is_reviewed'   => false,
            ]);

            $starIcons = str_repeat('⭐', $rating);
            $reply = "🌟 *Thank you for your review!* {$starIcons}\n\n" .
                     "We've recorded your {$rating}/5 star rating for *{$restaurant->name}*.\n" .
                     "Your feedback helps us continue serving you the freshest and tastiest food! 🙏❤️";

            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $reply);
            Log::info("WhatsApp AI: Recorded {$rating}-star feedback from {$customerPhone} for {$restaurant->name}");
            return true;
        } catch (\Throwable $e) {
            Log::error("WhatsApp AI: Error saving feedback: " . $e->getMessage());
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Smart fallback
    // ──────────────────────────────────────────────────────────────────────────

    private function smartFallback(string $text, Restaurant $restaurant, array $history = []): string
    {
        $name  = $restaurant->name ?: 'our restaurant';
        $lower = strtolower($text);

        // 1. If user confirms after an order summary was already presented
        $hasSummaryInHistory = false;
        foreach ($history as $h) {
            if ($h['role'] === 'assistant' && stripos($h['content'], 'order summary') !== false) {
                $hasSummaryInHistory = true;
                break;
            }
        }

        if ($hasSummaryInHistory && preg_match('/^(?:ha|haa|haan|yes|yep|yeah|ok|theek|thk|confirm|done|jee|ji|kr do|kar do)\b/i', $lower)) {
            return "Your order is placed! Shukriya 😊 Aapka order kitchen ko bhej diya gaya hai.";
        }

        // 2. If user provides name, address, or payment details
        if (preg_match('/(?:name|naam|address|pata|ghar|street|road|delivery|payment|cash|cod|jazzcash|easypaisa)\b/i', $lower)) {
            $dbItems = $restaurant->menuItems()->where('is_available', true)->get();
            $orderLines = [];
            $subtotal = 0.0;
            $userTexts = array_map(fn($m) => $m['content'], array_filter($history, fn($m) => $m['role'] === 'user'));
            $allUserText = implode(' ', $userTexts) . ' ' . $text;

            foreach ($dbItems as $item) {
                if ($item->price > 0 && stripos($allUserText, $item->name) !== false) {
                    $qty = 1;
                    if (preg_match('/(\d+)\s*(?:x\s*)?' . preg_quote($item->name, '/') . '/i', $allUserText, $qm)) {
                        $qty = (int) $qm[1];
                    }
                    $lineTotal = (float) $item->price * $qty;
                    $subtotal += $lineTotal;
                    $orderLines[] = "{$qty}x {$item->name} — Rs.{$lineTotal}";
                }
            }

            if (! empty($orderLines)) {
                $deliveryFee = (float) ($restaurant->delivery_charge ?? 50);
                $grandTotal = $subtotal + $deliveryFee;
                $itemsText = implode("\n", $orderLines);

                return "─────────────────\n" .
                       "🧾 *Order Summary*\n" .
                       "{$itemsText}\n" .
                       "─────────────────\n" .
                       "Subtotal: Rs.{$subtotal}\n" .
                       "Delivery: Rs.{$deliveryFee}\n" .
                       "*Total: Rs.{$grandTotal}*\n" .
                       "─────────────────\n" .
                       "Deliver to: {$text}\n\n" .
                       "Kya main aapka order confirm kar doon? ✅\n" .
                       "_(Reply 'CONFIRM' ya 'HAAN' to place order)_";
            }
        }

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

        return "Hey! 😊 I'm here to help you order from *{$name}*!\n\nPlease reply with what you'd like to order, your name, and delivery address!";
    }

    /**
     * Build clean, beautiful, formatted customer menu for WhatsApp.
     */
    private function buildFormattedCustomerMenu(Restaurant $restaurant): string
    {
        $name = strtoupper($restaurant->name ?: 'Restaurant');
        $out  = "📋 *{$name} — OFFICIAL MENU*\n";
        $out .= "───────────────────\n";

        $categories = $restaurant->categories()
            ->with(['items' => fn($q) => $q->where('is_available', true)])
            ->orderBy('sort_order')
            ->get();

        $hasItems = false;
        foreach ($categories as $cat) {
            $items = $cat->items ?? collect();
            if ($items->isEmpty()) continue;
            $hasItems = true;
            $catName = strtoupper($cat->name);
            $out .= "\n🍽️ *{$catName}*\n";
            foreach ($items as $item) {
                $priceStr = "Rs. " . number_format((float) $item->price, 0);
                $activeSizes = $item->getActiveSizesList();
                if (!empty($activeSizes)) {
                    $parts = array_map(fn($s) => ($s['name'] ?? $s['size']) . ": Rs." . number_format($s['price'] ?? 0, 0), $activeSizes);
                    $priceStr = implode(' / ', $parts);
                }
                $desc = $item->description ? " _({$item->description})_" : "";
                $out .= "• *{$item->name}* — {$priceStr}{$desc}\n";
            }
        }

        if (!$hasItems) {
            $items = $restaurant->menuItems()->where('is_available', true)->get();
            foreach ($items as $item) {
                $priceStr = "Rs. " . number_format((float) $item->price, 0);
                $activeSizes = $item->getActiveSizesList();
                if (!empty($activeSizes)) {
                    $parts = array_map(fn($s) => ($s['name'] ?? $s['size']) . ": Rs." . number_format($s['price'] ?? 0, 0), $activeSizes);
                    $priceStr = implode(' / ', $parts);
                }
                $desc = $item->description ? " _({$item->description})_" : "";
                $out .= "• *{$item->name}* — {$priceStr}{$desc}\n";
            }
        }

        $radius = $restaurant->delivery_radius_km ?: 5.0;
        $fee    = (float) ($restaurant->delivery_charge ?? 50);
        $min    = (float) ($restaurant->minimum_order ?? 0);

        $out .= "\n───────────────────\n";
        $out .= "🛵 *Delivery Fee:* Rs. {$fee} (within {$radius} KM)\n";
        if ($min > 0) {
            $out .= "🏷️ *Minimum Order:* Rs. {$min}\n";
        }
        $out .= "✨ *Order karne ke liye:* Reply with item name & quantity!\n";
        $out .= "_(Example: \"1 Zinger Burger aur 1 Cold Drink\")_";

        return $out;
    }

    /**
     * Harmonizes the assistant reply with authoritative database order totals and line items.
     */
    public function harmonizeConfirmationBill(string $reply, Order $order): string
    {
        $authTotal    = number_format((float) $order->total, 0, '.', '');
        $authSubtotal = number_format((float) $order->subtotal, 0, '.', '');
        $authDelivery = number_format((float) $order->delivery_charge, 0, '.', '');

        // 1. Reconcile Grand Total in reply
        $reply = preg_replace(
            '/(total(?:\s*payable)?\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/iu',
            "\${1}{$authTotal}\${3}",
            $reply
        );

        // 2. Reconcile Subtotal in reply
        $reply = preg_replace(
            '/(subtotal\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/iu',
            "\${1}{$authSubtotal}\${3}",
            $reply
        );

        // 3. Reconcile Delivery Fee in reply
        $reply = preg_replace(
            '/(delivery(?:\s*charge|\s*fee)?\s*[:*–-]?\s*(?:\*\*)?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)((?:\*\*)?)/iu',
            "\${1}{$authDelivery}\${3}",
            $reply
        );

        // 4. Reconcile line item subtotals if listed in reply
        if ($order->relationLoaded('items') || $order->items()->exists()) {
            foreach ($order->items as $item) {
                $itemSubtotal = number_format((float) $item->subtotal, 0, '.', '');
                $escName = preg_quote($item->name, '/');
                $itemPattern = '/(' . $item->quantity . '\s*[xX×]\s*' . $escName . '[^\n\r]*?rs\.?\s*)([0-9,]+(?:\.[0-9]{1,2})?)/iu';
                $reply = preg_replace($itemPattern, "\${1}{$itemSubtotal}", $reply);
            }
        }
        
        // 5. Reconcile Deliver to address in reply
        if ($order->delivery_address) {
            $reply = preg_replace(
                '/(deliver\s*to\s*[:*–-]?\s*)([^\n\r*]+)/i',
                "\${1}{$order->delivery_address}",
                $reply
            );
        }

        // If the authoritative total is not in the text, append the authoritative breakdown
        if (!str_contains($reply, "Rs.{$authTotal}") && !str_contains($reply, "Rs. {$authTotal}")) {
            $reply .= "\n\n🧾 *Confirmed Bill:*\nSubtotal: Rs.{$authSubtotal}\nDelivery: Rs.{$authDelivery}\n*Total Payable: Rs.{$authTotal}*";
        }

        return $reply;
    }

    /**
     * Matches a customer reply (e.g. "1", "2", "3", "Large", "L", "Medium", "S", etc.)
     * against pending size variants.
     */
    private function matchPendingVariantSelection(string $text, array $variants): ?array
    {
        $clean = trim($text);
        if ($clean === '') return null;

        // A. Match numeric selection: 1, 2, 3, etc.
        if (preg_match('/^(?:#|\boption\s*|\bopt\s*)?(\d+)\b/i', $clean, $m)) {
            $idx = (int) $m[1];
            foreach ($variants as $v) {
                if (($v['index'] ?? 0) === $idx) {
                    return $v;
                }
            }
        }

        // B. Match canonical size name or alias
        $norm = MenuItem::normalizeSizeName($clean);
        foreach ($variants as $v) {
            if (strcasecmp($v['name'], $norm) === 0) {
                return $v;
            }
        }

        // C. Match substring / keyword in text
        $lower = strtolower($clean);
        foreach ($variants as $v) {
            $vLower = strtolower($v['name']);
            if (str_contains($lower, $vLower)) {
                return $v;
            }
            if ($vLower === 'small' && preg_match('/\b(s|sm|7["”])\b/i', $lower)) return $v;
            if ($vLower === 'medium' && preg_match('/\b(m|med|10["”])\b/i', $lower)) return $v;
            if ($vLower === 'large' && preg_match('/\b(l|lg|13["”])\b/i', $lower)) return $v;
            if ($vLower === 'xl' && preg_match('/\b(xl|extra\s*large|16["”])\b/i', $lower)) return $v;
            if ($vLower === 'half' && preg_match('/\b(half|single)\b/i', $lower)) return $v;
            if ($vLower === 'full' && preg_match('/\b(full|double)\b/i', $lower)) return $v;
            if ($vLower === 'family' && preg_match('/\b(family)\b/i', $lower)) return $v;
            if ($vLower === 'personal' && preg_match('/\b(personal)\b/i', $lower)) return $v;
            if ($vLower === 'jumbo' && preg_match('/\b(jumbo)\b/i', $lower)) return $v;
        }

        return null;
    }

    /**
     * Checks if customer's message orders an item that has multiple active sizes WITHOUT specifying a size.
     * If so, stores pending size selection and prompts the customer with numbered size choices.
     */
    private function checkAndHandlePendingSizePrompt(Restaurant $restaurant, string $text, string $sessionKey, string $recipientJid, array &$history): bool
    {
        $cleanText = trim($text);
        if ($cleanText === '') return false;

        // Skip if customer is confirming, canceling, asking for menu flyer, or sending location
        if (preg_match('/^(?:haan|yes|confirm|theek hai|kr do|kar do|ok|done|cancel|track|menu)\b/i', $cleanText)) {
            return false;
        }

        // Load available items that have multiple active sizes
        $itemsWithSizes = $restaurant->menuItems()
            ->where('is_available', true)
            ->get()
            ->filter(fn ($it) => count($it->getActiveSizesList()) > 1);

        if ($itemsWithSizes->isEmpty()) {
            return false;
        }

        $textLower = strtolower($cleanText);

        foreach ($itemsWithSizes as $item) {
            $itemNameLower = strtolower(trim($item->name));

            // Check if item name is mentioned in customer text
            $isMentioned = str_contains($textLower, $itemNameLower);
            if (!$isMentioned && strlen($itemNameLower) >= 4) {
                // Check without special chars
                $cleanItemName = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $itemNameLower));
                $cleanMsg = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $textLower));
                if (str_contains($cleanMsg, $cleanItemName)) {
                    $isMentioned = true;
                }
            }

            if ($isMentioned) {
                $activeSizes = $item->getActiveSizesList();
                $alreadySpecified = false;

                // Check if customer ALREADY specified any size variant
                foreach ($activeSizes as $sz) {
                    $sName = strtolower(MenuItem::normalizeSizeName($sz['name'] ?? $sz['size'] ?? ''));
                    if (str_contains($textLower, $sName)) {
                        $alreadySpecified = true;
                        break;
                    }
                    if ($sName === 'small' && preg_match('/\b(small|s|sm|7["”])\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'medium' && preg_match('/\b(medium|m|med|10["”])\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'large' && preg_match('/\b(large|l|lg|13["”])\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'xl' && preg_match('/\b(xl|extra\s*large|16["”])\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'regular' && preg_match('/\b(regular|reg)\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'half' && preg_match('/\b(half)\b/i', $textLower)) { $alreadySpecified = true; break; }
                    if ($sName === 'full' && preg_match('/\b(full)\b/i', $textLower)) { $alreadySpecified = true; break; }
                }

                if ($alreadySpecified) {
                    // Customer specified size (e.g. "2 Large Shahi Pizza") -> Proceed directly without prompting
                    return false;
                }

                // Customer ordered without size! Extract quantity:
                $qty = 1;
                if (preg_match('/(\d+)\s*(?:x\s*)?' . preg_quote($itemNameLower, '/') . '/i', $textLower, $qm)) {
                    $qty = (int) $qm[1];
                } elseif (preg_match('/(?:^|\s)(\d+)\s*(?:x\s*)?/i', $textLower, $qm)) {
                    $qty = (int) $qm[1];
                } elseif (preg_match('/\b(ek|one)\b/i', $textLower)) {
                    $qty = 1;
                } elseif (preg_match('/\b(do|two)\b/i', $textLower)) {
                    $qty = 2;
                } elseif (preg_match('/\b(teen|three)\b/i', $textLower)) {
                    $qty = 3;
                }
                $qty = max(1, $qty);

                // Build variants list and prompt message
                $variantsList = [];
                $sizeLines = [];
                $idx = 1;
                foreach ($activeSizes as $sz) {
                    $cName = MenuItem::normalizeSizeName($sz['name'] ?? $sz['size'] ?? '');
                    $cPrice = (float) ($sz['price'] ?? 0);
                    $variantsList[] = [
                        'index' => $idx,
                        'name'  => $cName,
                        'price' => $cPrice,
                    ];
                    $sizeLines[] = "{$idx}. {$cName} — Rs. " . number_format($cPrice, 0);
                    $idx++;
                }

                $pendingSizeKey = "pending_size_selection_{$sessionKey}";
                Cache::put($pendingSizeKey, [
                    'item_id'   => $item->id,
                    'item_name' => $item->name,
                    'quantity'  => $qty,
                    'variants'  => $variantsList,
                ], now()->addMinutes(self::SESSION_TTL));

                $icon = str_contains(strtolower($item->name), 'pizza') ? '🍕' : (str_contains(strtolower($item->name), 'burger') ? '🍔' : '🍽️');
                $promptText = "{$icon} *{$item->name}*\nPlease select a size:\n\n" . implode("\n", $sizeLines);

                $history[] = ['role' => 'user', 'content' => $text];
                $history[] = ['role' => 'assistant', 'content' => $promptText];
                Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));

                BotEvolutionClient::sendMessage($restaurant, $recipientJid, $promptText);
                return true;
            }
        }

        return false;
    }
}
