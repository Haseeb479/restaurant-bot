<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Http\Request;

class MobileOrderController extends Controller
{
    /**
     * List restaurant orders with status filter & pagination.
     */
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $status     = $request->query('status');

        $query = $restaurant->orders()->with('items')->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            if ($status === 'live') {
                $query->whereIn('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery']);
            } else {
                $query->where('status', $status);
            }
        }

        $orders = $query->paginate(20);

        return response()->json([
            'success' => true,
            'orders'  => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * Get single order detail strictly scoped to authenticated restaurant.
     */
    public function show(Request $request, $orderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $order = $restaurant->orders()->with('items')->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order'   => $order,
        ]);
    }

    /**
     * Authoritative order status update and optional rider assignment.
     */
    public function updateStatus(Request $request, $orderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $order = $restaurant->orders()->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or unauthorized.',
            ], 404);
        }

        $validated = $request->validate([
            'status'   => 'required|string|in:pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled',
            'rider_id' => 'nullable|integer',
        ]);

        $order->status = $validated['status'];

        if (!empty($validated['rider_id'])) {
            $rider = $restaurant->riders()->find($validated['rider_id']);
            if ($rider) {
                $order->rider_id = $rider->id;
                $order->rider_name = $rider->name;
            }
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'order'   => $order->fresh('items'),
        ]);
    }
}
