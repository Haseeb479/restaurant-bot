<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Deal;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderPricingAuthorityTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Pizza Supreme',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'delivery_charge' => 150.00,
            'is_open'         => true,
            'city'            => 'Karachi',
            'address'         => 'Test Street 1',
        ]);
        $r->status              = 'active';
        $r->registration_status = 'approved';
        $r->is_active           = true;
        $r->email_verified_at   = now();
        $r->plan                = 'trial';
        $r->owner_password      = Hash::make('owner-secret-password');
        $r->save();

        $cat = Category::create([
            'restaurant_id' => $r->id,
            'name'          => 'Main Menu',
            'sort_order'    => 1,
        ]);

        // Standard fixed-price item
        MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Zinger Burger',
            'price'         => 550.00,
            'is_available'  => true,
        ]);

        // Sized item
        MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Fajita Pizza',
            'price'         => 0.00,
            'sizes'         => [
                ['size' => 'Small', 'price' => 600],
                ['size' => 'Large', 'price' => 1400],
            ],
            'is_available'  => true,
        ]);

        // Active deal
        Deal::create([
            'restaurant_id'  => $r->id,
            'title'          => 'Midnight Combo',
            'description'    => '2 Burgers + Fries',
            'discount_type'  => 'bundle',
            'discount_value' => 899.00,
            'is_active'      => true,
        ]);

        return $r;
    }

    public function test_whatsapp_ai_bot_service_recalculates_prices_from_db(): void
    {
        $restaurant = $this->createRestaurant();
        $botService = app(WhatsAppAiBotService::class);

        // Simulated AI order summary where hallucinated or malicious AI generated cheap prices
        $fakeSummary = "Here is your order summary:\n" .
            "• 2 x Zinger Burger — Rs. 20\n" .
            "• 1 x Fajita Pizza (Large) — Rs. 50\n" .
            "• 1 x Midnight Combo — Rs. 30\n" .
            "Subtotal: Rs. 100\n" .
            "Delivery: Rs. 0\n" .
            "Total: Rs. 100\n" .
            "Name: Ali Khan\n" .
            "Phone: 03001112233\n" .
            "Deliver to: House 123, Sector 4\n" .
            "Payment: Cash on delivery";

        $history = [
            ['role' => 'user', 'content' => 'Please confirm my order'],
            ['role' => 'assistant', 'content' => $fakeSummary],
            ['role' => 'user', 'content' => 'Yes, confirm order'],
        ];

        $trackingCode = $botService->saveOrderFromHistory($restaurant, '923001112233', $history);

        $this->assertNotNull($trackingCode);

        $order = Order::where('tracking_code', $trackingCode)->with('items')->first();
        $this->assertNotNull($order);

        // Authoritative pricing:
        // 2 x Zinger @ 550 = 1100
        // 1 x Fajita Pizza (Large) @ 1400 = 1400
        // 1 x Midnight Combo @ 899 = 899
        // Expected Subtotal = 3399
        // Expected Delivery Charge = 150 (from restaurant model, not 0 from AI)
        // Expected Total = 3399 + 150 = 3549

        $this->assertEquals(3399.00, (float) $order->subtotal);
        $this->assertEquals(150.00, (float) $order->delivery_charge);
        $this->assertEquals(3549.00, (float) $order->total);

        // Verify items
        $this->assertCount(3, $order->items);

        $zingerItem = $order->items->firstWhere('name', 'Zinger Burger');
        $this->assertNotNull($zingerItem);
        $this->assertEquals(550.00, (float) $zingerItem->unit_price);
        $this->assertEquals(1100.00, (float) $zingerItem->subtotal);

        $pizzaItem = $order->items->firstWhere('name', 'Fajita Pizza');
        $this->assertNotNull($pizzaItem);
        $this->assertEquals('Large', $pizzaItem->size);
        $this->assertEquals(1400.00, (float) $pizzaItem->unit_price);
        $this->assertEquals(1400.00, (float) $pizzaItem->subtotal);

        $dealItem = $order->items->firstWhere('name', 'Midnight Combo');
        $this->assertNotNull($dealItem);
        $this->assertEquals(899.00, (float) $dealItem->unit_price);
    }

    public function test_order_controller_create_enforces_authoritative_delivery_charge_and_total(): void
    {
        $restaurant = $this->createRestaurant();

        // Pass an order request with manipulated subtotal, delivery_charge, total, and items
        $payload = [
            'restaurant_id'   => $restaurant->id,
            'customer_phone'  => '923009998877',
            'customer_name'   => 'Tariq',
            'delivery_address'=> 'Gulshan Block 5',
            'subtotal'        => 50.00, // Client/AI claimed 50
            'delivery_charge' => 0.00,  // Client/AI claimed free delivery
            'total'           => 50.00, // Client/AI claimed 50 total
            'payment_method'  => 'cash_on_delivery',
            'items'           => [
                [
                    'name'       => 'Zinger Burger',
                    'quantity'   => 1,
                    'unit_price' => 50.00, // DB price is 550
                ],
            ],
        ];

        $request = \Illuminate\Http\Request::create('/api/orders/create', 'POST', $payload);
        $controller = app(\App\Http\Controllers\OrderController::class);
        $response = $controller->create($request);

        $this->assertEquals(201, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        $orderId = $responseData['order_id'];
        $order = Order::find($orderId);

        $this->assertNotNull($order);
        // Subtotal must be resolved from DB: 1 x 550 = 550
        $this->assertEquals(550.00, (float) $order->subtotal);
        // Delivery charge must be enforced from restaurant: 150
        $this->assertEquals(150.00, (float) $order->delivery_charge);
        // Total must strictly be subtotal + delivery_charge = 700
        $this->assertEquals(700.00, (float) $order->total);
    }

    public function test_save_order_rejects_empty_cart_or_zero_subtotal(): void
    {
        $restaurant = $this->createRestaurant();
        $botService = app(WhatsAppAiBotService::class);

        // Simulated AI summary with no items or 0 subtotal
        $emptySummary = "Here is your order summary:\n" .
            "Subtotal: Rs. 0\n" .
            "Delivery: Rs. 150\n" .
            "Total: Rs. 150\n" .
            "Name: Ali Khan\n" .
            "Phone: 03001112233\n" .
            "Deliver to: House 123, Sector 4\n" .
            "Payment: Cash on delivery";

        $history = [
            ['role' => 'user', 'content' => 'Please confirm my order'],
            ['role' => 'assistant', 'content' => $emptySummary],
            ['role' => 'user', 'content' => 'Yes, confirm'],
        ];

        $trackingCode = $botService->saveOrderFromHistory($restaurant, '923001112233', $history);

        $this->assertNull($trackingCode);
        $this->assertEquals(0, Order::count());
    }

    public function test_exact_menu_price_and_whatsapp_confirmation_harmonization(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'nominatim.openstreetmap.org/*' => \Illuminate\Support\Facades\Http::response([
                ['lat' => '28.5', 'lon' => '71.6', 'display_name' => 'Lodhran, Pakistan']
            ], 200),
            '*' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);

        $r = new Restaurant([
            'name'            => 'Fezio Restaurant',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'delivery_charge' => 250.00,
            'is_open'         => true,
            'city'            => 'Lodhran',
            'address'         => 'Model Town, Lodhran',
        ]);
        $r->status              = 'active';
        $r->registration_status = 'approved';
        $r->is_active           = true;
        $r->email_verified_at   = now();
        $r->plan                = 'trial';
        $r->owner_password      = Hash::make('owner-secret-password');
        $r->save();

        $cat = Category::create([
            'restaurant_id' => $r->id,
            'name'          => 'Tandoori & Drinks',
            'sort_order'    => 1,
        ]);

        MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Butter Naan',
            'price'         => 50.00,
            'is_available'  => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Naan',
            'price'         => 40.00,
            'is_available'  => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Fresh Lime Soda',
            'price'         => 50.00,
            'is_available'  => true,
        ]);

        $botService = app(WhatsAppAiBotService::class);

        // AI hallucinated 3x Naan @ 50 = 150, subtotal = 350, total = 600
        $hallucinatedSummary = "🧾 *Order Summary*\n" .
            "3x Naan — Rs. 150\n" .
            "4x Fresh Lime Soda — Rs. 200\n" .
            "─────────────────\n" .
            "Subtotal: Rs. 350\n" .
            "Delivery: Rs. 250\n" .
            "*Total: Rs. 600*\n" .
            "─────────────────\n" .
            "Name: Haseeb\n" .
            "Phone: 03241679919\n" .
            "Deliver to: Lodhran, near Model Bazar\n" .
            "Payment: Cash on delivery\n\n" .
            "Your order is placed! Total payable: Rs. 600";

        $history = [
            ['role' => 'user', 'content' => 'Please confirm order'],
            ['role' => 'assistant', 'content' => $hallucinatedSummary],
            ['role' => 'user', 'content' => 'Yes, confirm'],
        ];

        $trackingCode = $botService->saveOrderFromHistory($r, '923241679919', $history);
        $this->assertNotNull($trackingCode);

        $order = Order::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($order);

        // Authoritative pricing:
        // 3x Naan @ 40 = 120
        // 4x Fresh Lime Soda @ 50 = 200
        // Subtotal = 320
        // Delivery = 250
        // Grand Total = 570
        $this->assertEquals(320.00, (float) $order->subtotal, 'Authoritative subtotal must be 320, not hallucinated 350');
        $this->assertEquals(250.00, (float) $order->delivery_charge, 'Authoritative delivery charge must be 250');
        $this->assertEquals(570.00, (float) $order->total, 'Authoritative total must be 570, not hallucinated 600');

        // Test message harmonization
        $harmonizedReply = $botService->harmonizeConfirmationBill($hallucinatedSummary, $order);
        $this->assertStringContainsString('3x Naan — Rs. 120', $harmonizedReply);
        $this->assertStringContainsString('Subtotal: Rs. 320', $harmonizedReply);
        $this->assertStringContainsString('Total: Rs. 570', $harmonizedReply);
        $this->assertStringContainsString('Total payable: Rs. 570', $harmonizedReply);
        $this->assertStringNotContainsString('Rs. 600', $harmonizedReply);
    }

    public function test_butter_naan_is_preserved_with_exact_name_and_authoritative_price(): void
    {
        $r = $this->createRestaurant();
        $r->delivery_charge = 250.00;
        $r->name = 'Naan House';
        $r->save();

        $cat = \App\Models\Category::create([
            'restaurant_id' => $r->id,
            'name'          => 'Breads & Drinks',
        ]);

        $naan = MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Naan',
            'price'         => 40.00,
            'is_available'  => true,
            'sort_order'    => 1,
        ]);

        $butterNaan = MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Butter Naan',
            'price'         => 50.00,
            'is_available'  => true,
            'sort_order'    => 2,
        ]);

        $soda = MenuItem::create([
            'restaurant_id' => $r->id,
            'category_id'   => $cat->id,
            'name'          => 'Fresh Lime Soda',
            'price'         => 50.00,
            'is_available'  => true,
            'sort_order'    => 3,
        ]);

        $botService = app(WhatsAppAiBotService::class);

        $summary = "🧾 *Order Summary*\n" .
                   "3x Butter Naan — Rs. 150\n" .
                   "4x Fresh Lime Soda — Rs. 200\n" .
                   "─────────────────\n" .
                   "Subtotal: Rs. 350\n" .
                   "Delivery: Rs. 250\n" .
                   "*Total: Rs. 600*\n" .
                   "─────────────────\n" .
                   "Name: Test Customer\n" .
                   "Phone: 03001234567\n" .
                   "Payment: Cash on Delivery\n" .
                   "Deliver to: Model Town, Lodhran\n\n" .
                   "Kya main aapka order confirm kar doon? ✅";

        $history = [
            ['role' => 'assistant', 'content' => $summary],
            ['role' => 'user', 'content' => 'haan confirm kar do'],
        ];

        $trackingCode = $botService->saveOrderFromHistory($r, '923001234567', $history);
        $this->assertNotNull($trackingCode);

        $order = Order::where('tracking_code', $trackingCode)->with('items')->first();
        $this->assertNotNull($order);

        // Subtotal must be 3x50 + 4x50 = 350, total = 600
        $this->assertEquals(350.00, (float) $order->subtotal);
        $this->assertEquals(250.00, (float) $order->delivery_charge);
        $this->assertEquals(600.00, (float) $order->total);

        // Order item name must be "Butter Naan", NOT downgraded to "Naan"
        $firstItem = $order->items->first();
        $this->assertEquals('Butter Naan', $firstItem->name);
        $this->assertEquals(50.00, (float) $firstItem->unit_price);
        $this->assertEquals(150.00, (float) $firstItem->subtotal);

        // Also test OrderController::create with composite candidate input
        $payload = [
            'restaurant_id'   => $r->id,
            'customer_name'   => 'Ali Khan',
            'customer_phone'  => '03123456789',
            'delivery_address'=> 'Street 5, Karachi',
            'subtotal'        => 100.00,
            'delivery_charge' => 0.00,
            'total'           => 100.00,
            'payment_method'  => 'cash_on_delivery',
            'items'           => [
                ['name' => 'Naan', 'size' => 'Butter', 'quantity' => 3], // size passed separately
                ['name' => 'Fresh Lime Soda', 'quantity' => 4],
            ],
        ];

        $request = \Illuminate\Http\Request::create('/api/orders/create', 'POST', $payload);
        $controller = app(\App\Http\Controllers\OrderController::class);
        $response = $controller->create($request);

        $this->assertEquals(201, $response->getStatusCode());
        $ctrlOrder = Order::where('customer_phone', '03123456789')->with('items')->latest()->first();
        $this->assertNotNull($ctrlOrder);
        $this->assertEquals(350.00, (float) $ctrlOrder->subtotal);
        $this->assertEquals(250.00, (float) $ctrlOrder->delivery_charge);
        $this->assertEquals(600.00, (float) $ctrlOrder->total);

        $ctrlFirstItem = $ctrlOrder->items->first();
        $this->assertEquals('Butter Naan', $ctrlFirstItem->name);
        $this->assertEquals(50.00, (float) $ctrlFirstItem->unit_price);
        $this->assertEquals(150.00, (float) $ctrlFirstItem->subtotal);
    }
}
