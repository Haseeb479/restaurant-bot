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
        $event    = (string) $request->input('event', '');
        $instance = (string) ($request->input('instance') ?? $request->input('instanceName', ''));
        $data     = (array) $request->input('data', []);

        if ($instance === '') {
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

        // 1. Connection Update event (open, close, connecting)
        if ($event === 'connection.update' || $event === 'CONNECTION_UPDATE') {
            $state = $data['state'] ?? $data['status'] ?? '';
            $this->handleConnectionUpdate($restaurant, (string) $state);
            return response()->json(['status' => 'processed', 'event' => 'connection.update']);
        }

        // 2. QR Code update
        if ($event === 'qrcode.updated' || $event === 'QRCODE_UPDATED') {
            $restaurant->update(['bot_status' => 'qr_pending']);
            return response()->json(['status' => 'processed', 'event' => 'qrcode.updated']);
        }

        // 3. Incoming message (MESSAGES_UPSERT)
        if ($event === 'messages.upsert' || $event === 'MESSAGES_UPSERT') {
            $this->handleIncomingMessage($restaurant, $data);
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

        $newStatus = $statusMap[$state] ?? 'disconnected';

        $restaurant->update([
            'bot_status'       => $newStatus,
            'evolution_status' => $newStatus,
            'bot_last_seen_at' => $newStatus === 'connected' ? now() : $restaurant->bot_last_seen_at,
        ]);

        if ($newStatus === 'connected') {
            AuditLog::log('bot.connected', "WhatsApp bot connected for {$restaurant->name} (#{$restaurant->id}) via Evolution instance {$restaurant->evolution_instance_id}");
        }
    }

    /**
     * Process an incoming customer message.
     */
    private function handleIncomingMessage(Restaurant $restaurant, array $data): void
    {
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

        // Extract customer phone number
        $customerPhone = preg_replace('/[^0-9]/', '', explode('@', $remoteJid)[0]);
        if ($customerPhone === '') {
            return;
        }

        // Extract message text content
        $text = trim((string) (
            $messageObj['conversation']
            ?? $messageObj['extendedTextMessage']['text']
            ?? $messageObj['text']
            ?? ''
        ));

        if ($text === '') {
            return;
        }

        // Check if message is a tracking code (e.g. TRK1234 or ORD5678)
        if (preg_match('/^[A-Za-z]{2,4}\d{4,6}$/', $text) || preg_match('/^(track|status|order)\s+([A-Za-z0-9-]+)$/i', $text, $m)) {
            $trackingCode = strtoupper(trim(isset($m[2]) ? $m[2] : $text));
            $this->handleTrackingInquiry($restaurant, $customerPhone, $trackingCode);
            return;
        }

        // Basic auto-greeting / order link response
        $this->handleGreetingOrMenu($restaurant, $customerPhone, $text);
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
