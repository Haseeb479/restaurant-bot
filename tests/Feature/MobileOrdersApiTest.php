<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantApiToken;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_are_strictly_scoped_to_authenticated_restaurant()
    {
        $r1 = new Restaurant([
            'name'            => 'Restaurant A',
            'whatsapp_number' => '923001111111',
            'wa_phone_id'     => 'wa_1',
            'owner_phone'     => '923001111111',
            'city'            => 'Lahore',
            'address'         => 'Street 1',
            'is_active'       => true,
        ]);
        $r1->owner_password = Hash::make('pass');
        $r1->save();

        $r2 = new Restaurant([
            'name'            => 'Restaurant B',
            'whatsapp_number' => '923002222222',
            'wa_phone_id'     => 'wa_2',
            'owner_phone'     => '923002222222',
            'city'            => 'Lahore',
            'address'         => 'Street 2',
            'is_active'       => true,
        ]);
        $r2->owner_password = Hash::make('pass');
        $r2->save();

        $order1 = Order::create([
            'restaurant_id'   => $r1->id,
            'tracking_code'   => 'TRK_A',
            'customer_name'   => 'Customer A',
            'customer_phone'  => '923001111111',
            'delivery_address'=> 'Address A',
            'subtotal'        => 500,
            'delivery_charge' => 50,
            'total'           => 550,
            'status'          => 'pending',
        ]);

        $order2 = Order::create([
            'restaurant_id'   => $r2->id,
            'tracking_code'   => 'TRK_B',
            'customer_name'   => 'Customer B',
            'customer_phone'  => '923002222222',
            'delivery_address'=> 'Address B',
            'subtotal'        => 900,
            'delivery_charge' => 100,
            'total'           => 1000,
            'status'          => 'pending',
        ]);

        $token1 = Str::random(60);
        RestaurantApiToken::create([
            'restaurant_id' => $r1->id,
            'token'         => hash('sha256', $token1),
        ]);

        // List orders for Restaurant A
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('orders'));
        $this->assertEquals($order1->id, $response->json('orders.0.id'));

        // Restaurant A trying to view Restaurant B's order must return 404
        $crossView = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/v1/orders/' . $order2->id);

        $crossView->assertStatus(404);

        // Restaurant A trying to update Restaurant B's order status must return 404
        $crossUpdate = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->patchJson('/api/v1/orders/' . $order2->id . '/status', [
                'status' => 'confirmed',
            ]);

        $crossUpdate->assertStatus(404);

        // Legitimate status update for Restaurant A
        $legitUpdate = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->patchJson('/api/v1/orders/' . $order1->id . '/status', [
                'status' => 'confirmed',
            ]);

        $legitUpdate->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order'   => [
                    'id'     => $order1->id,
                    'status' => 'confirmed',
                ],
            ]);
    }
}
