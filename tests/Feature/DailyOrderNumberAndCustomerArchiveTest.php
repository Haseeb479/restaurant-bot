<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\OrderArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DailyOrderNumberAndCustomerArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name = 'Daily Grill'): Restaurant
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

    public function test_order_numbers_reset_daily_per_restaurant(): void
    {
        $r = $this->createRestaurant('Daily Cafe');

        // Order 1 on Day 1 (yesterday)
        $yesterday = now()->subDay();
        $order1 = new Order([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Zaid',
            'customer_phone'   => '923001111111',
            'delivery_address' => 'House 1, Street 1',
            'tracking_code'    => 'DC-YEST-01',
            'total'            => 1000,
            'status'           => 'delivered',
            'payment_method'   => 'cash_on_delivery',
        ]);
        $order1->created_at = $yesterday;
        $order1->save();

        // Order 2 on Day 1 (yesterday)
        $order2 = new Order([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Hamza',
            'customer_phone'   => '923002222222',
            'delivery_address' => 'House 2, Street 2',
            'tracking_code'    => 'DC-YEST-02',
            'total'            => 1500,
            'status'           => 'delivered',
            'payment_method'   => 'cash_on_delivery',
        ]);
        $order2->created_at = $yesterday;
        $order2->save();

        // Order 3 on Day 2 (today) - must start at #1!
        $orderToday1 = Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Bilal',
            'customer_phone'   => '923003333333',
            'delivery_address' => 'House 3, Street 3',
            'tracking_code'    => 'DC-TODAY-01',
            'total'            => 800,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
        ]);

        // Order 4 on Day 2 (today) - must be #2!
        $orderToday2 = Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Usman',
            'customer_phone'   => '923004444444',
            'delivery_address' => 'House 4, Street 4',
            'tracking_code'    => 'DC-TODAY-02',
            'total'            => 1200,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $this->assertEquals(1, $order1->fresh()->daily_order_number);
        $this->assertEquals(2, $order2->fresh()->daily_order_number);
        $this->assertEquals(1, $orderToday1->fresh()->daily_order_number, 'Today first order must reset to 1');
        $this->assertEquals(2, $orderToday2->fresh()->daily_order_number, 'Today second order must be 2');
    }

    public function test_customer_information_is_automatically_saved_and_updated_for_deals(): void
    {
        $r = $this->createRestaurant('Customer Hub');

        // Customer places first order
        $order = Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Farhan Tariq',
            'customer_phone'   => '923007778899',
            'delivery_address' => 'Phase 5 DHA, Lahore',
            'tracking_code'    => 'CH-001',
            'total'            => 2500,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $customer = Customer::where('restaurant_id', $r->id)
            ->where('phone', '923007778899')
            ->first();

        $this->assertNotNull($customer, 'Customer record must be automatically created');
        $this->assertEquals('Farhan Tariq', $customer->name);
        $this->assertEquals('Phase 5 DHA, Lahore', $customer->address);
        $this->assertEquals(1, $customer->total_orders);
        $this->assertEquals(2500, (float)$customer->total_spent);
        $this->assertEquals('New', $customer->tag);

        // Customer places second order
        Order::create([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Farhan Tariq',
            'customer_phone'   => '923007778899',
            'delivery_address' => 'Phase 5 DHA, Lahore',
            'tracking_code'    => 'CH-002',
            'total'            => 3000,
            'status'           => 'delivered',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $customer->refresh();
        $this->assertEquals(2, $customer->total_orders);
        $this->assertEquals(5500, (float)$customer->total_spent);
        $this->assertEquals('Frequent', $customer->tag, 'Customer tag must advance to Frequent');
    }

    public function test_previous_day_orders_are_saved_in_archive_file_and_downloadable(): void
    {
        $r = $this->createRestaurant('Archive Bistro');
        $date = now()->subDays(2)->toDateString();

        $order = new Order([
            'restaurant_id'    => $r->id,
            'customer_name'    => 'Ayesha',
            'customer_phone'   => '923009988776',
            'delivery_address' => 'Gulberg III, Lahore',
            'tracking_code'    => 'ARCH-99',
            'total'            => 1850,
            'status'           => 'delivered',
            'payment_method'   => 'cash_on_delivery',
        ]);
        $order->created_at = now()->subDays(2);
        $order->save();

        OrderItem::create([
            'order_id'   => $order->id,
            'name'       => 'Chicken Karahi',
            'item_name'  => 'Chicken Karahi',
            'quantity'   => 1,
            'unit_price' => 1850,
            'subtotal'   => 1850,
        ]);

        // Generate day CSV archive
        $filePath = OrderArchiveService::generateDayCsv($r, $date);
        $this->assertTrue(File::exists($filePath), 'Daily CSV archive file must exist');

        $content = File::get($filePath);
        $this->assertStringContainsString('Chicken Karahi', $content);
        $this->assertStringContainsString('Ayesha', $content);
        $this->assertStringContainsString('#1', $content);

        // Test downloading through authenticated controller route
        $this->withSession(["restaurant_{$r->id}" => true]);
        $response = $this->get(route('dashboard.download-daily-archive', [$r->id, 'date' => $date]));
        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }
}
