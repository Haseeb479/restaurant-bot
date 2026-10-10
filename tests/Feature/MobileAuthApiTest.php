<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_succeeds_with_valid_credentials()
    {
        $restaurant = new Restaurant([
            'name'            => 'Pizza Palace',
            'whatsapp_number' => '923001234567',
            'wa_phone_id'     => 'phone_123',
            'owner_phone'     => '923001234567',
            'city'            => 'Lahore',
            'address'         => 'Mall Road',
            'is_active'       => true,
            'is_open'         => true,
        ]);
        $restaurant->owner_password = Hash::make('secret123');
        $restaurant->save();

        $response = $this->postJson('/api/v1/auth/login', [
            'restaurant_name' => 'Pizza Palace',
            'password'        => 'secret123',
            'device_name'     => 'Pixel 8',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'restaurant' => [
                    'id'   => $restaurant->id,
                    'name' => 'Pizza Palace',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $token = $response->json('token');

        // Test /api/v1/auth/me with bearer token
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'restaurant' => [
                    'id' => $restaurant->id,
                ],
            ]);

        // Test logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // After logout, token must be invalid (401)
        $meAfterLogout = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meAfterLogout->assertStatus(401);
    }

    public function test_mobile_login_fails_with_invalid_credentials()
    {
        $r = new Restaurant([
            'name'            => 'Burger King',
            'whatsapp_number' => '923009876543',
            'wa_phone_id'     => 'phone_987',
            'owner_phone'     => '923009876543',
            'city'            => 'Karachi',
            'address'         => 'Clifton',
            'is_active'       => true,
        ]);
        $r->owner_password = Hash::make('correct_pass');
        $r->save();

        $response = $this->postJson('/api/v1/auth/login', [
            'restaurant_name' => 'Burger King',
            'password'        => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }
}
