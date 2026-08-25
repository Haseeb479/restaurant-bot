<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderGpsTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;
    private Order $order;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = new Restaurant([
            'name'            => 'Tasty Bites',
            'whatsapp_number' => '03001234567',
            'owner_phone'     => '03001234567',
            'city'            => 'Lahore',
            'is_open'         => true,
        ]);
        $this->restaurant->owner_password = bcrypt('secret123');
        $this->restaurant->save();

        $this->token = Order::generateRiderToken();

        $this->order = Order::create([
            'restaurant_id'    => $this->restaurant->id,
            'customer_phone'   => '03111234567',
            'customer_name'    => 'Ali Khan',
            'delivery_address' => 'Gulberg III, Lahore',
            'tracking_code'    => 'F-TESTCODE123456',
            'status'           => 'out_for_delivery',
            'payment_method'   => 'cash_on_delivery',
            'total'            => 1500,
            'rider_name'       => 'Hamza Rider',
            'rider_phone'      => '03211234567',
            'rider_token'      => $this->token,
        ]);
    }

    public function test_rider_can_view_delivery_portal_with_valid_token(): void
    {
        $response = $this->get(route('rider.deliver.show', $this->token));

        $response->assertOk();
        $response->assertSee('Rider Delivery Mode');
        $response->assertSee('Ali Khan');
        $response->assertSee('Gulberg III, Lahore');
        $response->assertSee('1,500');
    }

    public function test_invalid_rider_token_returns_404(): void
    {
        $response = $this->get(route('rider.deliver.show', 'invalid-token-12345'));

        $response->assertNotFound();
    }

    public function test_rider_can_stream_live_gps_coordinates(): void
    {
        $response = $this->postJson(route('rider.deliver.location', $this->token), [
            'latitude'  => 31.5204,
            'longitude' => 74.3587,
            'accuracy'  => 12.5,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals('31.5204000', $this->order->rider_lat);
        $this->assertEquals('74.3587000', $this->order->rider_lng);
        $this->assertNotNull($this->order->rider_location_updated_at);
        $this->assertTrue($this->order->hasLiveGps());
    }

    public function test_invalid_gps_coordinates_are_rejected(): void
    {
        $response = $this->postJson(route('rider.deliver.location', $this->token), [
            'latitude'  => 999.0, // Out of bounds (-90 to 90)
            'longitude' => 74.3587,
        ]);

        $response->assertUnprocessable();
    }

    public function test_customer_tracking_status_endpoint_returns_live_gps(): void
    {
        $this->order->update([
            'rider_lat'                 => 31.5204,
            'rider_lng'                 => 74.3587,
            'rider_location_updated_at' => now(),
        ]);

        $response = $this->getJson(route('order.track.status', $this->order->tracking_code));

        $response->assertOk();
        $response->assertJson([
            'status'       => 'out_for_delivery',
            'has_live_gps' => true,
            'rider_lat'    => 31.5204,
            'rider_lng'    => 74.3587,
        ]);
    }

    public function test_rider_can_mark_order_as_delivered(): void
    {
        $response = $this->post(route('rider.deliver.complete', $this->token));

        $response->assertRedirect(route('rider.deliver.show', $this->token));

        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->status);
        $this->assertTrue($this->order->is_paid);
    }
}
