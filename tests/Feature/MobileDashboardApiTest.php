<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantApiToken;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_center_returns_real_backend_kpis()
    {
        $restaurant = new Restaurant([
            'name'            => 'Pizza Delight',
            'whatsapp_number' => '923001112233',
            'wa_phone_id'     => 'phone_pza',
            'owner_phone'     => '923001112233',
            'city'            => 'Multan',
            'address'         => 'Cantt',
            'is_active'       => true,
            'is_open'         => true,
        ]);
        $restaurant->owner_password = Hash::make('pass123');
        $restaurant->save();

        // Create today order
        Order::create([
            'restaurant_id'   => $restaurant->id,
            'tracking_code'   => 'TRK123456',
            'customer_name'   => 'Haris',
            'customer_phone'  => '923004445566',
            'delivery_address'=> 'House 1',
            'subtotal'        => 1200,
            'delivery_charge' => 100,
            'total'           => 1300,
            'status'          => 'pending',
        ]);

        $plainToken = Str::random(60);
        RestaurantApiToken::create([
            'restaurant_id' => $restaurant->id,
            'token'         => hash('sha256', $plainToken),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->getJson('/api/v1/dashboard/command-center');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kpis' => [
                    'today_sales'  => 1300,
                    'today_orders' => 1,
                    'live_orders'  => 1,
                ],
                'needs_attention' => [
                    'pending_orders' => 1,
                ],
            ]);

        // Test toggle open
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->postJson('/api/v1/dashboard/toggle-open');

        $toggleResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_open' => false,
            ]);

        $this->assertFalse((bool) $restaurant->fresh()->is_open);
    }
}
