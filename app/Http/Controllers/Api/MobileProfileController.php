<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Support\BotControlClient;
use App\Support\BotEvolutionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileProfileController extends Controller
{
    /**
     * Get owner profile, restaurant configuration, and bot connection data.
     */
    public function show(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        // Fetch live bot status
        $botStatus = 'disconnected';
        if ($restaurant->evolution_instance_name) {
            $botStatus = BotEvolutionClient::getConnectionState($restaurant) ?: ($restaurant->bot_status ?? 'disconnected');
        }

        return response()->json([
            'success'  => true,
            'profile'  => [
                'id'                 => $restaurant->id,
                'name'               => $restaurant->name,
                'owner_name'         => $restaurant->owner_name,
                'email'              => $restaurant->email,
                'owner_phone'        => $restaurant->owner_phone,
                'whatsapp_number'    => $restaurant->whatsapp_number,
                'city'               => $restaurant->city,
                'address'            => $restaurant->address,
                'delivery_charge'    => (float) $restaurant->delivery_charge,
                'minimum_order'      => (float) $restaurant->minimum_order,
                'delivery_radius_km' => (float) ($restaurant->delivery_radius_km ?? 5.0),
                'bot_status'         => $botStatus,
                'is_open'            => (bool) $restaurant->is_open,
                'created_at'         => $restaurant->created_at->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Get WhatsApp pairing QR code for bot connectivity.
     */
    public function botQr(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        // 1. Try local Node bot control client (port 3000)
        try {
            $localStatus = BotControlClient::status();
            if ($localStatus) {
                if (!empty($localStatus['qr'])) {
                    $qr = $localStatus['qr'];
                    if (!str_starts_with($qr, 'data:image')) {
                        $qr = 'data:image/png;base64,' . $qr;
                    }
                    return response()->json([
                        'success' => true,
                        'qr'      => $qr,
                        'status'  => $localStatus['status'] ?? 'qr_pending',
                        'message' => 'Scan with WhatsApp to link bot.',
                    ]);
                }
                if (($localStatus['status'] ?? '') === 'connected') {
                    return response()->json([
                        'success'    => true,
                        'qr'         => null,
                        'status'     => 'connected',
                        'bot_number' => $localStatus['bot_number'] ?? null,
                        'message'    => 'WhatsApp bot is already connected & online!',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Local bot not running or failed
        }

        // 2. Fallback to Evolution API (port 8080)
        try {
            $qrData = BotEvolutionClient::getQrCode($restaurant);
            if ($qrData && !empty($qrData['base64'])) {
                $base64 = $qrData['base64'];
                if (!str_starts_with($base64, 'data:image')) {
                    $base64 = 'data:image/png;base64,' . $base64;
                }
                return response()->json([
                    'success' => true,
                    'qr'      => $base64,
                    'code'    => $qrData['code'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => false,
            'message' => 'No active pairing session. Bot may already be connected or initializing.',
            'status'  => $restaurant->bot_status ?? 'disconnected',
        ]);
    }

    /**
     * Restart bot instance.
     */
    public function botRestart(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $res = BotEvolutionClient::restartInstance($restaurant);

        return response()->json([
            'success' => true,
            'message' => 'Bot restart command dispatched.',
            'result'  => $res,
        ]);
    }

    /**
     * Get recent active WhatsApp conversations with oversight & state.
     */
    public function conversations(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $conversations = Conversation::where('restaurant_id', $restaurant->id)
            ->orderBy('last_message_at', 'desc')
            ->take(30)
            ->get()
            ->map(function ($c) {
                $isPaused = $c->human_handling_until && $c->human_handling_until->isFuture();
                return [
                    'id'               => $c->id,
                    'customer_phone'   => $c->customer_phone,
                    'customer_name'    => $c->customer_name ?: 'Guest',
                    'customer_address' => $c->customer_address,
                    'state'            => $c->state,
                    'cart_summary'     => $c->cartSummary(),
                    'cart_total'       => $c->cartTotal(),
                    'last_message_at'  => $c->last_message_at ? $c->last_message_at->diffForHumans() : 'Recently',
                    'is_human_paused'  => $isPaused,
                    'paused_until'     => $isPaused ? $c->human_handling_until->toIso8601String() : null,
                ];
            });

        return response()->json([
            'success'       => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Toggle Human Takeover / Pause Bot for a specific customer conversation.
     */
    public function toggleConversationPause(Request $request, $conversationId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $conv = Conversation::where('restaurant_id', $restaurant->id)->find($conversationId);

        if (!$conv) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        $currentlyPaused = $conv->human_handling_until && $conv->human_handling_until->isFuture();

        if ($currentlyPaused) {
            // Resume bot immediately
            $conv->human_handling_until = null;
            $conv->save();
            $msg = 'Bot resumed for this customer.';
        } else {
            // Pause bot for 60 minutes for human takeover
            $conv->human_handling_until = now()->addMinutes(60);
            $conv->save();
            $msg = 'Bot paused for 60 minutes. You can reply directly via WhatsApp Business.';
        }

        return response()->json([
            'success'         => true,
            'is_human_paused' => !$currentlyPaused,
            'message'         => $msg,
        ]);
    }

    /**
     * Update owner password securely.
     */
    public function updatePassword(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
        ]);

        if (!\App\Http\Controllers\DashboardController::passwordMatches($validated['current_password'], $restaurant->owner_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.',
            ], 422);
        }

        $restaurant->owner_password = Hash::make($validated['new_password']);
        $restaurant->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
