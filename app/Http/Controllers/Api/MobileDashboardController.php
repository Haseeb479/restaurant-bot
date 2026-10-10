<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    /**
     * Mobile Home / Command Center live data and metrics.
     */
    public function commandCenter(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $todayOrders = $restaurant->todayOrders()->with('items')->get();
        $liveOrders  = $todayOrders->whereIn('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery']);
        $riders      = $restaurant->riders()->get();
        $menuItems   = $restaurant->menuItems()->get();

        $todayRevenue      = (float) $todayOrders->where('status', '!=', 'cancelled')->sum('total');
        $totalOrdersToday  = $todayOrders->count();
        $deliveredCount    = $todayOrders->where('status', 'delivered')->count();
        $averageOrderValue = $totalOrdersToday > 0 ? round($todayRevenue / $totalOrdersToday) : 0;

        $unavailableItems    = $menuItems->where('is_available', false);
        $waitingRidersOrders = $todayOrders->whereIn('status', ['confirmed', 'preparing'])->whereNull('rider_name');

        // Top selling items today
        $topSelling = OrderItem::whereHas('order', fn($q) => $q->where('restaurant_id', $restaurant->id)->whereDate('created_at', today()))
            ->selectRaw('name, sum(quantity) as total_qty')
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // Recent orders
        $recentOrders = $restaurant->orders()->with('items')->orderBy('created_at', 'desc')->take(10)->get()
            ->map(function ($o) {
                return [
                    'id'               => $o->id,
                    'order_number'     => $o->daily_order_number ? "#{$o->daily_order_number}" : "#{$o->id}",
                    'customer_name'    => $o->customer_name ?? 'Guest Customer',
                    'customer_phone'   => $o->customer_phone,
                    'total'            => (float) $o->total,
                    'status'           => $o->status,
                    'payment_method'   => $o->payment_method ?? 'cash_on_delivery',
                    'created_at'       => $o->created_at->diffForHumans(),
                    'items_summary'    => $o->items->pluck('name')->implode(', '),
                ];
            });

        return response()->json([
            'success' => true,
            'restaurant' => [
                'id'         => $restaurant->id,
                'name'       => $restaurant->name,
                'is_open'    => (bool) $restaurant->is_open,
                'is_active'  => (bool) $restaurant->is_active,
                'bot_status' => $restaurant->bot_status ?? 'disconnected',
            ],
            'kpis' => [
                'today_sales'     => $todayRevenue,
                'today_orders'    => $totalOrdersToday,
                'aov'             => $averageOrderValue,
                'completed'       => $deliveredCount,
                'live_orders'     => $liveOrders->count(),
                'active_riders'   => $riders->where('is_active', true)->count(),
            ],
            'needs_attention' => [
                'pending_orders'       => $todayOrders->where('status', 'pending')->count(),
                'waiting_for_rider'    => $waitingRidersOrders->count(),
                'unavailable_items'    => $unavailableItems->count(),
                'bot_disconnected'     => ($restaurant->bot_status ?? 'disconnected') !== 'connected',
            ],
            'top_selling'   => $topSelling,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Toggle restaurant open/closed status.
     */
    public function toggleOpen(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $restaurant->is_open = !$restaurant->is_open;
        $restaurant->save();

        return response()->json([
            'success' => true,
            'is_open' => (bool) $restaurant->is_open,
            'message' => $restaurant->is_open ? 'Restaurant is now OPEN' : 'Restaurant is now CLOSED',
        ]);
    }
}
