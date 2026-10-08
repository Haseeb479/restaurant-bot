<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\OrderingStateEngine;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WhatsAppDealOrderingAndMenuDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Pizza Bite',
            'whatsapp_number' => '923001234567',
            'owner_phone'     => '923009876543',
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

    public function test_menu_renders_deal_contents_prominently(): void
    {
        $restaurant = $this->createRestaurant();

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Deals',
            'sort_order'    => 1,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Deal 01',
            'price'         => 450,
            'description'   => '1 Zinger Burger; 1 Reg Drink',
            'is_available'  => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Deal 02',
            'price'         => 850,
            'description'   => '2 Zinger Burgers; 2 Reg Drinks; 1 Fries',
            'is_available'  => true,
        ]);

        $engine = new OrderingStateEngine($restaurant, '923001112233');
        $menuText = $engine->renderMenuText();

        $this->assertStringContainsString('🔥 *DEALS*', $menuText);
        $this->assertStringContainsString('Deal 01', $menuText);
        $this->assertStringContainsString('👉 _1 Zinger Burger + 1 Reg Drink_', $menuText);
        $this->assertStringContainsString('Deal 02', $menuText);
        $this->assertStringContainsString('👉 _2 Zinger Burgers + 2 Reg Drinks + 1 Fries_', $menuText);
    }

    public function test_customer_can_order_deal_1_without_refusal(): void
    {
        $restaurant = $this->createRestaurant();

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Deals',
            'sort_order'    => 1,
        ]);

        $deal1 = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Deal 01',
            'price'         => 450,
            'description'   => '1 Zinger Burger; 1 Reg Drink',
            'is_available'  => true,
        ]);

        $deal2 = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Deal 02',
            'price'         => 850,
            'description'   => '2 Zinger Burgers; 2 Reg Drinks',
            'is_available'  => true,
        ]);

        $botService = new WhatsAppAiBotService();

        // 1. Check fallback NLU extracts "deal 1"
        $nlu = $botService->extractNluFallback('deal 1');
        $this->assertEquals('ADD_ITEM', $nlu['intent']);
        $this->assertNotEmpty($nlu['items']);
        $this->assertEquals('deal 1', $nlu['items'][0]['name']);
        $this->assertEquals(1, $nlu['items'][0]['quantity']);

        // 2. Process through state engine
        $engine = new OrderingStateEngine($restaurant, '923001112233');
        $reply = $engine->process($nlu);

        $this->assertStringNotContainsString('Main samajh nahi saka', $reply);
        $this->assertStringContainsString('Deal 01', $reply);
        $this->assertStringContainsString('1 Zinger Burger + 1 Reg Drink', $reply);
        $this->assertStringContainsString('Cart', $reply);
    }

    public function test_deal_order_with_quantity_and_different_numbers(): void
    {
        $restaurant = $this->createRestaurant();

        $cat = Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Deals',
            'sort_order'    => 1,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $cat->id,
            'name'          => 'Deal 01',
            'price'         => 450,
            'description'   => '1 Zinger Burger; 1 Reg Drink',
            'is_available'  => true,
        ]);

        $deal5 = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $cat->id,
            'name'          => 'Deal 05',
            'price'         => 1450,
            'description'   => '1 Large Pizza; 1 Garlic Bread; 1.5L Drink',
            'is_available'  => true,
        ]);

        $botService = new WhatsAppAiBotService();

        // Customer orders "2 deal 5"
        $nlu = $botService->extractNluFallback('2 deal 5');
        $this->assertEquals('ADD_ITEM', $nlu['intent']);
        $this->assertEquals(2, $nlu['items'][0]['quantity']);
        $this->assertEquals('deal 5', $nlu['items'][0]['name']);

        $engine = new OrderingStateEngine($restaurant, '923001112233');
        $reply = $engine->process($nlu);

        $this->assertStringNotContainsString('Main samajh nahi saka', $reply);
        $this->assertStringContainsString('Deal 05', $reply);
        $this->assertStringNotContainsString('Deal 01', $reply);
        $this->assertStringContainsString('1 Large Pizza + 1 Garlic Bread + 1.5L Drink', $reply);
    }

    public function test_customer_can_ask_what_is_in_deal_and_confirm_to_order(): void
    {
        $restaurant = $this->createRestaurant();

        $cat = Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Deals',
            'sort_order'    => 1,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $cat->id,
            'name'          => 'Deal 01',
            'price'         => 450,
            'description'   => '1 Zinger Burger; 1 Reg Drink',
            'is_available'  => true,
        ]);

        $botService = new WhatsAppAiBotService();

        // Customer asks "deal 1 mein kya hai"
        $nlu = $botService->extractNluFallback('deal 1 mein kya hai');
        $this->assertEquals('ASK_DEAL_DETAILS', $nlu['intent']);
        $this->assertEquals('deal 1', $nlu['deal_name']);

        $engine = new OrderingStateEngine($restaurant, '923001112233');
        $reply = $engine->process($nlu);

        $this->assertStringContainsString('Deal 01', $reply);
        $this->assertStringContainsString('1 Zinger Burger + 1 Reg Drink', $reply);
        $this->assertStringContainsString('Rs. 450', $reply);
        $this->assertStringContainsString('Haan', $reply);

        // Customer replies "haan"
        $confirmNlu = $botService->extractNluFallback('haan');
        $confirmReply = $engine->process($confirmNlu);

        $this->assertStringContainsString('Deal 01', $confirmReply);
        $this->assertStringContainsString('Added to Cart', $confirmReply);
    }
}
