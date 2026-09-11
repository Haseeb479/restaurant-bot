<?php

namespace App\Services;

use App\Models\Feedback;
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

        // 4. Load / create session history (max 20 messages)
        $sessionKey = "wa_session_{$restaurant->id}_{$customerPhone}";
        $history    = Cache::get($sessionKey, []);

        // ── Handle Native WhatsApp Location Attachment (locationMessage) ──────
        if ($locationCoords && isset($locationCoords['lat'], $locationCoords['lng'])) {
            $lat = (float) $locationCoords['lat'];
            $lng = (float) $locationCoords['lng'];
            Cache::put("verified_delivery_coords_{$sessionKey}", [$lat, $lng], now()->addMinutes(self::SESSION_TTL));

            // Extract or reverse-geocode textual address
            $resolvedAddress = ! empty($locationCoords['address']) ? trim($locationCoords['address']) : '';
            if ($resolvedAddress === '' && ! empty($locationCoords['name'])) {
                $resolvedAddress = trim($locationCoords['name']);
            }
            if ($resolvedAddress === '') {
                $resolvedAddress = $this->reverseGeocode($lat, $lng) ?? '';
            }

            if ($resolvedAddress !== '') {
                Cache::put("verified_delivery_address_{$sessionKey}", $resolvedAddress, now()->addMinutes(self::SESSION_TTL));
            }

            $restCoords = $this->getRestaurantCoords($restaurant);
            $distKm     = $restCoords ? $this->calculateHaversineDistance($restCoords[0], $restCoords[1], $lat, $lng) : 1.0;
            $maxRadius  = $restaurant->maxDeliveryRadiusKm();

            if ($distKm > $maxRadius) {
                $refusal = "❌ *Maafi chahte hain!* Aapki pin ki gayi location hamare restaurant se *{$distKm} km* door hai.\n\n" .
                           "Hamari maximum delivery limit *{$maxRadius} km* tak hai 🛵.\n\n" .
                           "Barah-e-karam apna koi qareebi address bhejein ya take-away order karein! 😊";
                BotEvolutionClient::sendMessage($restaurant, $recipientJid, $refusal);
                return;
            }

            // Location is verified and within radius!
            $locAck = "📍 *Shukriya! Aapki exact delivery location pin receive ho gayi hai!* ✅\n" .
                      "_(Kitchen se faasla: {$distKm} km)_\n";
            if ($resolvedAddress !== '') {
                $locAck .= "🏠 *Pata:* {$resolvedAddress}\n";
            }
            $locAck .= "\nBarah-e-karam apna *Order* ya *Naam* batayein, ya agar order ready hai toh reply karein *'CONFIRM'*! 😊";

            $history[] = ['role' => 'user', 'content' => "Shared GPS Pin: [Lat: {$lat}, Lng: {$lng}]" . ($resolvedAddress !== '' ? " Address: {$resolvedAddress}" : "")];
            $history[] = ['role' => 'assistant', 'content' => $locAck];
            Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $locAck);
            return;
        }

        $history[] = ['role' => 'user', 'content' => $text];
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // ── Foodpanda-Style Delivery Radius & City Protection ────────────────
        // Validate delivery address against city lock and maximum KM radius limit
        // BEFORE invoking AI to prevent rogue deliveries or AI hallucinations.
        $looksLikeAddress = (bool) preg_match(
            '/deliver\s*to|address|ghar|house|flat|block|phase|sector|street|road|lane|bazar|colony|town|city|near|opposite|behind|mahallah|mohallah|پتہ|ایڈریس/iu',
            $text
        );

        if ($looksLikeAddress) {
            $msgLower = mb_strtolower($text);
            $restCity = mb_strtolower(trim($restaurant->city ?? ''));
            $maxRadius = $restaurant->maxDeliveryRadiusKm();

            // 1. City Lock Check:
            // If the customer explicitly mentions a major Pakistani city that does NOT match the restaurant's base city, block immediately.
            $majorCities = [
                'karachi', 'lahore', 'islamabad', 'rawalpindi', 'faisalabad', 'multan', 'peshawar',
                'quetta', 'gujranwala', 'sialkot', 'hyderabad', 'bahawalpur', 'sargodha', 'lodhran',
                'sukkur', 'larkana', 'abbottabad', 'mardan', 'kasur', 'sahiwal', 'okara', 'gujrat',
                'sheikhupura', 'jhang', 'rahim yar khan', 'muzaffargarh', 'dera ghazi khan'
            ];

            foreach ($majorCities as $city) {
                if (mb_strpos($msgLower, $city) !== false) {
                    if ($restCity !== '' && mb_strpos($restCity, $city) === false && mb_strpos($city, $restCity) === false) {
                        $refusal = "❌ *Maafi chahte hain!* Hamara restaurant *" . ucwords($restaurant->city) . "* mein waqia hai.\n\n" .
                                   "Hum sirf *" . ucwords($restaurant->city) . "* aur uske ird-gird *{$maxRadius} km* tak deliver karte hain 🛵. " .
                                   "Doosre sheheron mein delivery dastiyab nahi hai.";
                        Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
                        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $refusal);
                        return;
                    }
                }
            }

            // 2. GPS Radius Distance Check:
            $cachedGps  = Cache::get("verified_delivery_coords_{$sessionKey}");
            $restCoords = $this->getRestaurantCoords($restaurant);
            if ($restCoords) {
                $cleanAddr  = $this->extractAddressFromText($text);
                $custCoords = $cachedGps ?: $this->geocodeAddress($cleanAddr, $restaurant->city ?? '');
                if ($custCoords) {
                    $distKm = $this->calculateHaversineDistance($restCoords[0], $restCoords[1], $custCoords[0], $custCoords[1]);
                    if ($distKm > $maxRadius) {
                        $refusal = "❌ *Maafi chahte hain!* Aapka address hamare restaurant se *{$distKm} km* door hai.\n\n" .
                                   "Hamari maximum delivery limit *{$maxRadius} km* tak hai 🛵.\n\n" .
                                   "Barah-e-karam apna koi qareebi address bhejein ya take-away / pickup order karein! 😊";
                        Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
                        BotEvolutionClient::sendMessage($restaurant, $recipientJid, $refusal);
                        return;
                    } else {
                        // Location verified within radius! Store coordinates for order save
                        Cache::put("verified_delivery_coords_{$sessionKey}", $custCoords, now()->addMinutes(self::SESSION_TTL));
                    }
                }
            }

            // 3. Fallback: Configured manual delivery areas whitelist (if owner specified any)
            $configuredAreas = array_filter(array_map(
                fn ($a) => mb_strtolower(trim($a)),
                explode(',', $restaurant->delivery_areas ?? '')
            ));

            if (! empty($configuredAreas)) {
                $matched = false;
                foreach ($configuredAreas as $area) {
                    if ($area !== '' && mb_strpos($msgLower, $area) !== false) {
                        $matched = true;
                        break;
                    }
                }

                // Only block on manual whitelist if we also didn't get a valid geocoded GPS match
                if (! $matched && empty($custCoords)) {
                    $areaList = implode(', ', array_map('ucwords', $configuredAreas));
                    $refusal  =
                        "❌ *Maafi chahte hain!* Hum abhi sirf in areas mein deliver karte hain:\n\n" .
                        "📍 *{$areaList}*\n\n" .
                        "Kya aapka address in mein se kisi area mein hai? 😊 " .
                        "Agar haan, toh apna poora address dobara bhejein!";

                    Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
                    BotEvolutionClient::sendMessage($restaurant, $recipientJid, $refusal);
                    return;
                }
            }
        }

        // ── Direct Menu Request: Send Flyer + Clean Formatted Text Menu ───────
        $isMenuQuery = (bool) preg_match('/^(?:menu|show\s+menu|send\s+menu|menu\s+dikhao|menu\s+bhejo|menu\s+card|menu\s+pdf|menu\s+photo|flyer|rate\s+list)\b/iu', $text)
            || (bool) preg_match('/^(?:منو|مینو)$/u', $text)
            || (bool) preg_match('/^(?:kya\s+hai|kya\s+items\s+hain|list\s+bhejo|menu\s+chahiye|apna\s+menu\s+bhejo)$/iu', $text);

        $isOrdering = (bool) preg_match('/\b\d+\s*(?:x|burger|pizza|biryani|deal|half|full|plate|bottle|piece|roll|chahiye|mangwana|pack|dona|bhej\s+do)\b/i', $text);

        if ($isMenuQuery && ! $isOrdering) {
            // 1. Send visual menu flyer if uploaded
            $menuFile = $restaurant->menu_image ?: $restaurant->menu_file;
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

            // 2. Send clean, structured text menu
            $formattedMenu = $this->buildFormattedCustomerMenu($restaurant);
            BotEvolutionClient::sendMessage($restaurant, $recipientJid, $formattedMenu);

            $history[] = ['role' => 'user', 'content' => $text];
            $history[] = ['role' => 'assistant', 'content' => "Menu sent! Please let me know what items and quantity you would like to order."];
            Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));
            return;
        }

        // 3. Build system prompt from live DB menu
        $systemPrompt = $this->buildSystemPrompt($restaurant, $sessionKey);

        // 4. Call Groq AI
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        $reply = $this->callGroq($messages);

        if ($reply === null) {
            // AI unavailable — smart fallback
            $reply = $this->smartFallback($text, $restaurant, $history);
            Log::warning("WhatsApp AI: Groq unavailable, using fallback for {$restaurant->name} — customer: {$customerPhone}");
        }

        $history[] = ['role' => 'assistant', 'content' => $reply];
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        // 5. Detect order confirmation and save to database
        if ($this->isOrderConfirmed($reply, $history)) {
            $trackingCode = $this->saveOrderFromHistory($restaurant, $customerPhone, $history);
            if ($trackingCode) {
                $trackUrl = url('/track/' . $trackingCode);
                $reply .= "\n\n🎉 *Order Confirmed!*\n📦 *Your Tracking Code:* *{$trackingCode}*\n🔗 *Live Order Tracking:* {$trackUrl}\n\nSend this code anytime to check your live order & rider status!";

                // Reset session so next conversation starts fresh
                Cache::forget($sessionKey);

                // ── GAP 3: Notify owner/manager via WhatsApp ──────────────────
                // Send the restaurant owner or manager a new-order WhatsApp
                // alert immediately after saving, just like the Node.js
                // NotifyService does. Uses the same Evolution instance so no
                // extra infra is required.
                $notifyPhone = $restaurant->manager_phone ?: $restaurant->owner_phone;
                if ($notifyPhone) {
                    // Retrieve saved order to get exact parsed total
                    $savedOrder  = \App\Models\Order::where('tracking_code', $trackingCode)->first();
                    $totalStr    = $savedOrder ? 'Rs. ' . number_format((float) $savedOrder->total, 0) : '';
                    $addressStr  = $savedOrder?->delivery_address ?: 'N/A';
                    $itemsStr    = '';
                    if ($savedOrder && $savedOrder->items()->exists()) {
                        $itemsStr = "\n🍽️ *Items:* " . $savedOrder->items->map(
                            fn ($i) => "{$i->quantity}x {$i->name}"
                        )->implode(', ');
                    }

                    $ownerMsg =
                        "🔔 *New Order — {$restaurant->name}!*\n\n" .
                        "📦 *#{$trackingCode}*\n" .
                        "📱 *Customer:* {$customerPhone}" .
                        $itemsStr .
                        ($totalStr ? "\n💰 *Total:* {$totalStr}" : '') .
                        "\n📍 *Address:* {$addressStr}\n\n" .
                        "✅ Login to your dashboard to confirm the order.";

                    BotEvolutionClient::sendMessage($restaurant, $notifyPhone, $ownerMsg);
                }

                // If saved order doesn't have confirmed GPS coordinates, offer pin setting link
                $savedOrder = \App\Models\Order::where('tracking_code', $trackingCode)->first();
                if ($savedOrder && (! $savedOrder->delivery_lat || ! $savedOrder->delivery_lng)) {
                    $pinLink = url('/confirm-location/' . $trackingCode);
                    $reply .= "\n\n📍 *Doorstep Pin:* Rider ke liye apna exact map pin set karein:\n👉 {$pinLink}";
                }
            } else {
                // ── GAP 5: Order save failed — don't leave customer hanging ───
                // Clear the session so the AI doesn't loop into another spurious
                // "order confirmed" on the next message. Override the AI's reply
                // with a clear retry message so the customer knows to re-order.
                Cache::forget($sessionKey);
                $reply =
                    "⚠️ Sorry — something went wrong saving your order, so it has *not* been placed.\n\n" .
                    "Please send your order again in a moment, or contact us directly.";
            }
        } else {
            Cache::put($sessionKey, $history, now()->addMinutes(self::SESSION_TTL));

            // Pre-order pin confirmation link: when AI presents summary or asks for address
            $hasVerifiedCoords = (bool) Cache::get("verified_delivery_coords_{$sessionKey}");
            if (! $hasVerifiedCoords && (
                stripos($reply, 'order summary') !== false ||
                stripos($reply, 'deliver to') !== false ||
                preg_match('/(?:address|ghar\s*ka\s*pata|location)\b/iu', $reply)
            )) {
                $locToken = self::getOrCreateLocationToken($restaurant, $customerPhone, $recipientJid);
                $pinUrl = url("/confirm-location/{$locToken}");
                $reply .= "\n\n📍 *Set / Confirm Pin on Map:*\n" .
                          "Apna exact doorstep pin set karne ke liye tap karein:\n" .
                          "👉 {$pinUrl}\n" .
                          "_(Ya WhatsApp par 📎 -> Location se direct pin share karein)_";
            }
        }

        // Send AI conversational reply (taking orders, answering queries, confirmations)
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
        $verifiedNotice = $hasVerifiedGps
            ? "  • CUSTOMER PIN STATUS: Customer's exact location pin has ALREADY been verified on the map! Accept their delivery address and proceed directly to Order Summary.\n"
            : '';

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
        $dbSubtotal  = 0.0; // Will be recalculated from DB prices

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));
            if (preg_match('/^[-*•\s]*(\d+)\s*x\s*(.+)/i', $cleanLine, $m)) {
                $qty  = (int) $m[1];
                $rest = trim($m[2], " *–—-\t\n\r\0\x0B");

                // Extract clean item name (strip price suffixes)
                $itemName = preg_replace('/(?:—|-|–|:|@|\(|→|Rs\.|PKR|₹).*$/iu', '', $rest);
                $itemName = trim($itemName, " *–—-\t\n\r\0\x0B");

                // Look up the authoritative price from the database (C1)
                $matchedDbItem = $dbMenuItems->first(function ($mi) use ($itemName) {
                    return stripos($mi->name, $itemName) !== false || stripos($itemName, $mi->name) !== false;
                });

                if ($matchedDbItem) {
                    // Always use DB price — never trust AI-generated price (C1)
                    $unitPrice  = (float) $matchedDbItem->price;
                    $lineTotal  = $unitPrice * $qty;
                    $itemName   = $matchedDbItem->name; // canonical casing
                    $menuItemId = $matchedDbItem->id;
                } else {
                    // Unknown item: fall back to AI-parsed price, but log it
                    $linePrice = 0;
                    if (preg_match_all('/(?:rs\.?|pkr\.?|₹)\s*([0-9,]+(?:\.\d+)?)/i', $rest, $pMatches)) {
                        $linePrice = (float) str_replace(',', '', end($pMatches[1]));
                    }
                    $unitPrice  = ($qty > 0 && $linePrice > 0) ? ($linePrice / $qty) : 0;
                    $lineTotal  = $linePrice;
                    $menuItemId = null;
                    Log::warning("WhatsApp AI: Item '{$itemName}' not found in DB for {$restaurant->name} — using AI price Rs.{$linePrice}");
                }

                if ($itemName !== '' && $qty > 0) {
                    $parsedItems[] = [
                        'menu_item_id' => $menuItemId,
                        'name'         => $itemName,
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
            // No DB items matched — totals remain as AI-parsed (logged above)
            $deliveryCharge = (float) ($restaurant->delivery_charge ?? 0);
        }

        try {
            // $deliveryCharge and $subtotal/$total are already set above (DB-validated).
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

            // Geocode delivery address and persist for live tracking map
            $cachedGps  = Cache::get("verified_delivery_coords_{$sessionKey}");
            $cachedAddr = Cache::get("verified_delivery_address_{$sessionKey}");
            if ($cachedAddr && ($address === 'Delivery order via WhatsApp' || empty($address))) {
                $address = $cachedAddr;
                $order->update(['delivery_address' => $address]);
            }

            $gpsCoords = $cachedGps ?: $this->geocodeAddress($address, $restaurant->city ?? '');
            if ($gpsCoords) {
                $order->update([
                    'delivery_lat' => $gpsCoords[0],
                    'delivery_lng' => $gpsCoords[1],
                ]);
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
                if (!empty($item->sizes) && is_array($item->sizes)) {
                    $parts = array_map(fn($s) => "{$s['size']}: Rs." . number_format($s['price'] ?? 0, 0), $item->sizes);
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
                if (!empty($item->sizes) && is_array($item->sizes)) {
                    $parts = array_map(fn($s) => "{$s['size']}: Rs." . number_format($s['price'] ?? 0, 0), $item->sizes);
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
}
