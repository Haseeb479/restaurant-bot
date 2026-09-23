<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\OrderingStateEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppDeliveryRadiusAndCustomerDataTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected MenuItem $pizza;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Setup Restaurant in Bahawalpur (Model Town B: 29.5405, 71.6336) with 9.0 km radius
        $this->restaurant = new Restaurant([
            'name' => 'Foodio Bahawalpur Hub',
            'whatsapp_number' => '923000000001',
            'owner_phone' => '923000000002',
            'city' => 'Bahawalpur',
            'address' => 'Model Town B, Bahawalpur',
            'restaurant_lat' => 29.5405,
            'restaurant_lng' => 71.6336,
            'delivery_radius_km' => 9.0,
            'delivery_charge' => 100.0,
            'minimum_order' => 200.0,
            'is_open' => true,
        ]);
        $this->restaurant->owner_password = bcrypt('secret123');
        $this->restaurant->is_active = true;
        $this->restaurant->trial_started_at = now();
        $this->restaurant->plan_expires_at = now()->addDays(30);
        $this->restaurant->save();

        $cat = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Fast Food',
            'sort_order' => 1,
        ]);

        $this->pizza = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $cat->id,
            'name' => 'Chicken Tikka Pizza',
            'price' => 800.0,
            'is_available' => true,
        ]);
    }

    /**
     * Test 1: Address outside 9km radius (e.g. DHA Bahawalpur ~22km away) is rejected.
     */
    public function test_address_outside_radius_is_rejected(): void
    {
        $phone = '923001234567';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        // Step 1: Add item to cart
        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        // Step 2: Customer provides name
        $reply1 = $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Hamza Khan',
        ]);

        $this->assertStringContainsString('Delivery Address', $reply1);
        $this->assertEquals('Hamza Khan', $engine->getSession()['customer_name']);

        // Step 3: Customer enters "DHA bahawalpur"
        $reply2 = $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'DHA bahawalpur',
        ]);

        // Must reject with out-of-radius message and NOT proceed to confirmation review
        $this->assertStringContainsString('radius', strtolower($reply2));
        $this->assertStringNotContainsString('ORDER SUMMARY REVIEW', $reply2);
        $this->assertNull($engine->getSession()['customer_address']);
        $this->assertEquals(OrderingStateEngine::STATE_COLLECT_CUSTOMER_INFO, $engine->getState());
    }

    /**
     * Test 2: Address within 9km radius (e.g. Model Town B) is accepted.
     */
    public function test_address_within_radius_is_accepted(): void
    {
        // Fake Nominatim geocode response for Model Town B (~1 km away)
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '29.5420',
                    'lon' => '71.6350',
                    'display_name' => 'Model Town B, Bahawalpur, Punjab, Pakistan',
                ],
            ], 200),
        ]);

        $phone = '923001234568';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        // Add item
        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        // Customer name
        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Usman Ali',
        ]);

        // In-radius address
        $reply = $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'House 12, Street 3, Model Town B',
        ]);

        $this->assertEquals('House 12, Street 3, Model Town B', $engine->getSession()['customer_address']);
        $this->assertNull($engine->getSession()['delivery_lat']); // GPS coords are NOT fabricated for manual text addresses
        $this->assertStringContainsString('Location pin share karein', $reply);
        $this->assertEquals(OrderingStateEngine::STATE_WAITING_FOR_LOCATION, $engine->getState());

        // Customer skips pin sharing
        $skipReply = $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Skip',
        ]);

        $this->assertStringContainsString('ORDER SUMMARY REVIEW', $skipReply);
        $this->assertEquals(OrderingStateEngine::STATE_WAITING_FOR_CONFIRMATION, $engine->getState());
    }

    /**
     * Test 2b: Local landmarks like "model bazzar", "adda permit", "AL fareed medical store" are accepted without false coordinates.
     */
    public function test_local_landmarks_are_accepted_and_do_not_hallucinate_false_places(): void
    {
        $phone = '923001234599';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Zumaar',
        ]);

        // Customer enters local address "model bazzar"
        $reply = $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'model bazzar',
        ]);

        // Address must be accepted and NOT rejected, and coordinates must NOT be set to Model City!
        $this->assertEquals('model bazzar', $engine->getSession()['customer_address']);
        $this->assertNull($engine->getSession()['delivery_lat']);
        $this->assertNull($engine->getSession()['delivery_lng']);
        $this->assertStringNotContainsString('Model City', $reply);
        $this->assertStringNotContainsString('bahar hai', $reply);
        $this->assertStringContainsString('Location pin share karein', $reply);
    }

    /**
     * Test 3: Stale customer name and address are NOT reused on fresh conversations.
     */
    public function test_fresh_session_does_not_reuse_stale_customer_name_or_address(): void
    {
        $phone = '923001234569';

        // Old completed conversation in DB
        Conversation::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_phone' => $phone,
            'state' => OrderingStateEngine::STATE_ORDER_CREATED,
            'cart' => [],
            'customer_name' => 'Seebay Old',
            'customer_address' => 'Old Street 99',
            'metadata' => [
                'poi_name' => 'Al fareed Medical store',
                'delivery_lat' => 29.5400,
                'delivery_lng' => 71.6300,
            ],
            'last_message_at' => now()->subDay(),
        ]);

        // New engine instance for fresh interaction
        $freshEngine = new OrderingStateEngine($this->restaurant, $phone);

        // Name and address must be NULL on load!
        $this->assertNull($freshEngine->getSession()['customer_name']);
        $this->assertNull($freshEngine->getSession()['customer_address']);
        $this->assertNull($freshEngine->getSession()['poi_name']);

        // Customer adds item
        $reply = $freshEngine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 pizza chahiye',
        ]);

        // Proceeding to checkout should ask for Name fresh!
        $checkoutReply = $freshEngine->process([
            'intent' => 'CHECKOUT',
            'raw_text' => 'checkout',
        ]);

        $this->assertStringContainsString('Naam', $checkoutReply);
        $this->assertStringNotContainsString('Seebay Old', $checkoutReply);
    }

    /**
     * Test 4: WhatsApp Pin location does NOT ask for address again and clears old landmark.
     */
    public function test_location_pin_sets_address_and_does_not_ask_for_address_again(): void
    {
        $phone = '923001234570';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        // Add item
        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        // Customer already gave name
        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Bilal Tariq',
        ]);

        // Customer sends WhatsApp Pin (lat: 29.5410, lng: 71.6340 - within radius)
        $reply = $engine->handleLocationPin(29.5410, 71.6340);

        // Pin must transition directly to confirmation review!
        $this->assertStringContainsString('ORDER SUMMARY REVIEW', $reply);
        $this->assertStringNotContainsString('Barahe karam apna Delivery Address batayein', $reply);
        $this->assertStringContainsString('Bilal Tariq', $reply);
        $this->assertEquals(OrderingStateEngine::STATE_WAITING_FOR_CONFIRMATION, $engine->getState());
        $this->assertEquals(29.5410, $engine->getSession()['delivery_lat']);
        $this->assertEquals(71.6340, $engine->getSession()['delivery_lng']);
    }

    /**
     * Test 5: Location pin received BEFORE name asks ONLY for Name, NOT address.
     */
    public function test_location_pin_before_name_prompts_only_for_name(): void
    {
        $phone = '923001234571';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        // Add item
        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        // Customer sends WhatsApp pin immediately
        $reply = $engine->handleLocationPin(29.5410, 71.6340);

        // Should ask for Name only!
        $this->assertStringContainsString('Naam', $reply);
        $this->assertStringNotContainsString('Delivery Address batayein', $reply);
        $this->assertEquals(OrderingStateEngine::STATE_COLLECT_CUSTOMER_INFO, $engine->getState());
    }

    /**
     * Test 6: Typo in confirmation (e.g. "confim", "cnfrm") confirms the order and does NOT report as missing menu item.
     */
    public function test_confirmation_typo_confim_confirms_order_without_menu_error(): void
    {
        $phone = '923001234572';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Zefeey',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Jamsahid medical store , Adda permit',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'skip',
        ]);

        $this->assertEquals(OrderingStateEngine::STATE_WAITING_FOR_CONFIRMATION, $engine->getState());

        // Customer replies with typo: "confim"
        $reply = $engine->process([
            'intent' => 'CONFIRM_ORDER',
            'raw_text' => 'confim',
        ]);

        $this->assertStringContainsString('AAPKA ORDER CONFIRM HO GAYA HAI', $reply);
        $this->assertStringNotContainsString('hamare menu mein dastiyab nahi hai', $reply);
        $this->assertEquals(OrderingStateEngine::STATE_ORDER_CREATED, $engine->getState());
    }

    /**
     * Test 7: Even if NLU mistakenly parses "confim" as ADD_ITEM, the engine does not abort confirmation state.
     */
    public function test_mistaken_add_item_with_confim_at_confirmation_state_still_confirms(): void
    {
        $phone = '923001234573';
        $engine = new OrderingStateEngine($this->restaurant, $phone);

        $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'Chicken Tikka Pizza', 'quantity' => 1],
            ],
            'raw_text' => '1 Chicken Tikka Pizza',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Zefeey',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'Jamsahid medical store , Adda permit',
        ]);

        $engine->process([
            'intent' => 'UNKNOWN',
            'raw_text' => 'skip',
        ]);

        $this->assertEquals(OrderingStateEngine::STATE_WAITING_FOR_CONFIRMATION, $engine->getState());

        // Mistaken NLU payload with intent=ADD_ITEM and name="confim"
        $reply = $engine->process([
            'intent' => 'ADD_ITEM',
            'items' => [
                ['name' => 'confim', 'quantity' => 1],
            ],
            'raw_text' => 'confim',
        ]);

        // It must NOT say "confim hamare menu mein dastiyab nahi hai"!
        $this->assertStringNotContainsString('hamare menu mein dastiyab nahi hai', $reply);
        // It should confirm because "confim" is affirmative / typo of confirm!
        $this->assertStringContainsString('AAPKA ORDER CONFIRM HO GAYA HAI', $reply);
    }

    /**
     * Test 8: WhatsAppAiBotService::extractNlu correctly maps "confim", "cnfrm", "kardo", etc. to CONFIRM_ORDER.
     */
    public function test_extract_nlu_recognizes_confirmation_variations(): void
    {
        $botService = app(\App\Services\WhatsAppAiBotService::class);

        $confimNlu = $botService->extractNlu($this->restaurant, 'confim');
        $this->assertEquals('CONFIRM_ORDER', $confimNlu['intent']);

        $cnfrmNlu = $botService->extractNlu($this->restaurant, 'cnfrm');
        $this->assertEquals('CONFIRM_ORDER', $cnfrmNlu['intent']);

        $kardoNlu = $botService->extractNlu($this->restaurant, 'kardo');
        $this->assertEquals('CONFIRM_ORDER', $kardoNlu['intent']);
    }
}

