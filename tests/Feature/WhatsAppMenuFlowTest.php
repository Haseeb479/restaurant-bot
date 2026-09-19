<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsAppMenuFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'GrillCafe Lodhran',
            'whatsapp_number' => '923001234567',
            'owner_phone'     => '923001234567',
            'delivery_charge' => 150.00,
            'is_open'         => true,
            'city'            => 'Lodhran',
            'address'         => 'Main Bazar Lodhran',
        ]);
        $r->status                = 'active';
        $r->registration_status   = 'approved';
        $r->is_active             = true;
        $r->email_verified_at     = now();
        $r->plan                  = 'trial';
        $r->evolution_instance_id = 'rest_menuflow';
        $r->owner_password        = \Illuminate\Support\Facades\Hash::make('owner-secret-password');
        $r->save();

        return $r;
    }

    public function test_menu_flow_shows_categories_on_menu_command()
    {
        $restaurant = $this->createRestaurant();
        $cat1 = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Burgers 🍔', 'is_active' => true, 'sort_order' => 1]);
        $cat2 = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Drinks 🥤', 'is_active' => true, 'sort_order' => 2]);

        MenuItem::create(['restaurant_id' => $restaurant->id, 'category_id' => $cat1->id, 'name' => 'Zinger Burger', 'price' => 350, 'is_available' => true]);

        $botService = new WhatsAppAiBotService();
        $phone = '923001112233';
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";

        $result = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'menu', $sessionKey);

        $this->assertNotNull($result);
        $this->assertFalse($result['orderReady']);
        $this->assertStringContainsString('GrillCafe Lodhran', $result['reply']);
        $this->assertStringContainsString('Burgers 🍔', $result['reply']);
        $this->assertStringContainsString('Drinks 🥤', $result['reply']);
    }

    public function test_menu_flow_browsing_category_and_adding_to_cart()
    {
        $restaurant = $this->createRestaurant();
        $cat = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Fast Food', 'is_active' => true]);
        MenuItem::create(['restaurant_id' => $restaurant->id, 'category_id' => $cat->id, 'name' => 'Zinger Burger', 'price' => 400, 'is_available' => true]);
        MenuItem::create(['restaurant_id' => $restaurant->id, 'category_id' => $cat->id, 'name' => 'Beef Burger', 'price' => 500, 'is_available' => true]);

        $botService = new WhatsAppAiBotService();
        $phone = '923001112233';
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";

        // 1. Select category 1
        $catResult = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", '1', $sessionKey);
        $this->assertNotNull($catResult);
        $this->assertStringContainsString('Zinger Burger', $catResult['reply']);
        $this->assertStringContainsString('Rs.400', $catResult['reply']);

        // 2. Add 2x Zinger Burger (1x2)
        $addResult = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", '1x2', $sessionKey);
        $this->assertNotNull($addResult);
        $this->assertStringContainsString('Cart mein add ho gaya', $addResult['reply']);
        $this->assertStringContainsString('Subtotal:* Rs.800', $addResult['reply']);
        $this->assertStringContainsString('Total with Delivery:* Rs.950', $addResult['reply']);

        // 3. View cart
        $cartResult = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'cart', $sessionKey);
        $this->assertNotNull($cartResult);
        $this->assertStringContainsString('Aapka Shopping Cart', $cartResult['reply']);
        $this->assertStringContainsString('2x* Zinger Burger — Rs.800', $cartResult['reply']);
    }

    public function test_menu_flow_complete_checkout_saves_order_and_sets_cod_default()
    {
        $restaurant = $this->createRestaurant();
        $cat = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Fast Food', 'is_active' => true]);
        MenuItem::create(['restaurant_id' => $restaurant->id, 'category_id' => $cat->id, 'name' => 'Zinger Burger', 'price' => 400, 'is_available' => true]);

        $botService = new WhatsAppAiBotService();
        $phone = '923001112233';
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";

        // Select category and add item
        $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", '1', $sessionKey);
        $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", '1', $sessionKey);

        // Step 1: Checkout
        $step1 = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'checkout', $sessionKey);
        $this->assertStringContainsString('Step 1/3', $step1['reply']);

        // Step 2: Name
        $step2 = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'Haseeb Khan', $sessionKey);
        $this->assertStringContainsString('Step 2/3', $step2['reply']);

        // Step 3: Phone
        $step3 = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'same', $sessionKey);
        $this->assertStringContainsString('Step 3/3', $step3['reply']);

        // Step 4: Address -> Shows Order Summary
        $step4 = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'Basti Jhok Wala, Lodhran', $sessionKey);
        $this->assertStringContainsString('Order Summary', $step4['reply']);
        $this->assertStringContainsString('Cash on Delivery (COD)', $step4['reply']);
        $this->assertStringContainsString('Total: Rs.550', $step4['reply']);

        // Step 5: Affirmative confirmation -> Saves to DB
        $confirm = $botService->processMenuFlow($restaurant, $phone, "{$phone}@s.whatsapp.net", 'yes', $sessionKey);
        $this->assertNotNull($confirm);
        $this->assertTrue($confirm['orderReady']);
        $this->assertStringContainsString('Aapka order confirm ho chuka hai', $confirm['reply']);

        // Verify order saved in DB
        $order = Order::where('restaurant_id', $restaurant->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('Haseeb Khan', $order->customer_name);
        $this->assertEquals('Basti Jhok Wala, Lodhran', $order->delivery_address);
        $this->assertEquals('cash_on_delivery', $order->payment_method);
        $this->assertEquals(550.00, $order->total);
        $this->assertEquals(400.00, $order->subtotal);
        $this->assertEquals(150.00, $order->delivery_charge);
    }
}
