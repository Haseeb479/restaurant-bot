<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class MobileReportsController extends Controller
{
    /**
     * Aggregated sales, order volume, and performance analytics.
     */
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $range = $request->query('range', '7days'); // today, 7days, 30days

        $startDate = match ($range) {
            'today'  => today()->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default  => now()->subDays(6)->startOfDay(),
        };

        $ordersQuery = $restaurant->orders()->where('created_at', '>=', $startDate);

        $totalRevenue = (float) (clone $ordersQuery)->where('status', '!=', 'cancelled')->sum('total');
        $totalOrders  = (clone $ordersQuery)->count();
        $completed    = (clone $ordersQuery)->where('status', 'delivered')->count();
        $cancelled    = (clone $ordersQuery)->where('status', 'cancelled')->count();
        $aov          = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        // Daily breakdown
        $days = match ($range) {
            'today'  => 1,
            '30days' => 30,
            default  => 7,
        };

        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $label = now()->subDays($i)->format($days === 1 ? 'g A' : 'M d');
            $daySum = (float) $restaurant->orders()
                ->whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $chartData[] = [
                'date'   => $date,
                'label'  => $label,
                'amount' => $daySum,
            ];
        }

        // Top selling items
        $topItems = OrderItem::whereHas('order', fn($q) => $q->where('restaurant_id', $restaurant->id)->where('created_at', '>=', $startDate))
            ->selectRaw('name, sum(quantity) as total_qty, sum(subtotal) as total_revenue')
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_orders'  => $totalOrders,
                'completed'     => $completed,
                'cancelled'     => $cancelled,
                'aov'           => $aov,
            ],
            'chart_data' => $chartData,
            'top_items'  => $topItems,
        ]);
    }
}
