<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityPhase1AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveRestaurant(string $name = 'Burger Hub', string $password = 'securePassword123!'): Restaurant
    {
        $r = new Restaurant([
            'name'                => $name,
            'email'               => strtolower(str_replace(' ', '', $name)) . '@example.com',
            'whatsapp_number'     => '92300' . random_int(1000000, 9999999),
            'owner_phone'         => '92300' . random_int(1000000, 9999999),
            'status'              => 'active',
            'registration_status' => 'approved',
            'is_active'           => true,
            'is_open'             => true,
            'plan'                => 'trial',
        ]);
        $r->owner_password = Hash::make($password);
        $r->save();

        return $r;
    }

    // ── AUTH-001: Login Protection & Generic Error Responses ─────────

    public function test_auth_001_owner_login_success_with_valid_credentials(): void
    {
        $restaurant = $this->createActiveRestaurant('Kababish Grill', 'ValidPass123');

        $response = $this->post('/login', [
            'restaurant_name' => 'Kababish Grill',
            'password'        => 'ValidPass123',
        ]);

        $response->assertRedirect(route('dashboard.orders', $restaurant->id));
        $this->assertTrue(session("restaurant_{$restaurant->id}"));
    }

    public function test_auth_001_owner_login_returns_generic_error_for_invalid_password(): void
    {
        $this->createActiveRestaurant('Kababish Grill', 'ValidPass123');

        $response = $this->from('/login')->post('/login', [
            'restaurant_name' => 'Kababish Grill',
            'password'        => 'WrongPassword999',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password' => 'Wrong restaurant name or password. Please check and try again.'], null, 'owner');
        $this->assertNull(session('restaurant_1'));
    }

    public function test_auth_001_owner_login_returns_same_generic_error_for_unknown_restaurant(): void
    {
        $response = $this->from('/login')->post('/login', [
            'restaurant_name' => 'Completely Nonexistent Restaurant XYZ',
            'password'        => 'RandomPassword123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password' => 'Wrong restaurant name or password. Please check and try again.'], null, 'owner');
    }

    public function test_auth_001_owner_login_is_rate_limited(): void
    {
        $this->createActiveRestaurant('RateLimited Store', 'Pass123');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'restaurant_name' => 'RateLimited Store',
                'password'        => 'wrong-pass',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'restaurant_name' => 'RateLimited Store',
            'password'        => 'wrong-pass',
        ])->assertStatus(429);
    }

    // ── AUTH-002: Password Storage & Hash Protection ─────────────────

    public function test_auth_002_owner_password_is_never_leaked_in_model_serialization(): void
    {
        $restaurant = $this->createActiveRestaurant('Secret Store', 'SuperSecret999');

        $array = $restaurant->toArray();
        $json  = json_encode($restaurant);

        $this->assertArrayNotHasKey('owner_password', $array);
        $this->assertStringNotContainsString('owner_password', $json);
        $this->assertStringNotContainsString('SuperSecret999', $json);
    }

    public function test_auth_002_owner_passwords_are_stored_using_bcrypt(): void
    {
        $restaurant = $this->createActiveRestaurant('Bcrypt Store', 'PlainPassword999');

        $storedHash = $restaurant->owner_password;
        $this->assertNotSame('PlainPassword999', $storedHash);
        $this->assertStringStartsWith('$2y$', $storedHash);
        $this->assertTrue(Hash::check('PlainPassword999', $storedHash));
    }

    // ── AUTH-003 & AUTH-004: Session Lifecycle & Logout Invalidation ─

    public function test_auth_004_owner_logout_invalidates_session(): void
    {
        $restaurant = $this->createActiveRestaurant('Logout Store', 'Pass123');

        $this->withSession(["restaurant_{$restaurant->id}" => true]);
        $this->get(route('dashboard.orders', $restaurant->id))->assertOk();

        $this->post(route('dashboard.logout', $restaurant->id))
            ->assertRedirect('/');

        $this->assertNull(session("restaurant_{$restaurant->id}"));
        $this->get(route('dashboard.orders', $restaurant->id))->assertRedirect();
    }

    public function test_auth_004_admin_logout_invalidates_session(): void
    {
        $this->withSession(['admin_logged_in' => true]);
        $this->get(route('admin.dashboard'))->assertOk();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertNull(session('admin_logged_in'));
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    // ── AUTH-005: Password Reset & Anti-Enumeration ─────────────────

    public function test_auth_005_forgot_password_always_returns_success_to_prevent_enumeration(): void
    {
        // 1. For an existing restaurant
        $r = $this->createActiveRestaurant('Real Place', 'Pass123');
        $responseExisting = $this->from('/forgot-password')->post('/forgot-password', [
            'restaurant_name' => 'Real Place',
            'email'           => $r->email,
            'phone'           => '03001234567',
        ]);
        $responseExisting->assertRedirect('/forgot-password');
        $responseExisting->assertSessionHas('reset_sent', true);

        // 2. For a non-existent restaurant
        $responseMissing = $this->from('/forgot-password')->post('/forgot-password', [
            'restaurant_name' => 'Fake Store 999',
            'email'           => 'doesnotexist@nowhere.com',
            'phone'           => '03009999999',
        ]);
        $responseMissing->assertRedirect('/forgot-password');
        $responseMissing->assertSessionHas('reset_sent', true);
    }
}
