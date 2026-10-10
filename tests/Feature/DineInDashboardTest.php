<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DineInDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'GrillCafe',
            'whatsapp_number' => '923001234567',
            'owner_phone'     => '923001234567',
            'city'            => 'Lahore',
            'address'         => 'Test Street',
        ]);
        $r->is_active = true;
        $r->status = 'active';
        $r->registration_status = 'approved';
        $r->email_verified_at = now();
        $r->owner_password = Hash::make('secret123');
        $r->save();

        return $r;
    }

    public function test_owner_can_view_dine_in_section(): void
    {
        $r = $this->createRestaurant();

        $response = $this->withSession([
            "restaurant_{$r->id}" => true,
            "restaurant_{$r->id}_login_time" => now()->toIso8601String(),
        ])->get("/dashboard/{$r->id}/dine-in");

        $response->assertStatus(200);
        $response->assertSee('Dine-In Session & Kitchen Orders', false);
        $response->assertSee('Table Floor Map');
    }

    public function test_dine_in_feed_returns_active_table_orders(): void
    {
        $r = $this->createRestaurant();

        Order::create([
            'restaurant_id'    => $r->id,
            'tracking_code'    => 'TRK_DINE_01',
            'customer_name'    => 'Table Guest',
            'customer_phone'   => '0000000000',
            'delivery_address' => 'Table 4',
            'order_type'       => 'dine_in',
            'table_number'     => '4',
            'status'           => 'confirmed',
            'subtotal'         => 1250,
            'delivery_charge'  => 0,
            'total'            => 1250,
        ]);

        $response = $this->withSession([
            "restaurant_{$r->id}" => true,
            "restaurant_{$r->id}_login_time" => now()->toIso8601String(),
        ])->getJson("/dashboard/{$r->id}/dine-in/feed");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'occupied_tables_count',
            'pending_count',
            'total_revenue_today',
            'table_sessions',
            'orders',
        ]);
        $response->assertJsonFragment(['table_number' => '4']);
    }

    public function test_owner_can_update_dine_in_order_status(): void
    {
        $r = $this->createRestaurant();

        $order = Order::create([
            'restaurant_id'    => $r->id,
            'tracking_code'    => 'TRK_DINE_02',
            'customer_name'    => 'Table Guest',
            'customer_phone'   => '0000000000',
            'delivery_address' => 'Table 2',
            'order_type'       => 'dine_in',
            'table_number'     => '2',
            'status'           => 'confirmed',
            'subtotal'         => 800,
            'delivery_charge'  => 0,
            'total'            => 800,
        ]);

        $response = $this->withSession([
            "restaurant_{$r->id}" => true,
            "restaurant_{$r->id}_login_time" => now()->toIso8601String(),
        ])->postJson("/dashboard/{$r->id}/dine-in/orders/{$order->id}/status", [
            'status' => 'served',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('served', $order->fresh()->status);
    }

    public function test_owner_can_increase_and_decrease_table_capacity(): void
    {
        $r = $this->createRestaurant();
        $this->assertEquals(12, $r->total_tables ?: 12);

        // Increment table
        $responseInc = $this->withSession([
            "restaurant_{$r->id}" => true,
            "restaurant_{$r->id}_login_time" => now()->toIso8601String(),
        ])->postJson("/dashboard/{$r->id}/dine-in/tables", [
            'action' => 'increment',
        ]);

        $responseInc->assertStatus(200);
        $responseInc->assertJson(['success' => true, 'total_tables' => 13]);
        $this->assertEquals(13, $r->fresh()->total_tables);

        // Decrement table
        $responseDec = $this->withSession([
            "restaurant_{$r->id}" => true,
            "restaurant_{$r->id}_login_time" => now()->toIso8601String(),
        ])->postJson("/dashboard/{$r->id}/dine-in/tables", [
            'action' => 'decrement',
        ]);

        $responseDec->assertStatus(200);
        $responseDec->assertJson(['success' => true, 'total_tables' => 12]);
        $this->assertEquals(12, $r->fresh()->total_tables);
    }
}
