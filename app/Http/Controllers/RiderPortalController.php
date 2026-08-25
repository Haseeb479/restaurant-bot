<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiderPortalController extends Controller
{
    /**
     * Show mobile-optimized delivery dashboard for the assigned rider.
     */
    public function show(string $token): View
    {
        $order = Order::with(['restaurant', 'items'])
            ->where('rider_token', $token)
            ->firstOrFail();

        return view('rider.deliver', compact('order'));
    }

    /**
     * Ingest live GPS coordinates streamed by the rider's phone browser.
     */
    public function updateLocation(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'  => 'nullable|numeric|min:0',
        ]);

        $order = Order::where('rider_token', $token)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid delivery token.',
            ], 404);
        }

        // Only record GPS if order is currently out for delivery
        if ($order->status !== 'out_for_delivery') {
            return response()->json([
                'success' => false,
                'status'  => $order->status,
                'message' => 'Tracking is inactive (order is ' . $order->status . ').',
            ], 200);
        }

        $order->update([
            'rider_lat'                 => $validated['latitude'],
            'rider_lng'                 => $validated['longitude'],
            'rider_location_updated_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark the order as delivered directly from the rider's phone.
     */
    public function completeDelivery(Request $request, string $token)
    {
        $order = Order::where('rider_token', $token)->firstOrFail();

        if ($order->status !== 'delivered') {
            $order->update([
                'status'  => 'delivered',
                'is_paid' => true,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order marked as Delivered 🎉',
            ]);
        }

        return redirect()->route('rider.deliver.show', $token)->with('success', 'Order completed and marked as Delivered! 🎉');
    }
}
