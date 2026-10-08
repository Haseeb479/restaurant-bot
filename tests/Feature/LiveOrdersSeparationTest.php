<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LiveOrdersSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name = 'Burger Bay'): Restaurant
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

    public function test_main_dashboard_renders_live_notification_card_and_not_heavy_pipeline(): void
    {
        $r = $this->createRestaurant('Main Bistro');

        // Create a pending order
        $order = Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Ali Khan',
            'customer_phone'   => '923001234567',
            'delivery_address' => 'House 12, Street 4, Lahore',
            'status'           => 'pending',
            'total'            => 1250,
            'tracking_code'    => 'BB-101',
            'payment_method'   => 'cash_on_delivery',
        ]);

        OrderItem::create([
            'order_id'   => $order->id,
            'name'       => 'Zinger Burger',
            'item_name'  => 'Zinger Burger',
            'quantity'   => 2,
            'unit_price' => 600,
            'subtotal'   => 1200,
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.orders', $r->id));
        $response->assertOk();

        // Must contain real-time notification card & preview
        $response->assertSee('liveNotifCard');
        $response->assertSee('notifAlertHeading');
        $response->assertSee('Recent Orders');

        // Main dashboard should NOT contain the heavy 6-stage order pipeline strip or kitchen kanban
        $response->assertDontSee('pipeline-steps-strip');
    }

    public function test_dedicated_live_orders_section_renders_full_pipeline_and_workbench(): void
    {
        $r = $this->createRestaurant('Kitchen Hub');

        $order = Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Sara Ahmed',
            'customer_phone'   => '923009876543',
            'delivery_address' => 'Flat 5B, Gulberg, Lahore',
            'status'           => 'preparing',
            'total'            => 2100,
            'tracking_code'    => 'KH-202',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.live-orders', $r->id));
        $response->assertOk();

        // Must contain pipeline card, steps strip, and stages
        $response->assertSee('pipeline-card');
        $response->assertSee('pipeline-steps-strip');
        $response->assertSee('pipeline-step-box');
        $response->assertSee('live-main-grid');
        $response->assertSee('live-orders-list');
        $response->assertSee('Incoming Orders');
    }

    public function test_live_feed_endpoint_returns_json_with_status_counts(): void
    {
        $r = $this->createRestaurant('API Kitchen');

        Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Bilal',
            'customer_phone'   => '923001112233',
            'delivery_address' => 'Sector Y, DHA, Lahore',
            'status'           => 'pending',
            'total'            => 850,
            'tracking_code'    => 'API-001',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->getJson("/dashboard/{$r->id}/orders/live-feed");
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'today_count',
            'pending_count',
            'revenue',
            'active_count',
            'status_counts' => [
                'pending',
                'confirmed',
                'preparing',
                'ready',
                'out_for_delivery',
                'delivered',
            ],
            'orders',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['pending_count']);
        $this->assertEquals(1, $data['status_counts']['pending']);
    }
}
