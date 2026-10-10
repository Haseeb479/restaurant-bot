<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobilePosController extends Controller
{
    /**
     * Get menu catalog (categories, items, variants) for POS billing.
     */
    public function catalog(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $categories = $restaurant->categories()->with(['menuItems' => function ($q) {
            $q->where('is_available', true)->with('variants');
        }])->get();

        // Also fetch items without category
        $uncategorized = $restaurant->menuItems()
            ->whereNull('category_id')
            ->where('is_available', true)
            ->with('variants')
            ->get();

        return response()->json([
            'success'       => true,
            'categories'    => $categories,
            'uncategorized' => $uncategorized,
            'settings'      => [
                'delivery_charge' => (float) $restaurant->delivery_charge,
                'minimum_order'   => (float) $restaurant->minimum_order,
            ],
        ]);
    }

    /**
     * Get Today's POS Register Summary and recent bills for the owner.
     */
    public function summary(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $today = Carbon::today();

        $todayOrders = $restaurant->orders()
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'cancelled')
            ->get();

        $todayBillsCount = $todayOrders->count();
        $todaySales = (float) $todayOrders->sum('total');

        $cashSales = (float) $todayOrders->filter(function ($o) {
            return in_array($o->payment_method, ['cash', 'cash_on_delivery']);
        })->sum('total');

        $cardOnlineSales = (float) $todayOrders->filter(function ($o) {
            return in_array($o->payment_method, ['card', 'online', 'jazzcash', 'easypaisa']);
        })->sum('total');

        // Recent 15 bills for receipt re-print, review, or void
        $recentBills = $restaurant->orders()
            ->whereDate('created_at', $today)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get()
            ->map(function ($o) {
                return [
                    'id'                 => $o->id,
                    'daily_order_number' => $o->daily_order_number ?: $o->id,
                    'customer_name'      => $o->customer_name,
                    'customer_phone'     => $o->customer_phone,
                    'delivery_address'   => $o->delivery_address,
                    'delivery_type'      => $o->delivery_address === 'Dine-In / Counter' || str_contains($o->delivery_address, 'Table') ? 'dine_in' : 'takeaway',
                    'status'             => $o->status,
                    'payment_method'     => $o->payment_method,
                    'subtotal'           => (float) $o->subtotal,
                    'delivery_charge'    => (float) $o->delivery_charge,
                    'total'              => (float) $o->total,
                    'items_summary'      => $o->items->map(fn($it) => "{$it->quantity}x {$it->name}")->implode(', '),
                    'items'              => $o->items,
                    'created_at_time'    => $o->created_at->format('h:i A'),
                    'tracking_code'      => $o->tracking_code,
                ];
            });

        return response()->json([
            'success'            => true,
            'today_date'         => $today->format('M d, Y'),
            'today_bills_count'  => $todayBillsCount,
            'today_sales'        => $todaySales,
            'cash_sales'         => $cashSales,
            'card_online_sales'  => $cardOnlineSales,
            'recent_bills'       => $recentBills,
        ]);
    }

    /**
     * Authoritative counter billing from Mobile POS.
     * Owner-friendly: Supports catalog items, custom charges, discounts, cash tender & change.
     */
    public function createOrder(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $validated = $request->validate([
            'customer_name'    => 'nullable|string|max:150',
            'customer_phone'   => 'nullable|string|max:30',
            'delivery_type'    => 'required|in:delivery,takeaway,dine_in',
            'delivery_address' => 'nullable|string|max:255',
            'table_number'     => 'nullable|string|max:50',
            'payment_method'   => 'required|string',
            'discount_amount'  => 'nullable|numeric|min:0',
            'cash_tendered'    => 'nullable|numeric|min:0',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'nullable|integer',
            'items.*.name'     => 'nullable|string',
            'items.*.price'    => 'nullable|numeric|min:0',
            'items.*.variant_id'=> 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'notes'            => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($restaurant, $validated) {
            $subtotal = 0.0;
            $orderItemsToInsert = [];

            foreach ($validated['items'] as $reqItem) {
                // If standard menu item
                if (!empty($reqItem['item_id'])) {
                    $menuItem = $restaurant->menuItems()->find($reqItem['item_id']);

                    if (!$menuItem || !$menuItem->is_available) {
                        abort(422, 'One or more selected menu items are unavailable.');
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
                    $itemTotal = $itemPrice * $qty;
                    $subtotal += $itemTotal;

                    $orderItemsToInsert[] = [
                        'menu_item_id' => $menuItem->id,
                        'name'         => $menuItem->name,
                        'size'         => $variantName,
                        'unit_price'   => $itemPrice,
                        'quantity'     => $qty,
                        'subtotal'     => $itemTotal,
                    ];
                } else {
                    // Custom amount / custom item punched by owner
                    $customName = !empty($reqItem['name']) ? trim($reqItem['name']) : 'Custom Charge';
                    $customPrice = (float) ($reqItem['price'] ?? 0);
                    $qty = (int) $reqItem['quantity'];
                    $itemTotal = $customPrice * $qty;
                    $subtotal += $itemTotal;

                    $orderItemsToInsert[] = [
                        'menu_item_id' => null,
                        'name'         => $customName,
                        'size'         => null,
                        'unit_price'   => $customPrice,
                        'quantity'     => $qty,
                        'subtotal'     => $itemTotal,
                    ];
                }
            }

            $discount = (float) ($validated['discount_amount'] ?? 0);
            $deliveryCharge = ($validated['delivery_type'] === 'delivery')
                ? (float) ($restaurant->delivery_charge ?? 0)
                : 0.0;

            $total = max(0.0, ($subtotal - $discount) + $deliveryCharge);

            // Calculate change if cash tendered
            $cashTendered = (float) ($validated['cash_tendered'] ?? 0);
            $changeDue = ($cashTendered > $total) ? round($cashTendered - $total, 2) : 0.0;

            $deliveryAddress = $validated['delivery_address'];
            if (!$deliveryAddress) {
                if ($validated['delivery_type'] === 'dine_in') {
                    $table = $validated['table_number'] ? " (Table {$validated['table_number']})" : '';
                    $deliveryAddress = "Dine-In{$table}";
                } elseif ($validated['delivery_type'] === 'takeaway') {
                    $deliveryAddress = 'Takeaway Counter';
                } else {
                    $deliveryAddress = 'Delivery Order';
                }
            }

            $trackingCode = strtoupper(Str::random(8));
            $customerPhone = !empty($validated['customer_phone']) ? trim($validated['customer_phone']) : 'Walk-in';

            $notes = $validated['notes'] ?? '';
            if ($discount > 0) {
                $notes = trim("Discount: Rs. {$discount}. " . $notes);
            }
            if (!empty($validated['table_number'])) {
                $notes = trim("Table: {$validated['table_number']}. " . $notes);
            }

            $payMethod = match(strtolower($validated['payment_method'] ?? '')) {
                'jazzcash' => 'jazzcash',
                'easypaisa' => 'easypaisa',
                default => 'cash_on_delivery',
            };

            $order = Order::create([
                'restaurant_id'    => $restaurant->id,
                'order_type'       => $validated['delivery_type'] ?? 'takeaway',
                'table_number'     => !empty($validated['table_number']) ? trim((string)$validated['table_number']) : null,
                'customer_name'    => $validated['customer_name'] ?: 'Counter Guest',
                'customer_phone'   => $customerPhone,
                'delivery_address' => $deliveryAddress,
                'tracking_code'    => $trackingCode,
                'status'           => 'confirmed', // POS orders start confirmed
                'payment_method'   => $payMethod,
                'is_paid'          => true, // Counter bills are collected/paid upon punch
                'subtotal'         => $subtotal,
                'delivery_charge'  => $deliveryCharge,
                'total'            => $total,
                'notes'            => $notes ?: null,
            ]);

            foreach ($orderItemsToInsert as $data) {
                $order->items()->create($data);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Bill punched successfully.',
                'order'      => $order->fresh('items'),
                'change_due' => $changeDue,
            ], 201);
        });
    }

    /**
     * Void / Cancel a mistakenly punched bill.
     */
    public function voidOrder(Request $request, $orderId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $order = $restaurant->orders()->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
        }

        $order->status = 'cancelled';
        $order->notes = trim('VOIDED BY OWNER. ' . ($order->notes ?? ''));
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Bill #{$order->daily_order_number} marked as VOID.",
            'order'   => $order,
        ]);
    }
}
