<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Rider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityPhase4TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name): Restaurant
    {
        $r = new Restaurant([
            'name'                => $name,
            'whatsapp_number'     => '92300' . random_int(1000000, 9999999),
            'owner_phone'         => '92300' . random_int(1000000, 9999999),
            'status'              => 'active',
            'registration_status' => 'approved',
            'is_open'             => true,
            'plan'                => 'trial',
        ]);
        $r->is_active = true;
        $r->owner_password = Hash::make('Secret123');
        $r->save();

        return $r;
    }

    // ── TENANT-001: Cross-Tenant Order Status Update IDOR ───────────

    public function test_tenant_001_cannot_update_another_restaurants_order_status(): void
    {
        $restaurantA = $this->createRestaurant('Restaurant Alpha');
        $restaurantB = $this->createRestaurant('Restaurant Beta');

        $orderB = $restaurantB->orders()->create([
            'tracking_code'    => 'TRK-BETA-001',
            'customer_name'    => 'Alice Beta',
            'customer_phone'   => '923110000001',
            'delivery_address' => 'Street 1, Beta City',
            'subtotal'         => 500,
            'delivery_charge'  => 50,
            'total'            => 550,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
        ]);

        // Tenant A logs in
        $this->withSession(["restaurant_{$restaurantA->id}" => true]);

        // Tenant A attempts to update Tenant B's order
        $response = $this->post(route('dashboard.update-status', [$restaurantA->id, $orderB->id]), [
            'status' => 'delivered',
        ]);

        $response->assertForbidden();
        $this->assertSame('pending', $orderB->fresh()->status);
    }

    // ── TENANT-002: Cross-Tenant Print Bill IDOR ────────────────────

    public function test_tenant_002_cannot_print_another_restaurants_bill(): void
    {
        $restaurantA = $this->createRestaurant('Restaurant Alpha');
        $restaurantB = $this->createRestaurant('Restaurant Beta');

        $orderB = $restaurantB->orders()->create([
            'tracking_code'    => 'TRK-BETA-002',
            'customer_name'    => 'Bob Beta',
            'customer_phone'   => '923110000002',
            'delivery_address' => 'Street 2, Beta City',
            'subtotal'         => 1200,
            'delivery_charge'  => 100,
            'total'            => 1300,
            'status'           => 'confirmed',
            'payment_method'   => 'cash_on_delivery',
        ]);

        // Tenant A attempts to view Tenant B's invoice
        $this->withSession(["restaurant_{$restaurantA->id}" => true]);

        $response = $this->get(route('dashboard.print-bill', [$restaurantA->id, $orderB->id]));
        $response->assertForbidden();
    }

    // ── TENANT-003: Cross-Tenant Menu Item Manipulation IDOR ────────

    public function test_tenant_003_cannot_toggle_or_delete_another_restaurants_menu_item(): void
    {
        $restaurantA = $this->createRestaurant('Restaurant Alpha');
        $restaurantB = $this->createRestaurant('Restaurant Beta');

        $catB = $restaurantB->categories()->create([
            'name'       => 'Pizzas',
            'sort_order' => 1,
        ]);

        $itemB = $restaurantB->menuItems()->create([
            'category_id'  => $catB->id,
            'name'         => 'Beta Special Pizza',
            'price'        => 999,
            'is_available' => true,
        ]);

        $this->withSession(["restaurant_{$restaurantA->id}" => true]);

        // Attempt toggle
        $this->post(route('dashboard.toggle-item', [$restaurantA->id, $itemB->id]))
            ->assertForbidden();
        $this->assertTrue($itemB->fresh()->is_available);

        // Attempt delete
        $this->delete(route('dashboard.delete-item', [$restaurantA->id, $itemB->id]))
            ->assertForbidden();
        $this->assertDatabaseHas('menu_items', ['id' => $itemB->id]);
    }

    // ── TENANT-004: Cross-Tenant Rider IDOR ─────────────────────────

    public function test_tenant_004_cannot_delete_another_restaurants_rider(): void
    {
        $restaurantA = $this->createRestaurant('Restaurant Alpha');
        $restaurantB = $this->createRestaurant('Restaurant Beta');

        $riderB = $restaurantB->riders()->create([
            'name'      => 'Beta Rider Asif',
            'phone'     => '923450000000',
            'is_active' => true,
        ]);

        $this->withSession(["restaurant_{$restaurantA->id}" => true]);

        $this->delete(route('dashboard.delete-rider', [$restaurantA->id, $riderB->id]))
            ->assertForbidden();
        $this->assertDatabaseHas('riders', ['id' => $riderB->id]);
    }

    // ── TENANT-005: Cross-Tenant Category Hijacking Prevention ──────

    public function test_tenant_005_cannot_assign_another_restaurants_category_to_menu_item(): void
    {
        $restaurantA = $this->createRestaurant('Restaurant Alpha');
        $restaurantB = $this->createRestaurant('Restaurant Beta');

        $catB = $restaurantB->categories()->create([
            'name'       => 'Beta Private Category',
            'sort_order' => 1,
        ]);

        $this->withSession(["restaurant_{$restaurantA->id}" => true]);

        // Attempt to create an item in Restaurant A referencing Restaurant B's category
        $response = $this->post(route('dashboard.store-item', $restaurantA->id), [
            'name'        => 'Alpha Sneaky Item',
            'price'       => 500,
            'category_id' => $catB->id,
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('menu_items', ['name' => 'Alpha Sneaky Item']);
    }
}
