<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LiveOrdersAutoWipeDeliveredOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name = 'Flame Grill'): Restaurant
    {
        $r = new Restaurant([
            'name'            => $name,
            'whatsapp_number' => '92300' . random_int(1000000, 9999999),
            'owner_phone'     => '92300' . random_int(1000000, 9999999),
            'is_open'         => true,
        ]);
        $r->status              = 'active';
        $r->registration_status = 'approved';
        $r->is_active           = true;
        $r->email_verified_at   = now();
        $r->plan                = 'trial';
        $r->owner_password      = Hash::make('Secret123456!');
        $r->save();

        return $r;
    }

    public function test_live_orders_view_only_includes_active_orders_and_excludes_delivered_orders(): void
    {
        $r = $this->createRestaurant('Bistro 99');

        // Active orders: pending, preparing, out_for_delivery
        $activeOrder1 = Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Active Cust 1',
            'customer_phone'     => '923001111111',
            'delivery_address'   => 'Street 1, Lahore',
            'status'             => 'pending',
            'total'              => 1000,
            'tracking_code'      => 'ACT-001',
            'daily_order_number' => 1,
            'payment_method'     => 'cash_on_delivery',
        ]);

        $activeOrder2 = Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Active Cust 2',
            'customer_phone'     => '923002222222',
            'delivery_address'   => 'Street 2, Lahore',
            'status'             => 'out_for_delivery',
            'total'              => 1500,
            'tracking_code'      => 'ACT-002',
            'daily_order_number' => 2,
            'payment_method'     => 'cash_on_delivery',
        ]);

        // Delivered order: should NOT be in live orders stream
        $deliveredOrder = Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Delivered Cust',
            'customer_phone'     => '923003333333',
            'delivery_address'   => 'Street 3, Lahore',
            'status'             => 'delivered',
            'total'              => 2200,
            'tracking_code'      => 'DEL-999',
            'daily_order_number' => 3,
            'payment_method'     => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.live-orders', $r->id));
        $response->assertOk();

        // Verify view data: live orders only includes active orders
        $liveOrders = $response->viewData('orders');
        $this->assertCount(2, $liveOrders);
        $this->assertTrue($liveOrders->contains('id', $activeOrder1->id));
        $this->assertTrue($liveOrders->contains('id', $activeOrder2->id));
        $this->assertFalse($liveOrders->contains('id', $deliveredOrder->id));

        // In rendered HTML: live order list must show active orders and not delivered order
        $response->assertSee('ACT-001');
        $response->assertSee('ACT-002');
        $response->assertDontSee('DEL-999');
    }

    public function test_order_history_view_contains_delivered_orders_with_daily_order_number(): void
    {
        $r = $this->createRestaurant('History Kitchen');

        $deliveredOrder = Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Historical Customer',
            'customer_phone'     => '923004444444',
            'delivery_address'   => 'Plaza 10, Lahore',
            'status'             => 'delivered',
            'total'              => 1850,
            'tracking_code'      => 'HIST-555',
            'daily_order_number' => 5,
            'payment_method'     => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.history', $r->id));
        $response->assertOk();

        // History page must display the delivered order and its daily number
        $response->assertSee('HIST-555');
        $response->assertSee('#5');
        $response->assertSee('Historical Customer');
        $response->assertSee('Delivered');
    }

    public function test_updating_status_to_delivered_via_ajax_wipes_it_from_live_orders_and_moves_to_history(): void
    {
        $r = $this->createRestaurant('Live Pipeline Cafe');

        $order = Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Dispatched Customer',
            'customer_phone'     => '923007777777',
            'delivery_address'   => 'DHA Phase 5, Lahore',
            'status'             => 'out_for_delivery',
            'total'              => 2400,
            'tracking_code'      => 'WIPE-123',
            'daily_order_number' => 7,
            'payment_method'     => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        // Prior to delivery: Order is present in live orders
        $liveBefore = $this->get(route('dashboard.live-orders', $r->id));
        $liveBefore->assertOk();
        $this->assertTrue($liveBefore->viewData('orders')->contains('id', $order->id));

        // Mark order as delivered via AJAX endpoint
        $statusResponse = $this->postJson(route('dashboard.update-status', [$r->id, $order->id]), [
            'status' => 'delivered',
        ]);

        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'success' => true,
            'status'  => 'delivered',
        ]);

        // After delivery: Order is automatically wiped out from live orders
        $liveAfter = $this->get(route('dashboard.live-orders', $r->id));
        $liveAfter->assertOk();
        $this->assertFalse($liveAfter->viewData('orders')->contains('id', $order->id));
        $liveAfter->assertDontSee('WIPE-123');

        // Order is safely preserved in history
        $historyResponse = $this->get(route('dashboard.history', $r->id));
        $historyResponse->assertOk();
        $historyResponse->assertSee('WIPE-123');
        $historyResponse->assertSee('#7');
    }

    public function test_live_orders_feed_reports_accurate_active_and_delivered_counts(): void
    {
        $r = $this->createRestaurant('Feed Stats Cafe');

        // 1 pending order, 1 out_for_delivery order, 1 delivered order
        Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Feed Cust 1',
            'customer_phone'     => '923008888881',
            'delivery_address'   => 'Johar Town, Lahore',
            'status'             => 'pending',
            'total'              => 800,
            'tracking_code'      => 'FEED-001',
            'daily_order_number' => 1,
            'payment_method'     => 'cash_on_delivery',
        ]);

        Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Feed Cust 2',
            'customer_phone'     => '923008888882',
            'delivery_address'   => 'Johar Town, Lahore',
            'status'             => 'out_for_delivery',
            'total'              => 1200,
            'tracking_code'      => 'FEED-002',
            'daily_order_number' => 2,
            'payment_method'     => 'cash_on_delivery',
        ]);

        Order::create([
            'restaurant_id'      => $r->id,
            'customer_name'      => 'Feed Cust 3',
            'customer_phone'     => '923008888883',
            'delivery_address'   => 'Johar Town, Lahore',
            'status'             => 'delivered',
            'total'              => 1600,
            'tracking_code'      => 'FEED-003',
            'daily_order_number' => 3,
            'payment_method'     => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $feedResponse = $this->getJson(route('dashboard.orders.live-feed', $r->id));
        $feedResponse->assertOk();
        $feedResponse->assertJson([
            'success'         => true,
            'active_count'    => 2,
            'delivered_count' => 1,
            'status_counts'   => [
                'pending'          => 1,
                'out_for_delivery' => 1,
                'delivered'        => 1,
            ],
        ]);
    }
}
