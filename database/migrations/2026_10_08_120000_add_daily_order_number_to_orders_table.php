<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('daily_order_number')->nullable()->after('restaurant_id')->index();
        });

        // Backfill daily_order_number for existing orders chronologically per restaurant per date
        $restaurants = DB::table('orders')->select('restaurant_id')->distinct()->pluck('restaurant_id');

        foreach ($restaurants as $restaurantId) {
            $orders = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'created_at']);

            $groupedByDate = [];
            foreach ($orders as $o) {
                $dateKey = substr((string)$o->created_at, 0, 10);
                $groupedByDate[$dateKey][] = $o->id;
            }

            foreach ($groupedByDate as $dateKey => $orderIds) {
                $seq = 1;
                foreach ($orderIds as $orderId) {
                    DB::table('orders')->where('id', $orderId)->update(['daily_order_number' => $seq++]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('daily_order_number');
        });
    }
};
