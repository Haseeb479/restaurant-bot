<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LocationConfirmationController extends Controller
{
    /**
     * Show the interactive map-pin confirmation page.
     * Supports both pre-order session tokens and post-order tracking codes.
     */
    public function show(Request $request, string $token): View
    {
        $token = trim($token);

        // 1. Check if token is an active WhatsApp session token (pre-order)
        $sessionData = Cache::get("loc_token_{$token}");
        if ($sessionData && !empty($sessionData['restaurant_id'])) {
            $restaurant = Restaurant::find($sessionData['restaurant_id']);
            if ($restaurant) {
                $phone = $sessionData['customer_phone'] ?? '';
                $sessionKey = "wa_session_{$restaurant->id}_{$phone}";
                $cachedCoords = Cache::get("verified_delivery_coords_{$sessionKey}");
                $cachedAddr   = Cache::get("verified_delivery_address_{$sessionKey}", '');

                $restLat = $restaurant->restaurant_lat ? (float) $restaurant->restaurant_lat : null;
                $restLng = $restaurant->restaurant_lng ? (float) $restaurant->restaurant_lng : null;

                // Initial pin position: cached coords > restaurant coords > city fallback
                $initLat = $cachedCoords ? (float) $cachedCoords[0] : ($restLat ?: 31.5204);
                $initLng = $cachedCoords ? (float) $cachedCoords[1] : ($restLng ?: 74.3587);

                return view('location.confirm', [
                    'restaurant'   => $restaurant,
                    'token'        => $token,
                    'isOrder'      => false,
                    'initialLat'   => $initLat,
                    'initialLng'   => $initLng,
                    'hasCoords'    => !empty($cachedCoords),
                    'address'      => $cachedAddr,
                    'order'        => null,
                    'trackingCode' => null,
                ]);
            }
        }

        // 2. Check if token is an Order Tracking Code (post-order confirmation/edit)
        $order = Order::with('restaurant')
            ->where('tracking_code', strtoupper($token))
            ->first();

        if ($order) {
            $restaurant = $order->restaurant;
            $restLat = $restaurant->restaurant_lat ? (float) $restaurant->restaurant_lat : null;
            $restLng = $restaurant->restaurant_lng ? (float) $restaurant->restaurant_lng : null;

            $hasCoords = !is_null($order->delivery_lat) && !is_null($order->delivery_lng);
            $initLat   = $hasCoords ? (float) $order->delivery_lat : ($restLat ?: 31.5204);
            $initLng   = $hasCoords ? (float) $order->delivery_lng : ($restLng ?: 74.3587);

            return view('location.confirm', [
                'restaurant'   => $restaurant,
                'token'        => $token,
                'isOrder'      => true,
                'initialLat'   => $initLat,
                'initialLng'   => $initLng,
                'hasCoords'    => $hasCoords,
                'address'      => $order->delivery_address ?: '',
                'order'        => $order,
                'trackingCode' => $order->tracking_code,
            ]);
        }

        abort(404, 'Location link expired or invalid. Please request a new link from WhatsApp.');
    }

    /**
     * Save the confirmed latitude, longitude, and address.
     */
    public function update(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'lat'     => 'required|numeric|between:-90,90',
            'lng'     => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
        ]);

        $token = trim($token);
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $address = trim((string) $request->input('address', ''));

        // 1. Session token (pre-order)
        $sessionData = Cache::get("loc_token_{$token}");
        if ($sessionData && !empty($sessionData['restaurant_id'])) {
            $restaurantId = (int) $sessionData['restaurant_id'];
            $phone = (string) ($sessionData['customer_phone'] ?? '');
            $recipientJid = (string) ($sessionData['recipient_jid'] ?? $phone);

            $sessionKey = "wa_session_{$restaurantId}_{$phone}";
            Cache::put("verified_delivery_coords_{$sessionKey}", [$lat, $lng], now()->addMinutes(45));

            if ($address !== '') {
                Cache::put("verified_delivery_address_{$sessionKey}", $address, now()->addMinutes(45));
            }

            Log::info("Customer confirmed location pin before order", [
                'restaurant_id' => $restaurantId,
                'phone'         => $phone,
                'lat'           => $lat,
                'lng'           => $lng,
                'address'       => $address,
            ]);

            // Notify customer in WhatsApp
            $restaurant = Restaurant::find($restaurantId);
            if ($restaurant && $recipientJid) {
                try {
                    $addrNote = $address ? "\n🏠 *Address:* {$address}" : "";
                    BotEvolutionClient::sendMessage(
                        $restaurant,
                        $recipientJid,
                        "📍 *Delivery Pin Confirmed!* ✅\n" .
                        "Hamain aapki exact location mil gayi hai.{$addrNote}\n\n" .
                        "Aap WhatsApp par apna order continue ya confirm kar sakte hain! 😊"
                    );
                } catch (\Throwable $e) {
                    Log::warning("Failed to send WhatsApp confirmation message: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery location pin confirmed! You can now return to WhatsApp.',
                'lat'     => $lat,
                'lng'     => $lng,
            ]);
        }

        // 2. Order Tracking Code (post-order)
        $order = Order::with('restaurant')
            ->where('tracking_code', strtoupper($token))
            ->first();

        if ($order) {
            $updateData = [
                'delivery_lat' => $lat,
                'delivery_lng' => $lng,
            ];
            if ($address !== '') {
                $updateData['delivery_address'] = $address;
            }
            $order->update($updateData);

            Log::info("Customer updated order delivery pin", [
                'order_id'      => $order->id,
                'tracking_code' => $order->tracking_code,
                'lat'           => $lat,
                'lng'           => $lng,
                'address'       => $address,
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Order delivery location updated successfully!',
                'lat'          => $lat,
                'lng'          => $lng,
                'tracking_url' => url('/track/' . $order->tracking_code),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired token.',
        ], 404);
    }
}