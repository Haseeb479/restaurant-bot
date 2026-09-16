<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Validates the Foodpanda-style live delivery tracking experience:
 * - Restaurant: 'Grillcafe'
 * - Customer: 'Haseeb Tariq'
 * - Exact WhatsApp GPS destination pin preserved
 * - Real road routing engine (OSRM) integration without fake straight lines or Bezier curves
 * - No fake or interpolated rider coordinates
 * - Live status polling returns real rider GPS and updated timestamp without PII
 */
class FoodpandaLiveTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Restaurant: 'Grillcafe'
        $this->restaurant = new Restaurant([
            'name'            => 'Grillcafe',
            'whatsapp_number' => '923001234567',
            'owner_phone'     => '923001234567',
            'city'            => 'Lodhran',
            'address'         => 'Main Chowk, Lodhran',
            'restaurant_lat'  => 29.5405000,
            'restaurant_lng'  => 71.6336000,
            'is_active'       => true,
            'is_open'         => true,
            'plan'            => 'trial',
        ]);
        $this->restaurant->owner_password = Hash::make('owner-secure-password-123');
        $this->restaurant->save();

        // 2. Customer: 'Haseeb Tariq' with exact WhatsApp GPS coordinates
        $this->order = Order::create([
            'restaurant_id'             => $this->restaurant->id,
            'customer_phone'            => '923119876543',
            'customer_name'             => 'Haseeb Tariq',
            'delivery_address'          => 'House 45, Street 8, Block C, Lodhran',
            'delivery_lat'              => 29.5460000,
            'delivery_lng'              => 71.6420000,
            'tracking_code'             => 'TRK-GRILL-9901',
            'status'                    => 'out_for_delivery',
            'subtotal'                  => 1200,
            'delivery_charge'           => 150,
            'total'                     => 1350,
            'payment_method'            => 'cash_on_delivery',
            'rider_name'                => 'Sajid Ali',
            'rider_phone'               => '923215554433',
            'rider_token'               => Order::generateRiderToken(),
            'rider_lat'                 => 29.5422000,
            'rider_lng'                 => 71.6365000,
            'rider_location_updated_at' => now(),
        ]);
    }

    public function test_tracking_page_renders_with_grillcafe_and_haseeb_tariq_scenario(): void
    {
        $response = $this->get(route('order.track.live', $this->order->tracking_code));

        $response->assertOk();
        $html = $response->getContent();

        // Verify Restaurant and Customer names appear
        $this->assertStringContainsString('Grillcafe', $html);
        $this->assertStringContainsString('Haseeb Tariq', $html);
        $this->assertStringContainsString('TRK-GRILL-9901', $html);

        // Verify exact WhatsApp destination coordinates are embedded as source of truth
        $this->assertStringContainsString('29.546', $html);
        $this->assertStringContainsString('71.642', $html);

        // Verify restaurant origin coordinates are embedded
        $this->assertStringContainsString('29.5405', $html);
        $this->assertStringContainsString('71.6336', $html);

        // Verify live rider GPS coordinates are embedded
        $this->assertStringContainsString('29.5422', $html);
        $this->assertStringContainsString('71.6365', $html);
    }

    public function test_tracking_page_uses_real_road_routing_and_no_fake_bezier_curves(): void
    {
        $response = $this->get(route('order.track.live', $this->order->tracking_code));
        $html = $response->getContent();

        // Real OSRM driving routing engine must be embedded
        $this->assertStringContainsString('router.project-osrm.org/route/v1/driving', $html);
        $this->assertStringContainsString('fetchRoadRoute', $html);
        $this->assertStringContainsString('renderActiveRoadRoute', $html);

        // Fake Bezier curve formula must be completely removed
        $this->assertStringNotContainsString('midLat', $html);
        $this->assertStringNotContainsString('midLng', $html);

        // Fake 45%/55% rider interpolation must be completely removed
        $this->assertStringNotContainsString('0.45', $html);
        $this->assertStringNotContainsString('0.55', $html);
    }

    public function test_tracking_status_endpoint_returns_live_rider_gps_and_no_pii(): void
    {
        $response = $this->getJson(route('order.track.status', $this->order->tracking_code));

        $response->assertOk();
        $response->assertJson([
            'status'       => 'out_for_delivery',
            'has_live_gps' => true,
            'rider_lat'    => 29.5422,
            'rider_lng'    => 71.6365,
        ]);
        $this->assertNotNull($response->json('rider_updated'));

        // Ensure exactly the 7 documented status keys exist and no PII leaks
        $this->assertSame(7, count($response->json()));
        $body = $response->getContent();
        $this->assertStringNotContainsString('Haseeb Tariq', $body);
        $this->assertStringNotContainsString('923119876543', $body);
        $this->assertStringNotContainsString('House 45', $body);
    }

    public function test_when_rider_has_no_gps_it_does_not_invent_fake_coordinates(): void
    {
        // Order out for delivery, but rider GPS not yet reported
        $orderNoGps = Order::create([
            'restaurant_id'    => $this->restaurant->id,
            'customer_phone'   => '923119876543',
            'customer_name'    => 'Haseeb Tariq',
            'delivery_address' => 'House 45, Street 8, Block C, Lodhran',
            'delivery_lat'     => 29.5460000,
            'delivery_lng'     => 71.6420000,
            'tracking_code'    => 'TRK-NOGPS-1234',
            'status'           => 'out_for_delivery',
            'subtotal'         => 800,
            'total'            => 800,
            'payment_method'   => 'cash_on_delivery',
            'rider_lat'        => null,
            'rider_lng'        => null,
        ]);

        $response = $this->getJson(route('order.track.status', $orderNoGps->tracking_code));

        $response->assertOk();
        $response->assertJson([
            'status'       => 'out_for_delivery',
            'has_live_gps' => false,
            'rider_lat'    => null,
            'rider_lng'    => null,
        ]);

        $pageResponse = $this->get(route('order.track.live', $orderNoGps->tracking_code));
        $pageResponse->assertOk();
        $html = $pageResponse->getContent();

        // Badge should clearly indicate waiting for GPS
        $this->assertStringContainsString('Awaiting rider GPS', $html);
    }
}
