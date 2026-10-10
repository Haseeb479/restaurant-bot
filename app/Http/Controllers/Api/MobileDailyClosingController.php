<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderArchiveService;
use Illuminate\Http\Request;

class MobileDailyClosingController extends Controller
{
    /**
     * Get end-of-day register closing summary.
     */
    public function summary(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $date = $request->query('date', today()->toDateString());

        $orders = $restaurant->orders()
            ->whereDate('created_at', $date)
            ->get();

        $validOrders = $orders->where('status', '!=', 'cancelled');
        $totalSales  = (float) $validOrders->sum('total');
        $totalOrders = $orders->count();
        $deliveredCount = $orders->where('status', 'delivered')->count();
        $cancelledCount = $orders->where('status', 'cancelled')->count();
        $deliveryFees   = (float) $validOrders->sum('delivery_charge');

        // Breakdown by payment methods
        $cashSales = (float) $validOrders->whereIn('payment_method', ['cash', 'cash_on_delivery'])->sum('total');
        $cardSales = (float) $validOrders->where('payment_method', 'card')->sum('total');
        $digitalSales = (float) $validOrders->whereIn('payment_method', ['jazzcash', 'easypaisa'])->sum('total');

        // Past 7 days archives
        $pastArchives = OrderArchiveService::getPreviousDaysArchives($restaurant, 7);

        return response()->json([
            'success' => true,
            'date'    => $date,
            'closing' => [
                'total_sales'    => $totalSales,
                'total_orders'   => $totalOrders,
                'delivered'      => $deliveredCount,
                'cancelled'      => $cancelledCount,
                'delivery_fees'  => $deliveryFees,
                'cash_in_hand'   => $cashSales,
                'card_payments'  => $cardSales,
                'digital_sales'  => $digitalSales,
            ],
            'archives' => $pastArchives,
        ]);
    }

    /**
     * Download or stream daily orders archive CSV for sharing.
     */
    public function downloadArchive(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $date = $request->query('date', today()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['success' => false, 'message' => 'Invalid date format.'], 400);
        }

        return OrderArchiveService::downloadDayCsv($restaurant, $date);
    }
}
