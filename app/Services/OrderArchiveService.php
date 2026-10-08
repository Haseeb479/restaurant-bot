<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\CsvSanitizer;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderArchiveService
{
    /**
     * Get the absolute storage path for a day's archive CSV file.
     */
    public static function getArchiveFilePath(int $restaurantId, string $date): string
    {
        $dir = storage_path("app/private/order_archives/{$restaurantId}");
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
        return "{$dir}/orders_{$date}.csv";
    }

    /**
     * Generate or refresh the CSV order archive file for a specific date and restaurant.
     */
    public static function generateDayCsv(Restaurant $restaurant, string $date): string
    {
        $filePath = static::getArchiveFilePath($restaurant->id, $date);

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', $date)
            ->with(['items'])
            ->orderBy('daily_order_number', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $fp = fopen($filePath, 'w');
        if (!$fp) {
            return $filePath;
        }

        // Header row
        fputcsv($fp, [
            'Daily Order #',
            'Order ID',
            'Tracking Code',
            'Customer Name',
            'Customer Phone',
            'Delivery Address',
            'Items Summary',
            'Payment Method',
            'Subtotal (PKR)',
            'Delivery Fee (PKR)',
            'Total (PKR)',
            'Status',
            'Rider Assigned',
            'Order Time',
        ]);

        foreach ($orders as $o) {
            $itemsSummary = $o->items->map(function ($i) {
                $name = $i->name ?: ($i->item_name ?: 'Item');
                return "{$i->quantity}x {$name}" . ($i->size ? " ({$i->size})" : '');
            })->implode(', ');

            fputcsv($fp, CsvSanitizer::row([
                '#' . ($o->daily_order_number ?: $o->id),
                $o->id,
                $o->tracking_code ?: 'N/A',
                $o->customer_name ?: 'Guest',
                $o->customer_phone ?: 'N/A',
                $o->delivery_address ?: 'N/A',
                $itemsSummary ?: 'Standard Order',
                ucwords(str_replace('_', ' ', $o->payment_method ?: 'cash_on_delivery')),
                number_format((float)$o->subtotal, 2, '.', ''),
                number_format((float)$o->delivery_charge, 2, '.', ''),
                number_format((float)$o->total, 2, '.', ''),
                ucfirst($o->status),
                $o->rider_name ?: 'Unassigned',
                $o->created_at ? $o->created_at->format('g:i A') : '',
            ]));
        }

        fclose($fp);
        return $filePath;
    }

    /**
     * Returns a binary response downloading the archived CSV file.
     */
    public static function downloadDayCsv(Restaurant $restaurant, string $date): BinaryFileResponse
    {
        $filePath = static::getArchiveFilePath($restaurant->id, $date);

        // Always ensure file is up to date with latest order records
        static::generateDayCsv($restaurant, $date);

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $restaurant->name ?: 'restaurant');
        $downloadFilename = "{$safeName}_orders_{$date}.csv";

        return response()->download($filePath, $downloadFilename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$downloadFilename}\"",
        ]);
    }

    /**
     * Retrieve list of past days with order count, total revenue, and archive file metadata.
     */
    public static function getPreviousDaysArchives(Restaurant $restaurant, int $limit = 30): array
    {
        $driver = \DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : "DATE(created_at)";

        $days = Order::where('restaurant_id', $restaurant->id)
            ->selectRaw("{$dateExpr} as order_date, COUNT(*) as total_orders, SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) as total_sales, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count")
            ->groupBy('order_date')
            ->orderBy('order_date', 'desc')
            ->take($limit)
            ->get();

        $results = [];
        foreach ($days as $day) {
            $date = $day->order_date;
            if (!$date) continue;

            $filePath = static::getArchiveFilePath($restaurant->id, $date);
            $fileExists = File::exists($filePath);
            $fileSize = $fileExists ? File::size($filePath) : 0;

            $results[] = [
                'date'            => $date,
                'total_orders'    => (int) $day->total_orders,
                'delivered_count' => (int) $day->delivered_count,
                'total_sales'     => (float) $day->total_sales,
                'file_exists'     => $fileExists,
                'file_size_human' => $fileExists ? static::formatBytes($fileSize) : 'Ready to export',
            ];
        }

        return $results;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
