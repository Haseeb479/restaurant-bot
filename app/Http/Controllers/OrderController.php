<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
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

            // ── Create order (no tracking code yet — need ID first) ──
            $order = Order::create([
                ...$validated,
                'tracking_code'  => 'TEMP', // placeholder
                'status'         => $validated['status'] ?? 'pending',
                'payment_method' => $validated['payment_method'] ?? 'cash_on_delivery',
            ]);

            // ── Generate tracking code using order ID ──
            $trackingCode = Order::generateTrackingCode($restaurant, $order->id);
            $order->update(['tracking_code' => $trackingCode]);

            // ── Notify owner on WhatsApp ──
            $this->notifyOwnerWhatsApp($order, $restaurant);

            Log::info("✅ Order #{$order->id} created for {$restaurant->name} — Tracking: {$trackingCode}");

            return response()->json([
                'success'       => true,
                'order_id'      => $order->id,
                'tracking_code' => $trackingCode,
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
            ->with('items')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found. Please check your tracking code.'], 404);
        }

        return response()->json([
            'tracking_code'  => $order->tracking_code,
            'status'         => $order->status,
            'status_label'   => $order->status_label,
            'status_message' => $order->status_message,
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
            Http::timeout(5)->post(config('app.bot_internal_api', 'http://localhost:3000') . '/send-message', [
                'to'      => $restaurant->owner_phone,
                'message' => $message,
            ]);

            $order->update(['owner_notified' => true]);
            Log::info("Owner notified for order #{$order->id}");

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

            Http::timeout(5)->post(config('app.bot_internal_api', 'http://localhost:3000') . '/send-message', [
                'to'      => $order->customer_phone,
                'message' => $message,
            ]);

            Log::info("Customer notified for order #{$order->id} — status: {$order->status}");

        } catch (\Exception $e) {
            Log::warning("Could not notify customer: " . $e->getMessage());
        }
    }
}