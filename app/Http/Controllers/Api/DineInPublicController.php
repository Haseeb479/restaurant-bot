<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DineInPublicController extends Controller
{
    /**
     * Resolve default restaurant (or by ID/slug if multi-tenant).
     */
    protected function getRestaurant()
    {
        return Restaurant::where('is_active', true)->first() ?: Restaurant::first();
    }

    /**
     * Get lightweight public restaurant info for welcome screen.
     */
    public function restaurantInfo()
    {
        $restaurant = $this->getRestaurant();
        return response()->json([
            'success'    => true,
            'restaurant' => [
                'id'       => $restaurant ? $restaurant->id : null,
                'name'     => $restaurant ? $restaurant->name : 'Grill Cafe',
                'is_open'  => $restaurant ? (bool) $restaurant->is_open : true,
                'tagline'  => 'Great Food Better Moments',
            ],
        ]);
    }

    /**
     * Get public dine-in menu for customer tablet or phone.
     */
    public function menu(Request $request)
    {
        $restaurant = $this->getRestaurant();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'No active restaurant found.',
            ], 404);
        }

        $categories = $restaurant->categories()->with(['menuItems' => function ($q) {
            $q->where('is_available', true)->with('variants');
        }])->get();

        $uncategorized = $restaurant->menuItems()
            ->whereNull('category_id')
            ->where('is_available', true)
            ->with('variants')
            ->get();

        return response()->json([
            'success'       => true,
            'restaurant'    => [
                'id'           => $restaurant->id,
                'name'         => $restaurant->name,
                'is_open'      => (bool) $restaurant->is_open,
                'currency'     => 'Rs.',
                'total_tables' => max(1, (int) ($restaurant->total_tables ?: 12)),
            ],
            'categories'    => $categories,
            'uncategorized' => $uncategorized,
        ]);
    }

    /**
     * Customer places Dine-In order from table.
     */
    public function placeOrder(Request $request)
    {
        $restaurant = $this->getRestaurant();

        if (!$restaurant) {
            return response()->json(['success' => false, 'message' => 'Restaurant not found.'], 404);
        }

        $validated = $request->validate([
            'table_number'   => 'required|string|max:50',
            'customer_name'  => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
            'notes'          => 'nullable|string|max:500',
            'items'          => 'required|array|min:1',
            'items.*.item_id'=> 'required|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity'   => 'required|integer|min:1|max:50',
        ]);

        return DB::transaction(function () use ($restaurant, $validated) {
            $subtotal = 0.0;
            $orderItemsToInsert = [];

            foreach ($validated['items'] as $reqItem) {
                $menuItem = $restaurant->menuItems()->find($reqItem['item_id']);

                if (!$menuItem || !$menuItem->is_available) {
                    abort(422, 'One or more items are currently out of stock.');
                }

                $itemPrice = (float) $menuItem->price;
                $variantName = null;

                if (!empty($reqItem['variant_id'])) {
                    $variant = $menuItem->variants()->find($reqItem['variant_id']);
                    if ($variant && $variant->is_active) {
                        $itemPrice = (float) $variant->price;
                        $variantName = $variant->name;
                    }
                }

                $qty = (int) $reqItem['quantity'];
                $lineTotal = $itemPrice * $qty;
                $subtotal += $lineTotal;

                $orderItemsToInsert[] = [
                    'menu_item_id' => $menuItem->id,
                    'name'         => $menuItem->name,
                    'size'         => $variantName,
                    'unit_price'   => $itemPrice,
                    'quantity'     => $qty,
                    'subtotal'     => $lineTotal,
                ];
            }

            $tableNum = trim($validated['table_number']);
            $trackingCode = strtoupper(Str::random(8));

            $custName = !empty($validated['customer_name']) ? trim($validated['customer_name']) : "Table {$tableNum} Guest";
            $custPhone = !empty($validated['customer_phone']) ? trim($validated['customer_phone']) : "Dine-In (Table {$tableNum})";
            $notes = !empty($validated['notes']) ? trim($validated['notes']) : '';

            $order = Order::create([
                'restaurant_id'    => $restaurant->id,
                'order_type'       => 'dine_in',
                'table_number'     => $tableNum,
                'customer_name'    => $custName,
                'customer_phone'   => $custPhone,
                'delivery_address' => "Table {$tableNum}",
                'tracking_code'    => $trackingCode,
                'status'           => 'pending', // Starts in pending for owner to accept & cook
                'payment_method'   => 'cash_on_delivery',
                'is_paid'          => false,
                'subtotal'         => $subtotal,
                'delivery_charge'  => 0,
                'total'            => $subtotal,
                'notes'            => trim("DINE-IN ORDER • Table {$tableNum}. " . $notes),
            ]);

            foreach ($orderItemsToInsert as $data) {
                $order->items()->create($data);
            }

            return response()->json([
                'success' => true,
                'message' => "Order sent to kitchen for Table {$tableNum}!",
                'order'   => [
                    'id'                 => $order->id,
                    'daily_order_number' => $order->daily_order_number ?: $order->id,
                    'table_number'       => $tableNum,
                    'total'              => (float) $order->total,
                    'items_count'        => count($orderItemsToInsert),
                    'status'             => $order->status,
                    'created_at'         => $order->created_at->format('h:i A'),
                ],
            ], 201);
        });
    }
}
