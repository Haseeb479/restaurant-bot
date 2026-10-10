<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
            ->where(function ($q) {
                $q->whereNull('order_type')->orWhere('order_type', 'delivery');
            })
            ->whereNull('table_number')
            ->where('delivery_address', '!=', 'Dine-In / Counter')
            ->where('delivery_address', 'NOT LIKE', 'Table %')
            ->where('delivery_address', 'NOT LIKE', 'table %')
            ->where('customer_phone', 'NOT LIKE', 'Dine-In%')
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($o) => !$o->isDineIn())
            ->values();

        $riders = $restaurant->riders()->get();

        return response()->json([
            'success'            => true,
            'active_deliveries'  => $activeDeliveries,
            'riders'             => $riders,
        ]);
    }

    /**
     * Assign a rider to an order and dispatch.
     */
    public function assignRider(Request $request, $orderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $order = $restaurant->orders()->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->isDineIn()) {
            return response()->json([
                'success' => false,
                'message' => 'This is a Dine-In table order and cannot be assigned to a delivery rider.'
            ], 422);
        }

        $validated = $request->validate([
            'rider_id'    => 'nullable|integer',
            'rider_name'  => 'nullable|string|max:100',
            'rider_phone' => 'nullable|string|max:32',
        ]);

        if (!empty($validated['rider_id'])) {
            $rider = $restaurant->riders()->find($validated['rider_id']);
            if (!$rider) {
                return response()->json(['success' => false, 'message' => 'Rider not found or unauthorized.'], 404);
            }
            $order->rider_name  = $rider->name;
            $order->rider_phone = $rider->phone;
            if (Schema::hasColumn('orders', 'rider_id')) {
                $order->rider_id = $rider->id;
            }
        } elseif (!empty($validated['rider_name'])) {
            $order->rider_name  = trim($validated['rider_name']);
            $order->rider_phone = trim($validated['rider_phone'] ?? '');
        } else {
            return response()->json(['success' => false, 'message' => 'Please provide a rider ID or name.'], 422);
        }

        // Advance order to Out for Delivery
        $order->status = 'out_for_delivery';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order assigned to {$order->rider_name} and marked Out for Delivery.",
            'order'   => $order->fresh('items'),
        ]);
    }

    /**
     * Add a new rider to the fleet.
     */
    public function storeRider(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $validated = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:32',
        ]);

        $rider = $restaurant->riders()->create([
            'name'      => trim($validated['name']),
            'phone'     => trim($validated['phone']),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rider {$rider->name} added to fleet.",
            'rider'   => $rider,
        ], 201);
    }

    /**
     * Delete a rider from fleet.
     */
    public function deleteRider(Request $request, $riderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $rider = $restaurant->riders()->find($riderId);

        if (!$rider) {
            return response()->json(['success' => false, 'message' => 'Rider not found.'], 404);
        }

        $rider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rider removed from fleet.',
        ]);
    }
}
