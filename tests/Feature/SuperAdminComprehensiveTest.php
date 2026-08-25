<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('admin_password_hash', \Illuminate\Support\Facades\Hash::make('SuperAdminSecret123!'));
    }

    public function test_super_admin_requires_authentication(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_login(): void
    {
        $response = $this->post(route('admin.login'), [
            'password' => 'SuperAdminSecret123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('admin_logged_in'));
    }

    public function test_super_admin_dashboard_renders_with_metrics(): void
    {
        $response = $this->withSession(['admin_logged_in' => true])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Executive Dashboard');
        $response->assertSee('SaaS Monthly Revenue');
    }

    public function test_super_admin_can_create_and_manage_restaurant(): void
    {
        $plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'pro'],
            ['name' => 'Pro Business', 'price_monthly' => 3500, 'price_yearly' => 35000]
        );

        $response = $this->withSession(['admin_logged_in' => true])
            ->post(route('admin.store-restaurant'), [
                'name'            => 'Karachi Grill House',
                'whatsapp_number' => '923009988776',
                'owner_phone'     => '03009988776',
                'owner_password'  => 'secret1234',
                'plan'            => 'pro',
                'city'            => 'Karachi',
            ]);

        $response->assertRedirect(route('admin.restaurants'));
        $this->assertDatabaseHas('restaurants', [
            'name'            => 'Karachi Grill House',
            'whatsapp_number' => '923009988776',
        ]);
    }

    public function test_super_admin_can_access_billing_and_support(): void
    {
        $billingResponse = $this->withSession(['admin_logged_in' => true])
            ->get(route('admin.billing'));
        $billingResponse->assertStatus(200);
        $billingResponse->assertSee('Subscription Pricing Plans');

        $supportResponse = $this->withSession(['admin_logged_in' => true])
            ->get(route('admin.support'));
        $supportResponse->assertStatus(200);
        $supportResponse->assertSee('Support Queue');
    }
}
