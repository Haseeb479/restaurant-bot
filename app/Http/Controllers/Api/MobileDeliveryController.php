<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Http\Request;

class MobileDeliveryController extends Controller
{
    /**
     * Get active deliveries and rider roster.
     */
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $activeDeliveries = $restaurant->orders()
            ->whereIn('status', ['confirmed', 'preparing', 'ready', 'out_for_delivery'])
            ->where('delivery_address', '!=', 'Dine-In / Counter')
            ->with(['items', 'rider'])
            ->orderBy('created_at', 'desc')
            ->get();

        $riders = $restaurant->riders()->get();

        return response()->json([
            'success'            => true,
            'active_deliveries'  => $activeDeliveries,
            'riders'             => $riders,
        ]);
    }

    /**
     * Assign a rider to an order.
     */
    public function assignRider(Request $request, $orderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $order = $restaurant->orders()->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $validated = $request->validate([
            'rider_id' => 'required|integer',
        ]);

        $rider = $restaurant->riders()->find($validated['rider_id']);

        if (!$rider) {
            return response()->json(['success' => false, 'message' => 'Rider not found or unauthorized.'], 404);
        }

        $order->rider_id = $rider->id;
        $order->rider_name = $rider->name;
        $order->rider_phone = $rider->phone;
        $order->status = ($order->status === 'pending' || $order->status === 'confirmed') ? 'preparing' : $order->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order assigned to {$rider->name}.",
            'order'   => $order->fresh('rider'),
        ]);
    }
}
