<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotControlClient;
use App\Support\WebhookUrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    // ─── Create Order from Bot ────────────────────────────────────────────────
    public function create(Request $request)
    {
        $validated = $request->validate([
            'customer_phone'  => 'required|string',
            'restaurant_id'   => 'required|integer',
            'customer_name'   => 'nullable|string',
            'delivery_address'=> 'required|string',
            'subtotal'        => 'required|numeric',
            'delivery_charge' => 'required|numeric',
            'total'           => 'required|numeric',
            'payment_method'  => 'nullable|string',
            'status'          => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        try {
            $restaurant = Restaurant::find($validated['restaurant_id']);

            if (!$restaurant) {
                return response()->json(['success' => false, 'error' => 'Restaurant not found'], 404);
            }

            $deliveryCharge = (float) ($restaurant->delivery_charge ?? 0);
            $subtotal       = (float) $validated['subtotal'];

            // If items array is provided, calculate subtotal authoritatively from MenuItems
            $itemsData = $request->input('items');
            if (is_array($itemsData) && count($itemsData) > 0) {
                $calcSubtotal = 0.0;
                $dbMenuItems  = $restaurant->menuItems()->get();
                $dbDeals      = $restaurant->deals()->get();

                foreach ($itemsData as &$it) {
                    $qty = max(1, (int) ($it['quantity'] ?? 1));
                    $rawName = trim((string) ($it['name'] ?? ''));
                    $rawSize = trim((string) ($it['size'] ?? ''));

                    $normName = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $rawName));
                    $normName = trim(preg_replace('/\s+/', ' ', $normName));

                    $matchedItem = $dbMenuItems->first(function ($mi) use ($normName) {
                        $normMi = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $mi->name));
                        $normMi = trim(preg_replace('/\s+/', ' ', $normMi));
                        return $normMi === $normName || stripos($normMi, $normName) !== false || stripos($normName, $normMi) !== false;
                    });

                    $unitPrice = 0.0;
                    if ($matchedItem) {
                        if ($matchedItem->hasSizes() && is_array($matchedItem->sizes) && count($matchedItem->sizes) > 0) {
                            if ($rawSize !== '') {
                                $normSize = strtolower($rawSize);
                                foreach ($matchedItem->sizes as $s) {
                                    $sName = strtolower(trim($s['size'] ?? ''));
                                    if ($sName === $normSize || str_starts_with($sName, $normSize) || str_starts_with($normSize, $sName)) {
                                        $unitPrice = (float) ($s['price'] ?? 0);
                                        $it['size'] = $s['size'] ?? $rawSize;
                                        break;
                                    }
                                }
                            }
                            if ($unitPrice === 0.0 && ((float) $matchedItem->price) <= 0 && isset($matchedItem->sizes[0]['price'])) {
                                $unitPrice = (float) $matchedItem->sizes[0]['price'];
                                $it['size'] = $matchedItem->sizes[0]['size'] ?? $rawSize;
                            }
                        }
                        if ($unitPrice === 0.0) {
                            $unitPrice = (float) $matchedItem->price;
                        }
                        $it['menu_item_id'] = $matchedItem->id;
                        $it['name']         = $matchedItem->name;
                    } else {
                        $matchedDeal = $dbDeals->first(function ($deal) use ($normName) {
                            $dealTitle = $deal->title ?? $deal->name ?? '';
                            $normDeal  = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $dealTitle));
                            $normDeal  = trim(preg_replace('/\s+/', ' ', $normDeal));
                            return $normDeal === $normName || stripos($normDeal, $normName) !== false || stripos($normName, $normDeal) !== false;
                        });
                        if ($matchedDeal) {
                            $unitPrice = (float) ($matchedDeal->discount_value ?? 0);
                            $it['name'] = $matchedDeal->title;
                        } else {
                            $unitPrice = (float) ($it['unit_price'] ?? 0);
                        }
                    }

                    $lineTotal = $unitPrice * $qty;
                    $it['unit_price'] = $unitPrice;
                    $it['subtotal']   = $lineTotal;
                    $calcSubtotal    += $lineTotal;
                }
                unset($it);

                if ($calcSubtotal > 0) {
                    $subtotal = $calcSubtotal;
                }
            }

            // Backend is the only authority for totals: subtotal + delivery = total
            $total = $subtotal + $deliveryCharge;

            // ── Create order (no tracking code yet — need ID first) ──
            $order = Order::create([
                ...$validated,
                'subtotal'        => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'total'           => $total,
                'tracking_code'   => 'TEMP', // placeholder
                'status'          => $validated['status'] ?? 'pending',
                'payment_method'  => $validated['payment_method'] ?? 'cash_on_delivery',
            ]);

            // Save order items if passed
            if (is_array($itemsData) && count($itemsData) > 0) {
                foreach ($itemsData as $itemRow) {
                    $order->items()->create([
                        'menu_item_id' => $itemRow['menu_item_id'] ?? null,
                        'name'         => $itemRow['name'] ?? 'Item',
                        'size'         => $itemRow['size'] ?? null,
                        'quantity'     => max(1, (int) ($itemRow['quantity'] ?? 1)),
                        'unit_price'   => (float) ($itemRow['unit_price'] ?? 0),
                        'subtotal'     => (float) ($itemRow['subtotal'] ?? 0),
                    ]);
                }
            }

            // ── Generate tracking code using order ID ──
            $trackingCode = Order::generateTrackingCode($restaurant, $order->id);
            $order->update(['tracking_code' => $trackingCode]);

            // ── Notify owner on WhatsApp ──
            $this->notifyOwnerWhatsApp($order, $restaurant);

            // ── Live Google Sheet Webhook Sync ──
            // Owner-supplied URL, so it is an SSRF sink and has to be re-checked
            // at send time — the stored value may predate validation, or come from
            // the GOOGLE_SHEET_WEBHOOK env var. See App\Support\WebhookUrlValidator.
            $sheetWebhook = $restaurant->google_sheet_webhook ?: env('GOOGLE_SHEET_WEBHOOK');

            if ($sheetWebhook) {
                $rejection = WebhookUrlValidator::validate($sheetWebhook);

                if ($rejection !== null) {
                    Log::warning('Refused to push new order to unsafe Google Sheet webhook', [
                        'restaurant_id' => $restaurant->id,
                        'reason'        => $rejection,
                    ]);
                } else {
                    try {
                        Http::timeout(5)
                            // A public URL that redirects to 127.0.0.1 would
                            // otherwise walk straight past the check above.
                            ->withOptions(['allow_redirects' => false])
                            ->post($sheetWebhook, [
                                'timestamp'        => now()->toIso8601String(),
                                'event'            => 'new_order',
                                'tracking_code'    => $trackingCode,
                                'restaurant_id'    => $restaurant->id,
                                'restaurant_name'  => $restaurant->name,
                                'customer_name'    => $order->customer_name ?: 'WhatsApp Customer',
                                'customer_phone'   => $order->customer_phone,
                                'delivery_address' => $order->delivery_address,
                                'items'            => $order->notes ?: 'Items recorded via chat',
                                'total'            => $order->total,
                                'payment_method'   => $order->payment_method,
                                'status'           => $order->status,
                                'tracking_url'     => url('/track/' . $trackingCode),
                            ]);
                        Log::info("📊 New order logged to Google Sheet for {$restaurant->name}");
                    } catch (\Exception $e) {
                        Log::warning("Google Sheet webhook error: " . $e->getMessage());
                    }
                }
            }

            Log::info("✅ Order #{$order->id} created for {$restaurant->name} — Tracking: {$trackingCode}");

            return response()->json([
                'success'       => true,
                'order_id'      => $order->id,
                'tracking_code' => $trackingCode,
                'tracking_url'  => url('/track/' . $trackingCode),
                'message'       => 'Order placed successfully!',
                'order'         => $order,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order create error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── Track Order by Tracking Code ────────────────────────────────────────
    // Customer sends tracking code to bot → bot calls this endpoint
    public function track($trackingCode)
    {
        $order = Order::where('tracking_code', strtoupper($trackingCode))
            ->with(['items', 'restaurant'])
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found. Please check your tracking code.'], 404);
        }

        return response()->json([
            'tracking_code'  => $order->tracking_code,
            'status'         => $order->status,
            'status_label'   => $order->status_label,
            'status_message' => $order->status_message,
            'rider_name'     => $order->rider_name,
            'rider_phone'    => $order->rider_phone,
            'tracking_url'   => url('/track/' . $order->tracking_code),
            'items'          => $order->items,
            'subtotal'       => $order->subtotal,
            'delivery_charge'=> $order->delivery_charge,
            'total'          => $order->total,
            'payment_method' => $order->payment_method,
            'estimated_mins' => $order->estimated_minutes,
            'placed_at'      => $order->created_at->format('d M Y, h:i A'),
            'last_updated'   => $order->updated_at->format('d M Y, h:i A'),
        ]);
    }

    // ─── Get Orders by Customer Phone ─────────────────────────────────────────
    public function getByPhone($phone)
    {
        $orders = Order::where('customer_phone', $phone)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'phone'        => $phone,
            'total_orders' => $orders->count(),
            'orders'       => $orders,
        ]);
    }

    // ─── Get All Orders for Restaurant (Dashboard) ────────────────────────────
    public function getRestaurantOrders($restaurantId)
    {
        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'restaurant_id' => $restaurantId,
            'active_orders' => $orders->count(),
            'orders'        => $orders,
        ]);
    }

    // ─── Update Order Status (Owner Dashboard) ────────────────────────────────
    public function updateStatus(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,out_for_delivery,delivered,cancelled',
        ]);

        $oldStatus = $order->status;
        $order->update($validated);

        // Notify customer via WhatsApp about status change
        $this->notifyCustomerWhatsApp($order);

        Log::info("Order #{$order->id} status: {$oldStatus} → {$order->status}");

        return response()->json([
            'success' => true,
            'order'   => $order,
            'message' => "Status updated to {$order->status}",
        ]);
    }

    // ─── Notify Owner on WhatsApp ─────────────────────────────────────────────
    // Sends message to restaurant owner's phone via the Node.js bot
    private function notifyOwnerWhatsApp(Order $order, Restaurant $restaurant)
    {
        try {
            $itemsSummary = $order->notes ?? 'Items recorded in chat';

            $message = "🔔 *New Order Alert!*\n\n"
                . "📋 *Tracking:* {$order->tracking_code}\n"
                . "📞 *Customer:* {$order->customer_phone}\n"
                . "📍 *Address:* {$order->delivery_address}\n"
                . "💳 *Payment:* {$order->payment_method}\n"
                . "💰 *Total:* Rs. {$order->total}\n"
                . "📝 *Notes:* {$itemsSummary}\n\n"
                . "👉 Login to dashboard to confirm order.";

            // Call the Node.js bot's internal API to send WhatsApp message to owner
            $sent = BotControlClient::sendMessage($restaurant->owner_phone ?: '', $message, [
                'restaurant_id' => $restaurant->id,
                'order_id'      => $order->id,
                'recipient'     => 'owner',
            ]);

            // Only claim the owner was notified if the send actually succeeded —
            // the flag drives the "unseen orders" badge.
            if ($sent) {
                $order->update(['owner_notified' => true]);
                Log::info("Owner notified for order #{$order->id}");
            }

        } catch (\Exception $e) {
            Log::warning("Could not notify owner: " . $e->getMessage());
        }
    }

    // ─── Notify Customer on WhatsApp (Status Update) ──────────────────────────
    private function notifyCustomerWhatsApp(Order $order)
    {
        try {
            $restaurant = $order->restaurant;

            $message = "📦 *Order Update*\n\n"
                . "🔖 *Tracking:* {$order->tracking_code}\n"
                . "📊 *Status:* {$order->status_label}\n\n"
                . "{$order->status_message}\n\n"
                . "Reply with your tracking code anytime to check your order status.";

            if (BotControlClient::sendMessage($order->customer_phone, $message, [
                'order_id'  => $order->id,
                'recipient' => 'customer',
            ])) {
                Log::info("Customer notified for order #{$order->id} — status: {$order->status}");
            }

        } catch (\Exception $e) {
            Log::warning("Could not notify customer: " . $e->getMessage());
        }
    }
}