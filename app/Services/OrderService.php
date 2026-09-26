<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected LocationResolutionService $locationService;

    public function __construct(LocationResolutionService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Create an order deterministically with backend location revalidation and strict database pricing.
     *
     * @param Restaurant $restaurant
     * @param array $sessionData
     * @param string $customerPhone
     * @return Order
     * @throws \DomainException
     */
    public function createOrder(Restaurant $restaurant, array $sessionData, string $customerPhone): Order
    {
        $cart = $sessionData['cart'] ?? [];
        if (empty($cart)) {
            throw new \DomainException("Cart is empty. Order cannot be created.");
        }

        $customerName = trim($sessionData['customer_name'] ?? '') ?: 'WhatsApp Customer';
        $customerAddress = trim($sessionData['customer_address'] ?? '') ?: 'WhatsApp Delivery';
        $deliveryLat = !empty($sessionData['delivery_lat']) ? (float)$sessionData['delivery_lat'] : null;
        $deliveryLng = !empty($sessionData['delivery_lng']) ? (float)$sessionData['delivery_lng'] : null;
        $deliveryPlaceName = $sessionData['poi_name'] ?? null;
        $locationSource = $sessionData['location_source'] ?? ($deliveryLat ? 'whatsapp_pin' : null);
        $calculatedDistance = null;

        // ── 1. Revalidate GPS Location & Delivery Radius Authority ─────────────
        if ($deliveryLat !== null && $deliveryLng !== null) {
            $restLat = $restaurant->restaurant_lat;
            $restLng = $restaurant->restaurant_lng;
            $maxRadius = (float)$restaurant->maxDeliveryRadiusKm();

            if ($restLat && $restLng) {
                $distanceKm = $this->locationService->calculateHaversineDistance(
                    (float)$restLat,
                    (float)$restLng,
                    $deliveryLat,
                    $deliveryLng
                );

                $calculatedDistance = round($distanceKm, 2);

                if ($distanceKm > $maxRadius) {
                    $distDisplay = round($distanceKm, 1);
                    Log::warning("OrderService: Delivery rejected. GPS distance {$distDisplay} km exceeds radius {$maxRadius} km.", [
                        'restaurant_id' => $restaurant->id,
                        'customer_phone' => $customerPhone,
                        'distance_km' => $distanceKm,
                        'max_radius_km' => $maxRadius,
                    ]);

                    throw new \DomainException("Maazrat! Yeh location hamare delivery radius ({$maxRadius} km) se bahar hai (Faasla: {$distDisplay} km). Hum sirf {$maxRadius} km ke andar delivery karte hain.");
                }
            }
        }

        // ── 2. Authoritative Price Calculation from Database (Rule 4) ─────────
        $subtotal = 0.0;
        $orderItemsData = [];

        foreach ($cart as $cItem) {
            $menuItem = MenuItem::find($cItem['item_id'] ?? null);
            if (!$menuItem) {
                continue;
            }

            $unitPrice = (float)$menuItem->price;
            $variantName = null;
            $variantId = $cItem['variant_id'] ?? null;

            if ($variantId) {
                $variant = MenuItemVariant::find($variantId);
                if ($variant) {
                    $unitPrice = (float)$variant->price;
                    $variantName = $variant->name;
                }
            }

            $qty = max(1, (int)($cItem['quantity'] ?? 1));
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $orderItemsData[] = [
                'item_id'      => $menuItem->id,
                'name'         => $menuItem->name,
                'variant_id'   => $variantId,
                'variant_name' => $variantName,
                'unit_price'   => $unitPrice,
                'quantity'     => $qty,
                'subtotal'     => $lineTotal,
            ];
        }

        if (empty($orderItemsData)) {
            throw new \DomainException("No valid menu items in cart.");
        }

        // Initial order created from WhatsApp bot: delivery charge is decided by owner upon confirmation
        $deliveryCharge = 0.0;
        $total = $subtotal;

        // ── 3. Database Transaction & Order Creation ──────────────────────────
        return DB::transaction(function () use (
            $restaurant,
            $customerPhone,
            $customerName,
            $customerAddress,
            $deliveryLat,
            $deliveryLng,
            $calculatedDistance,
            $deliveryPlaceName,
            $locationSource,
            $subtotal,
            $deliveryCharge,
            $total,
            $orderItemsData
        ) {
            $trackingCode = Order::generateTrackingCode($restaurant);

            $order = Order::create([
                'restaurant_id'        => $restaurant->id,
                'customer_phone'       => $customerPhone,
                'customer_name'        => $customerName,
                'delivery_address'     => $customerAddress,
                'delivery_lat'         => $deliveryLat,
                'delivery_lng'         => $deliveryLng,
                'delivery_distance_km' => $calculatedDistance,
                'delivery_place_name'  => $deliveryPlaceName,
                'location_source'      => $locationSource,
                'subtotal'             => $subtotal,
                'delivery_charge'      => $deliveryCharge,
                'total'                => $total,
                'payment_method'       => 'cash_on_delivery',
                'status'               => 'pending',
                'is_paid'              => false,
                'tracking_code'        => $trackingCode,
            ]);

            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $item['item_id'],
                    'name'         => $item['name'],
                    'size'         => $item['variant_name'],
                    'unit_price'   => $item['unit_price'],
                    'quantity'     => $item['quantity'],
                    'subtotal'     => $item['subtotal'],
                ]);
            }

            return $order;
        });
    }
}
