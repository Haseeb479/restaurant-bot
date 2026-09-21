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

                $cachedPlace = Cache::get("verified_delivery_place_name_{$sessionKey}", '');

                return view('location.confirm', [
                    'restaurant'   => $restaurant,
                    'token'        => $token,
                    'isOrder'      => false,
                    'initialLat'   => $initLat,
                    'initialLng'   => $initLng,
                    'hasCoords'    => !empty($cachedCoords),
                    'address'      => $cachedAddr,
                    'placeName'    => $cachedPlace,
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
                'placeName'    => $order->delivery_place_name ?: '',
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
        $userInputAddress = trim((string) $request->input('address', ''));

        // POI-aware location resolution: Google Places API (New) Nearby Search -> Reverse geocode fallback
        // Customer exact GPS ($lat, $lng) is ALWAYS preserved as authoritative.
        $resolution = app(\App\Services\LocationResolutionService::class)->resolve($lat, $lng);
        $placeName = $resolution['delivery_place_name'] ?? null;
        $placeId = $resolution['delivery_place_id'] ?? null;
        $locationSource = ($resolution['location_source'] === 'google_places') ? 'google_places' : 'customer_pin';
        $resolvedAddress = $resolution['delivery_address'] ?? ($placeName ?: "Selected Pin Location ({$lat}, {$lng})");

        // Format final human-readable delivery address
        if ($userInputAddress !== '') {
            if ($placeName && stripos($userInputAddress, $placeName) === false) {
                $finalAddress = $userInputAddress . ', ' . ($resolvedAddress ?: $placeName);
            } elseif (! $placeName && $resolvedAddress && stripos($userInputAddress, $resolvedAddress) === false) {
                $finalAddress = $userInputAddress . ', ' . $resolvedAddress;
            } else {
                $finalAddress = $userInputAddress;
            }
        } else {
            $finalAddress = $resolvedAddress;
        }

        // 1. Session token (pre-order)
        $sessionData = Cache::get("loc_token_{$token}");
        if ($sessionData && !empty($sessionData['restaurant_id'])) {
            $restaurantId = (int) $sessionData['restaurant_id'];
            $phone = (string) ($sessionData['customer_phone'] ?? '');
            $recipientJid = (string) ($sessionData['recipient_jid'] ?? $phone);

            $sessionKey = "wa_session_{$restaurantId}_{$phone}";
            Cache::put("verified_delivery_coords_{$sessionKey}", [$lat, $lng], now()->addMinutes(45));
            Cache::put("verified_delivery_source_{$sessionKey}", $locationSource, now()->addMinutes(45));
            Cache::put("verified_delivery_address_{$sessionKey}", $finalAddress, now()->addMinutes(45));
            if ($placeName) {
                Cache::put("verified_delivery_place_name_{$sessionKey}", $placeName, now()->addMinutes(45));
            }
            if ($placeId) {
                Cache::put("verified_delivery_place_id_{$sessionKey}", $placeId, now()->addMinutes(45));
            }

            // Append confirmation event to session history so bot is fully aware upon return to WhatsApp
            $history = Cache::get($sessionKey, []);
            if (!is_array($history)) {
                $history = [];
            }
            $history[] = [
                'role' => 'user',
                'content' => "Confirmed Map Pin: [Lat: {$lat}, Lng: {$lng}]" .
                    ($placeName ? " Landmark: {$placeName}" : "") .
                    " Address: {$finalAddress}",
            ];
            $history[] = [
                'role' => 'assistant',
                'content' => "📍 *Delivery Pin Confirmed!* ✅\nHamain aapki exact location mil gayi hai." .
                    ($placeName ? "\n📍 *Landmark:* {$placeName}" : "") .
                    "\n🏠 *Address:* {$finalAddress}",
            ];
            if (count($history) > 20) {
                $history = array_slice($history, -20);
            }
            Cache::put($sessionKey, $history, now()->addMinutes(30));

            Log::info("Customer confirmed location pin before order", [
                'restaurant_id' => $restaurantId,
                'phone'         => $phone,
                'lat'           => $lat,
                'lng'           => $lng,
                'place_name'    => $placeName,
                'address'       => $finalAddress,
                'source'        => $locationSource,
            ]);

            // Notify customer in WhatsApp
            $restaurant = Restaurant::find($restaurantId);
            if ($restaurant && $recipientJid) {
                try {
                    $poiNote = $placeName ? "\n📍 *Landmark:* {$placeName}" : "";
                    $addrNote = "\n🏠 *Address:* {$finalAddress}";
                    BotEvolutionClient::sendMessage(
                        $restaurant,
                        $recipientJid,
                        "📍 *Delivery Pin Confirmed!* ✅\n" .
                        "Hamain aapki exact location mil gayi hai.{$poiNote}{$addrNote}\n\n" .
                        "Aap WhatsApp par apna order continue ya confirm kar sakte hain! 😊"
                    );
                } catch (\Throwable $e) {
                    Log::warning("Failed to send WhatsApp confirmation message: " . $e->getMessage());
                }
            }

            return response()->json([
                'success'         => true,
                'message'         => 'Delivery location pin confirmed! You can now return to WhatsApp.',
                'lat'             => $lat,
                'lng'             => $lng,
                'place_name'      => $placeName,
                'address'         => $finalAddress,
                'location_source' => $locationSource,
            ]);
        }

        // 2. Order Tracking Code (post-order)
        $order = Order::with('restaurant')
            ->where('tracking_code', strtoupper($token))
            ->first();

        if ($order) {
            $updateData = [
                'delivery_lat'        => $lat,
                'delivery_lng'        => $lng,
                'delivery_address'    => $finalAddress,
                'delivery_place_name' => $placeName,
                'delivery_place_id'   => $placeId,
                'location_source'     => $locationSource,
            ];
            $order->update($updateData);

            Log::info("Customer updated order delivery pin", [
                'order_id'      => $order->id,
                'tracking_code' => $order->tracking_code,
                'lat'           => $lat,
                'lng'           => $lng,
                'place_name'    => $placeName,
                'address'       => $finalAddress,
                'source'        => $locationSource,
            ]);

            return response()->json([
                'success'         => true,
                'message'         => 'Order delivery location updated successfully!',
                'lat'             => $lat,
                'lng'             => $lng,
                'place_name'      => $placeName,
                'address'         => $finalAddress,
                'location_source' => $locationSource,
                'tracking_url'    => url('/track/' . $order->tracking_code),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired token.',
        ], 404);
    }
}