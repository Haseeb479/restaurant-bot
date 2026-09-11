<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from EvolutionAPI.
     *
     * EvolutionAPI posts events here for all instances. The instance name in the
     * payload ("rest_{id}") guarantees perfect tenant isolation.
     */
    public function handle(Request $request): JsonResponse
    {
        // ── Webhook authentication ─────────────────────────────────────────────
        // Verify the request if an apikey/x-api-key header was provided.
        // (Evolution API does not send apikey on default outgoing webhooks,
        // so we only reject if a key was explicitly sent and is mismatched).
        $configuredKey = trim((string) env('EVOLUTION_API_KEY', ''));
        $incomingKey   = (string) ($request->header('apikey') ?? $request->header('x-api-key') ?? '');
        if ($incomingKey !== '' && $configuredKey !== '' && !hash_equals($configuredKey, $incomingKey)) {
            Log::warning('Evolution Webhook: Unauthorized request — invalid apikey header.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $rawEvent = (string) ($request->input('event') ?? $request->input('type') ?? '');
        $event    = strtolower(str_replace(['.', '-'], '_', $rawEvent));

        $instance = (string) (
            $request->input('instance')
            ?? $request->input('instanceName')
            ?? $request->input('data.instance')
            ?? $request->input('data.instanceName')
            ?? $request->header('instance')
            ?? $request->header('x-instance')
            ?? ''
        );
        $data     = (array) ($request->input('data') ?? $request->all());

        if ($instance === '') {
            Log::info("Evolution Webhook: Missing instance in payload: " . json_encode($request->all()));
            return response()->json(['status' => 'ignored', 'reason' => 'missing_instance'], 200);
        }

        // Find restaurant by evolution_instance_id or numeric suffix in "rest_42"
        $restaurantId = str_replace('rest_', '', $instance);
        $restaurant   = Restaurant::where('evolution_instance_id', $instance)
            ->orWhere('id', is_numeric($restaurantId) ? (int) $restaurantId : 0)
            ->first();

        if (! $restaurant) {
            Log::info("Evolution Webhook: No restaurant matching instance [{$instance}]");
            return response()->json(['status' => 'ignored', 'reason' => 'restaurant_not_found'], 200);
        }

        // Auto-heal evolution_instance_id if it was not stored previously
        if (empty($restaurant->evolution_instance_id)) {
            $restaurant->forceFill(['evolution_instance_id' => $instance])->save();
        }

        // 1. Connection Update event (open, close, connecting)
        if ($event === 'connection_update') {
            $state = $data['state'] ?? $data['status'] ?? '';
            $this->handleConnectionUpdate($restaurant, (string) $state);
            return response()->json(['status' => 'processed', 'event' => 'connection.update']);
        }

        // 2. QR Code update
        if ($event === 'qrcode_updated') {
            $restaurant->forceFill(['bot_status' => 'qr_pending', 'evolution_status' => 'qr_pending'])->save();
            return response()->json(['status' => 'processed', 'event' => 'qrcode.updated']);
        }

        // 3. Incoming message (MESSAGES_UPSERT)
        if ($event === 'messages_upsert' || isset($data['key']) || isset($data['message'])) {
            // ── Req 17: Controller-level Message ID Deduplication Guard ──────
            // Check dedup here so the HTTP response can signal 'deduplicated'.
            // The inner handleIncomingMessage also has a guard as a second fence.
            $msgData  = $data;
            if (isset($data[0]) && is_array($data[0])) {
                $msgData = $data[0]; // Batch — check first message ID
            }
            $msgKey    = $msgData['key'] ?? $msgData['message']['key'] ?? [];
            $messageId = (string) ($msgKey['id'] ?? '');
            if ($messageId !== '' && !\Illuminate\Support\Facades\Cache::has("wa_msg_seen_{$restaurant->id}_{$messageId}")) {
                // Not seen yet — proceed to process
                $this->handleIncomingMessage($restaurant, $data);
            } elseif ($messageId !== '') {
                Log::info("Evolution Webhook: Duplicate message event [{$messageId}] ignored (controller) for restaurant {$restaurant->name}");
                return response()->json(['status' => 'deduplicated', 'event' => 'messages.upsert']);
            } else {
                $this->handleIncomingMessage($restaurant, $data);
            }
            return response()->json(['status' => 'processed', 'event' => 'messages.upsert']);
        }

        return response()->json(['status' => 'acknowledged', 'event' => $event], 200);
    }

    /**
     * Update restaurant connection status in database.
     */
    private function handleConnectionUpdate(Restaurant $restaurant, string $state): void
    {
        $statusMap = [
            'open'       => 'connected',
            'close'      => 'disconnected',
            'connecting' => 'qr_pending',
        ];

        $newStatus      = $statusMap[$state] ?? 'disconnected';
        $previousStatus = $restaurant->bot_status;

        $updateData = [
            'bot_status'       => $newStatus,
            'evolution_status' => $newStatus,
            'bot_last_seen_at' => $newStatus === 'connected' ? now() : $restaurant->bot_last_seen_at,
        ];

        if ($newStatus === 'disconnected' && $previousStatus === 'connected') {
            $updateData['last_error']    = 'WhatsApp session unlinked or disconnected';
            $updateData['last_error_at'] = now();
        }

        $restaurant->forceFill($updateData)->save();

        if ($newStatus === 'connected') {
            AuditLog::log('bot.connected', "WhatsApp bot connected for {$restaurant->name} (#{$restaurant->id}) via Evolution instance {$restaurant->evolution_instance_id}");
        } elseif ($newStatus === 'disconnected' && $previousStatus === 'connected') {
            AuditLog::log('bot.disconnected', "⚠️ WhatsApp bot disconnected unexpectedly for {$restaurant->name} (#{$restaurant->id}). Owner should scan QR code.");
        }
    }

    /**
     * Process an incoming customer message.
     */
    private function handleIncomingMessage(Restaurant $restaurant, array $data): void
    {
        // Handle array of messages if Evolution sent a batch
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $msg) {
                if (is_array($msg)) {
                    $this->handleIncomingMessage($restaurant, $msg);
                }
            }
            return;
        }

        $messageObj = $data['message'] ?? $data;
        $key        = $data['key'] ?? $messageObj['key'] ?? [];

        // Ignore messages sent by the bot itself
        if (! empty($key['fromMe'])) {
            return;
        }

        $remoteJid = (string) ($key['remoteJid'] ?? '');

        // Ignore status broadcasts and group chats
        if (str_contains($remoteJid, '@g.us') || $remoteJid === 'status@broadcast') {
            return;
        }

        // ── Req 17: Message ID Deduplication Guard ────────────────────────────
        $messageId = (string) ($key['id'] ?? '');
        if ($messageId !== '') {
            $dedupKey = "wa_msg_seen_{$restaurant->id}_{$messageId}";
            if (\Illuminate\Support\Facades\Cache::has($dedupKey)) {
                Log::info("Evolution Webhook: Duplicate message event [{$messageId}] ignored for restaurant {$restaurant->name}");
                return;
            }
            \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->addMinutes(10));
        }

        // Extract customer phone number
        $customerPhone = preg_replace('/[^0-9]/', '', explode('@', $remoteJid)[0]);
        if ($customerPhone === '' && ! str_contains($remoteJid, '@')) {
            return;
        }

        // Extract message text content from various WhatsApp message types
        $text = trim((string) (
            $messageObj['conversation']
            ?? $messageObj['extendedTextMessage']['text']
            ?? $messageObj['imageMessage']['caption']
            ?? $messageObj['videoMessage']['caption']
            ?? $messageObj['buttonsResponseMessage']['selectedButtonId']
            ?? $messageObj['listResponseMessage']['singleSelectReply']['selectedRowId']
            ?? $messageObj['text']
            ?? ''
        ));

        // ── Detect WhatsApp Native Location Share (locationMessage / liveLocationMessage) ──
        $locMsg = $messageObj['locationMessage'] ?? $messageObj['liveLocationMessage'] ?? null;
        $locationCoords = null;
        if ($locMsg) {
            $rawLat = $locMsg['degreesLatitude'] ?? $locMsg['latitude'] ?? null;
            $rawLng = $locMsg['degreesLongitude'] ?? $locMsg['longitude'] ?? null;
            if ($rawLat !== null && $rawLng !== null && is_numeric($rawLat) && is_numeric($rawLng)) {
                $locationCoords = [
                    'lat'     => (float) $rawLat,
                    'lng'     => (float) $rawLng,
                    'name'    => (string) ($locMsg['name'] ?? ''),
                    'address' => (string) ($locMsg['address'] ?? ''),
                ];
                if ($text === '') {
                    $locLabel = $locationCoords['name'] ?: $locationCoords['address'] ?: 'Pin on map';
                    $text = "📍 [Customer shared location pin: {$locationCoords['lat']}, {$locationCoords['lng']} ({$locLabel})]";
                }
                $maskedJid = \App\Support\LogSanitizer::maskPhone($remoteJid);
                Log::info("Evolution Webhook: Received native location from [{$maskedJid}] for {$restaurant->name} (lat/lng sanitized)");
            }
        }

        if ($text === '' && ! $locationCoords) {
            return;
        }

        $maskedJid = \App\Support\LogSanitizer::maskPhone($remoteJid);
        $redactedMsg = \App\Support\LogSanitizer::redactMessage($text);
        Log::info("Evolution Webhook: Incoming message for {$restaurant->name} from [{$maskedJid}]: {$redactedMsg}");

        // ── GAP 4: Human handoff — mute AI while owner is handling manually ───
        // If the restaurant owner replied to this customer directly via WhatsApp,
        // the conversations.human_handling_until column is set. While it is in
        // the future we silence the bot so the owner's conversation is not
        // interrupted. This mirrors the Node.js SessionManager.isHandoffActive().
        $handoffActive = \App\Models\Conversation::where('restaurant_id', $restaurant->id)
            ->where('customer_phone', preg_replace('/[^0-9]/', '', $customerPhone))
            ->where('human_handling_until', '>', now())
            ->exists();

        if ($handoffActive) {
            $maskedPhone = \App\Support\LogSanitizer::maskPhone($customerPhone);
            Log::info("Evolution Webhook: AI muted (human handoff active) for {$maskedPhone} at {$restaurant->name}");
            return;
        }

        // Target recipient: use remoteJid directly to ensure 100% reply delivery for @lid & standard accounts
        $recipientJid = $remoteJid ?: $customerPhone;

        // ── D2: Process message (sync by default for instant reply; async if configured) ──
        if (config('queue.default') === 'sync' || env('WHATSAPP_PROCESS_SYNC', true)) {
            \App\Jobs\ProcessWhatsAppMessage::dispatchSync(
                $restaurant,
                $customerPhone ?: $recipientJid,
                $recipientJid,
                $text,
                $locationCoords
            );
        } else {
            \App\Jobs\ProcessWhatsAppMessage::dispatch(
                $restaurant,
                $customerPhone ?: $recipientJid,
                $recipientJid,
                $text,
                $locationCoords
            );
        }
    }

    /**
     * Send tracking status update back to customer.
     */
    private function handleTrackingInquiry(Restaurant $restaurant, string $customerPhone, string $trackingCode): void
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

        BotEvolutionClient::sendMessage($restaurant, $customerPhone, $reply);
    }

    /**
     * Send greeting and menu summary back to customer.
     */
    private function handleGreetingOrMenu(Restaurant $restaurant, string $customerPhone, string $text): void
    {
        $greeting = $restaurant->greeting_message
            ?: "👋 Welcome to *{$restaurant->name}*! How can we serve you today?";

        $menuItems = $restaurant->menuItems()
            ->where('is_available', true)
            ->take(8)
            ->get();

        $menuText = "";
        if ($menuItems->isNotEmpty()) {
            $menuText = "\n\n📋 *Popular Menu Items:*\n";
            foreach ($menuItems as $item) {
                $menuText .= "• *{$item->name}* — Rs. " . number_format($item->price, 0) . "\n";
            }
        }

        $reply = "{$greeting}{$menuText}\n\n💡 Reply with your desired items and delivery address, or send your *Tracking Code* to track an existing order.";

        BotEvolutionClient::sendMessage($restaurant, $customerPhone, $reply);
    }
}
