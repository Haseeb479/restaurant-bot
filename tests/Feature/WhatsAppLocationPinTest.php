<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Jobs\ProcessWhatsAppMessage;

class WhatsAppLocationPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.evolution.api_key' => 'test_evo_secret_key']);
    }

    private function makeRestaurant(string $instanceId): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Location Test Rest',
            'whatsapp_number' => '9230' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'delivery_charge' => 100.00,
            'is_open'         => true,
            'city'            => 'Lahore',
            'address'         => 'Mall Road',
        ]);
        $r->status                = 'active';
        $r->registration_status   = 'approved';
        $r->is_active             = true;
        $r->email_verified_at     = now();
        $r->plan                  = 'trial';
        $r->evolution_instance_id = $instanceId;
        $r->owner_password        = \Illuminate\Support\Facades\Hash::make('owner-secret-password');
        $r->save();
        return $r;
    }

    public function test_native_location_pin_is_extracted_and_queued()
    {
        Queue::fake();

        $restaurant = $this->makeRestaurant('rest_100');

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'rest_100',
            'data' => [
                'message' => [
                    'key' => [
                        'remoteJid' => '923001234567@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => '123456'
                    ],
                    'locationMessage' => [
                        'degreesLatitude' => 31.5204,
                        'degreesLongitude' => 74.3587,
                        'name' => 'Lahore Pin'
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders(['apikey' => 'test_evo_secret_key'])
            ->postJson(route('webhook.whatsapp'), $payload);
        $response->assertStatus(200);

        Queue::assertPushed(ProcessWhatsAppMessage::class, function ($job) {
            $coords = $job->locationCoords;
            return $coords !== null && $coords['lat'] === 31.5204 && $coords['lng'] === 74.3587;
        });
    }

    public function test_invalid_location_null_island()
    {
        Queue::fake();

        $restaurant = $this->makeRestaurant('rest_101');

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'rest_101',
            'data' => [
                'message' => [
                    'key' => [
                        'remoteJid' => '923001234567@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => '654321'
                    ],
                    'locationMessage' => [
                        'degreesLatitude' => 0.0,
                        'degreesLongitude' => 0.0,
                        'name' => 'Null Island'
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders(['apikey' => 'test_evo_secret_key'])
            ->postJson(route('webhook.whatsapp'), $payload);
        $response->assertStatus(200);

        // It should NOT queue a job since it's an empty text with invalid coordinates
        Queue::assertNotPushed(ProcessWhatsAppMessage::class);
    }

    public function test_customer_map_pin_update_stores_exact_coords_and_reverse_geocoded_address()
    {
        $restaurant = $this->makeRestaurant('rest_102');
        $phone = '923009988776';
        $token = 'test_token_123';

        Cache::put("loc_token_{$token}", [
            'restaurant_id'  => $restaurant->id,
            'customer_phone' => $phone,
            'recipient_jid'  => "{$phone}@s.whatsapp.net",
        ], now()->addMinutes(15));

        $response = $this->postJson(route('location.confirm.update', $token), [
            'lat'             => 29.58968,
            'lng'             => 71.60523,
            'address'         => 'Near Railway Station, Lodhran Tehsil, Lodhran District, Punjab',
            'location_source' => 'customer_pin',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";
        $coords = Cache::get("verified_delivery_coords_{$sessionKey}");
        $source = Cache::get("verified_delivery_source_{$sessionKey}");
        $addr   = Cache::get("verified_delivery_address_{$sessionKey}");
        $history = Cache::get($sessionKey);

        $this->assertEquals([29.58968, 71.60523], $coords);
        $this->assertEquals('customer_pin', $source);
        $this->assertEquals('Near Railway Station, Lodhran Tehsil, Lodhran District, Punjab', $addr);
        $this->assertNotEmpty($history);
        $this->assertStringContainsString('29.58968', json_encode($history));
    }

    public function test_saved_order_uses_customer_pin_coords_and_address_with_cod_default()
    {
        $restaurant = $this->makeRestaurant('rest_103');
        $phone = '923009988777';
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";

        $category = \App\Models\Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Fast Food',
        ]);

        \App\Models\MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Zinger Burger',
            'price'         => 450.00,
            'is_available'  => true,
        ]);

        Cache::put("verified_delivery_coords_{$sessionKey}", [29.58968, 71.60523], now()->addMinutes(45));
        Cache::put("verified_delivery_source_{$sessionKey}", 'customer_pin', now()->addMinutes(45));
        Cache::put("verified_delivery_address_{$sessionKey}", 'Near Railway Station, Lodhran', now()->addMinutes(45));

        $history = [
            ['role' => 'user', 'content' => '1x Zinger Burger'],
            ['role' => 'assistant', 'content' => "🧾 *Order Summary*\n1x Zinger Burger — Rs.450\nSubtotal: Rs.450\nDelivery: Rs.100\n*Total: Rs.550*\nName: Ali Khan\nPhone: {$phone}\nPayment: Cash on Delivery (COD) 💵\nDeliver to: Near Railway Station, Lodhran\n\nKya main aapka order confirm kar doon? ✅"],
            ['role' => 'user', 'content' => 'haan confirm kar do'],
        ];

        $botService = new WhatsAppAiBotService();
        $trackingCode = $botService->saveOrderFromHistory($restaurant, $phone, $history);

        $this->assertNotNull($trackingCode);
        $order = Order::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($order);
        $this->assertEquals(29.58968, $order->delivery_lat);
        $this->assertEquals(71.60523, $order->delivery_lng);
        $this->assertEquals('Near Railway Station, Lodhran', $order->delivery_address);
        $this->assertEquals('cash_on_delivery', $order->payment_method);
        $this->assertEquals(550.00, $order->total);
    }

    public function test_saved_order_uses_jazzcash_when_explicitly_requested()
    {
        $restaurant = $this->makeRestaurant('rest_104');
        $phone = '923009988778';
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";

        $category = \App\Models\Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Fast Food',
        ]);

        \App\Models\MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Chicken Roll',
            'price'         => 300.00,
            'is_available'  => true,
        ]);

        $history = [
            ['role' => 'user', 'content' => '1x Chicken Roll, JazzCash se pay karna hai'],
            ['role' => 'assistant', 'content' => "🧾 *Order Summary*\n1x Chicken Roll — Rs.300\nSubtotal: Rs.300\nDelivery: Rs.100\n*Total: Rs.400*\nName: Usman\nPhone: {$phone}\nPayment: JazzCash\nDeliver to: Model Town, Lahore\n\nKya main aapka order confirm kar doon? ✅"],
            ['role' => 'user', 'content' => 'yes please'],
        ];

        $botService = new WhatsAppAiBotService();
        $trackingCode = $botService->saveOrderFromHistory($restaurant, $phone, $history);

        $this->assertNotNull($trackingCode);
        $order = Order::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($order);
        $this->assertEquals('jazzcash', $order->payment_method);
    }
}
