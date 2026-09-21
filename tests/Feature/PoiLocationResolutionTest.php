<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\LocationResolutionService;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PoiLocationResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.evolution.api_key'  => 'test_evo_secret_key',
            'services.google.places_api_key' => 'AIzaSyFakeTestKeyPlaces2026',
        ]);
        Http::preventStrayRequests();
    }

    private function makeRestaurant(string $instanceId): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Foodio Lodhran Kitchen',
            'whatsapp_number' => '9230' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'delivery_charge' => 100.00,
            'is_open'         => true,
            'city'            => 'Lodhran',
            'address'         => 'Main Bazar Lodhran',
            'restaurant_lat'  => 29.54000,
            'restaurant_lng'  => 71.63000,
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

    /**
     * Requirement 11: Exact Test Coordinates: 29.48913, 71.64155
     * Must identify "Shahida Islam Medical & Dental College" as the nearby POI,
     * while STRICTLY preserving the customer's exact GPS coordinates (never moving the pin).
     */
    public function test_exact_coordinates_resolve_to_shahida_islam_medical_and_dental_college_and_preserve_gps()
    {
        $targetLat = 29.48913;
        $targetLng = 71.64155;

        // Simulate Google Places API (New) Nearby Search returning Shahida Islam Medical & Dental College
        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response([
                'places' => [
                    [
                        'id' => 'ChIJ_shahida_islam_college_lodhran',
                        'types' => ['university', 'school', 'hospital', 'point_of_interest', 'establishment'],
                        'formattedAddress' => 'Shahida Islam Medical & Dental College, Bahawalpur Road, Lodhran, Punjab, Pakistan',
                        'location' => [
                            // Different from customer's pin to verify pin is NEVER moved
                            'latitude'  => 29.48950,
                            'longitude' => 71.64210,
                        ],
                        'displayName' => [
                            'text'         => 'Shahida Islam Medical & Dental College',
                            'languageCode' => 'en',
                        ],
                        'primaryType' => 'university',
                    ],
                ],
            ], 200),
            'http://127.0.0.1:8080/*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $resolver = new LocationResolutionService();
        $result = $resolver->resolve($targetLat, $targetLng);

        // 1. Exact customer coordinates MUST NOT be replaced with POI coordinates
        $this->assertEquals($targetLat, $result['delivery_lat'], 'Customer latitude was altered!');
        $this->assertEquals($targetLng, $result['delivery_lng'], 'Customer longitude was altered!');

        // 2. POI fields are descriptive
        $this->assertEquals('Shahida Islam Medical & Dental College', $result['delivery_place_name']);
        $this->assertEquals('ChIJ_shahida_islam_college_lodhran', $result['delivery_place_id']);
        $this->assertEquals('google_places', $result['location_source']);
        $this->assertStringContainsString('Shahida Islam Medical & Dental College', $result['delivery_address']);

        // 3. Test web confirmation endpoint with exact coordinates
        $restaurant = $this->makeRestaurant('rest_exact_1');
        $phone = '923001112233';
        $token = 'exact_test_token_1';

        Cache::put("loc_token_{$token}", [
            'restaurant_id'  => $restaurant->id,
            'customer_phone' => $phone,
            'recipient_jid'  => "{$phone}@s.whatsapp.net",
        ], now()->addMinutes(15));

        $response = $this->postJson(route('location.confirm.update', $token), [
            'lat'     => $targetLat,
            'lng'     => $targetLng,
            'address' => '', // Customer entered no extra street/house
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'         => true,
            'lat'             => $targetLat,
            'lng'             => $targetLng,
            'place_name'      => 'Shahida Islam Medical & Dental College',
            'location_source' => 'google_places',
        ]);

        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";
        $this->assertEquals([$targetLat, $targetLng], Cache::get("verified_delivery_coords_{$sessionKey}"));
        $this->assertEquals('Shahida Islam Medical & Dental College', Cache::get("verified_delivery_place_name_{$sessionKey}"));
        $this->assertEquals('google_places', Cache::get("verified_delivery_source_{$sessionKey}"));
    }

    /**
     * Requirement 12: Generic Location Fallback
     * When there is no meaningful nearby POI, system must gracefully fall back
     * to the normal reverse-geocoded road/address (e.g. "National Highway, Lodhran").
     */
    public function test_generic_location_gracefully_falls_back_to_reverse_geocoded_address()
    {
        $genericLat = 29.45000;
        $genericLng = 71.60000;

        // Mock Google Places returning empty (no establishment within radius)
        // and Nominatim returning the generic highway / locality address
        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response([
                'places' => [],
            ], 200),
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'National Highway, Lodhran, Punjab, Pakistan',
                'address' => [
                    'road'    => 'National Highway',
                    'city'    => 'Lodhran',
                    'state'   => 'Punjab',
                    'country' => 'Pakistan',
                ],
            ], 200),
            'http://127.0.0.1:8080/*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $resolver = new LocationResolutionService();
        $result = $resolver->resolve($genericLat, $genericLng);

        // Coordinates strictly preserved
        $this->assertEquals($genericLat, $result['delivery_lat']);
        $this->assertEquals($genericLng, $result['delivery_lng']);

        // Graceful fallback: no POI, reverse-geocoded road name used
        $this->assertNull($result['delivery_place_name']);
        $this->assertStringContainsString('National Highway', $result['delivery_address']);
        $this->assertEquals('reverse_geocode', $result['location_source']);
    }

    /**
     * Requirement 13: Final Regression Test
     * Full flow:
     * Customer selects pin -> Confirm Delivery Pin -> POI/address resolved ->
     * Exact GPS saved -> Bot session continues -> Cart remains intact ->
     * Menu not restarted -> Order created with structured location fields.
     */
    public function test_regression_checkout_flow_and_order_persistence_with_poi()
    {
        $lat = 29.48913;
        $lng = 71.64155;

        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response([
                'places' => [
                    [
                        'id' => 'ChIJ_shahida_islam_college',
                        'types' => ['university', 'establishment'],
                        'formattedAddress' => 'Shahida Islam Medical & Dental College, Bahawalpur Road, Lodhran',
                        'location' => [
                            'latitude'  => 29.4899,
                            'longitude' => 71.6420,
                        ],
                        'displayName' => [
                            'text' => 'Shahida Islam Medical & Dental College',
                        ],
                    ],
                ],
            ], 200),
            'http://127.0.0.1:8080/*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $restaurant = $this->makeRestaurant('rest_reg_1');
        $phone = '923007654321';
        $token = 'reg_token_456';

        $cat = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Pizza']);
        $item = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $cat->id,
            'name'          => 'Chicken Fajita Pizza',
            'price'         => 1200.00,
            'is_available'  => true,
        ]);

        // Step 1: Pre-order token generated
        Cache::put("loc_token_{$token}", [
            'restaurant_id'  => $restaurant->id,
            'customer_phone' => $phone,
            'recipient_jid'  => "{$phone}@s.whatsapp.net",
        ], now()->addMinutes(15));

        // Step 2: Customer confirms delivery pin with optional house detail
        $confirmRes = $this->postJson(route('location.confirm.update', $token), [
            'lat'     => $lat,
            'lng'     => $lng,
            'address' => 'Hostel #2, Room 14',
        ]);

        $confirmRes->assertStatus(200);
        $confirmRes->assertJson([
            'success'    => true,
            'lat'        => $lat,
            'lng'        => $lng,
            'place_name' => 'Shahida Islam Medical & Dental College',
        ]);

        // Step 3: Customer continues session in WhatsApp and confirms order
        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";
        $history = [
            ['role' => 'user', 'content' => '1x Chicken Fajita Pizza'],
            ['role' => 'assistant', 'content' => "🧾 *Order Summary*\n1x Chicken Fajita Pizza — Rs.1200\nSubtotal: Rs.1200\nDelivery: Rs.100\n*Total: Rs.1300*\nPayment: Cash on Delivery (COD) 💵\nDeliver to: Hostel #2, Room 14, Shahida Islam Medical & Dental College\n\nKya main aapka order confirm kar doon? ✅"],
            ['role' => 'user', 'content' => 'CONFIRM'],
        ];

        $botService = new WhatsAppAiBotService();
        $trackingCode = $botService->saveOrderFromHistory($restaurant, $phone, $history);

        $this->assertNotNull($trackingCode);

        // Step 4: Verify Order in database has structured POI fields & exact coordinates
        $order = Order::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($order);

        // Coordinates strictly match customer pin
        $this->assertEquals($lat, (float) $order->delivery_lat);
        $this->assertEquals($lng, (float) $order->delivery_lng);

        // Structured location fields
        $this->assertEquals('Shahida Islam Medical & Dental College', $order->delivery_place_name);
        $this->assertEquals('ChIJ_shahida_islam_college', $order->delivery_place_id);
        $this->assertEquals('google_places', $order->location_source);
        $this->assertStringContainsString('Hostel #2, Room 14', $order->delivery_address);
        $this->assertStringContainsString('Shahida Islam Medical & Dental College', $order->delivery_address);

        // Checkout & cart integrity
        $this->assertEquals(1300.00, $order->total);
        $this->assertEquals('cash_on_delivery', $order->payment_method);
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals('Chicken Fajita Pizza', $order->items()->first()->item_name);
    }

    /**
     * Test WhatsApp native location pin message receives POI resolution
     */
    public function test_native_whatsapp_location_pin_uses_poi_resolution()
    {
        $lat = 29.48913;
        $lng = 71.64155;

        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response([
                'places' => [
                    [
                        'id' => 'ChIJ_shahida_islam_college',
                        'types' => ['university', 'establishment'],
                        'formattedAddress' => 'Shahida Islam Medical & Dental College, Lodhran',
                        'location' => ['latitude' => 29.4895, 'longitude' => 71.6420],
                        'displayName' => ['text' => 'Shahida Islam Medical & Dental College'],
                    ],
                ],
            ], 200),
            'http://127.0.0.1:8080/*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $restaurant = $this->makeRestaurant('rest_wa_poi_1');
        $phone = '923004455667';
        $jid = "{$phone}@s.whatsapp.net";

        $botService = new WhatsAppAiBotService();
        $botService->handleIncomingMessage(
            $restaurant,
            $phone,
            $jid,
            '',
            ['lat' => $lat, 'lng' => $lng]
        );

        $sessionKey = "wa_session_{$restaurant->id}_{$phone}";
        $this->assertEquals([$lat, $lng], Cache::get("verified_delivery_coords_{$sessionKey}"));
        $this->assertEquals('Shahida Islam Medical & Dental College', Cache::get("verified_delivery_place_name_{$sessionKey}"));
        $this->assertEquals('google_places', Cache::get("verified_delivery_source_{$sessionKey}"));

        // History contains the landmark name
        $history = Cache::get($sessionKey);
        $this->assertNotEmpty($history);
        $historyJson = json_encode($history);
        $this->assertStringContainsString('Shahida Islam Medical & Dental College', $historyJson);
        $this->assertStringContainsString((string) $lat, $historyJson);
    }
}
