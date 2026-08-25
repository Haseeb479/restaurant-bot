<?php

namespace Tests\Feature;

use App\Models\{Restaurant, SubscriptionPlan, Payment, Subscription, Setting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaaSOnboardingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminSession(): void
    {
        Setting::put('admin_password_hash', Hash::make('secret-admin-pass'));
        session(['admin_logged_in' => true]);
    }

    public function test_owner_can_complete_step1_signup_and_is_redirected_to_plan(): void
    {
        $response = $this->post(route('onboarding.signup.submit'), [
            'name'            => 'Crispy Bites Grill',
            'owner_name'      => 'Ali Hassan',
            'email'           => 'ali@crispybites.com',
            'whatsapp_number' => '03001234567',
            'owner_phone'     => '03007654321',
            'owner_password'  => 'password123',
            'city'            => 'Lodhran',
            'address'         => 'Main Highway, Lodhran',
        ]);

        $restaurant = Restaurant::where('whatsapp_number', '03001234567')->first();
        $this->assertNotNull($restaurant);
        $this->assertSame('pending', $restaurant->status);
        $this->assertSame('pending_plan', $restaurant->registration_status);
        $this->assertFalse($restaurant->is_active);

        $response->assertRedirect(route('onboarding.plan', $restaurant->id));
    }

    private function createRestaurant(array $attributes = []): Restaurant
    {
        $r = new Restaurant(array_merge([
            'name'                => 'Crispy Bites Grill',
            'owner_name'          => 'Ali Hassan',
            'email'               => 'ali@crispybites.com',
            'whatsapp_number'     => '03001234567',
            'owner_phone'         => '03007654321',
            'status'              => 'pending',
            'registration_status' => 'pending_plan',
            'is_active'           => false,
        ], $attributes));
        $r->owner_password = Hash::make($attributes['password'] ?? 'password123');
        $r->save();
        return $r;
    }

    public function test_owner_can_choose_plan_and_is_redirected_to_payment(): void
    {
        $restaurant = $this->createRestaurant();

        $plan = SubscriptionPlan::create([
            'name'                 => 'Pro',
            'slug'                 => 'pro',
            'price_monthly'        => 7000,
            'price_yearly'         => 70000,
            'max_orders_per_month' => 2000,
            'max_menu_items'       => 150,
            'is_active'            => true,
            'is_popular'           => true,
            'features'             => ['AI Bot', 'Live GPS Tracking'],
        ]);

        $response = $this->post(route('onboarding.plan.submit', $restaurant->id), [
            'plan_id'          => $plan->id,
            'billing_interval' => 'monthly',
        ]);

        $restaurant->refresh();
        $this->assertSame($plan->id, $restaurant->plan_id);
        $this->assertSame('pro', $restaurant->plan);
        $this->assertSame('pending_payment', $restaurant->registration_status);

        $response->assertRedirect(route('onboarding.payment', $restaurant->id));
    }

    public function test_owner_can_complete_payment_and_is_set_to_pending_review(): void
    {
        $plan = SubscriptionPlan::create([
            'name'                 => 'Pro',
            'slug'                 => 'pro',
            'price_monthly'        => 7000,
            'price_yearly'         => 70000,
            'max_orders_per_month' => 2000,
            'max_menu_items'       => 150,
            'is_active'            => true,
            'is_popular'           => true,
        ]);

        $restaurant = $this->createRestaurant([
            'plan_id'             => $plan->id,
            'plan'                => 'pro',
            'status'              => 'pending',
            'registration_status' => 'pending_payment',
        ]);

        $response = $this->post(route('onboarding.payment.submit', $restaurant->id), [
            'payment_method'    => 'stripe',
            'payment_reference' => 'ch_test_123456',
        ]);

        $restaurant->refresh();
        $this->assertSame('completed', $restaurant->payment_status);
        $this->assertSame('pending_review', $restaurant->registration_status);
        $this->assertSame('pending', $restaurant->status);

        $this->assertDatabaseHas('payments', [
            'restaurant_id'  => $restaurant->id,
            'plan_id'        => $plan->id,
            'payment_method' => 'stripe',
            'status'         => 'completed',
        ]);

        $response->assertRedirect(route('onboarding.status', $restaurant->id));
    }

    public function test_superadmin_can_view_pending_queue_and_approve_restaurant(): void
    {
        $this->createAdminSession();

        $plan = SubscriptionPlan::create([
            'name'          => 'Pro',
            'slug'          => 'pro',
            'price_monthly' => 7000,
            'is_active'     => true,
        ]);

        $restaurant = $this->createRestaurant([
            'plan_id'             => $plan->id,
            'plan'                => 'pro',
            'payment_status'      => 'completed',
            'registration_status' => 'pending_review',
            'status'              => 'pending',
        ]);

        // 1. Check pending queue page
        $this->get(route('admin.restaurants.pending'))
            ->assertOk()
            ->assertSee('Crispy Bites Grill')
            ->assertSee('Ali Hassan');

        // 2. Approve restaurant
        $response = $this->post(route('admin.restaurant.approve', $restaurant->id));
        $response->assertRedirect();

        $restaurant->refresh();
        $this->assertSame('active', $restaurant->status);
        $this->assertSame('approved', $restaurant->registration_status);
        $this->assertTrue($restaurant->is_active);
        $this->assertNotNull($restaurant->approved_at);

        $this->assertDatabaseHas('subscriptions', [
            'restaurant_id' => $restaurant->id,
            'plan_id'       => $plan->id,
            'status'        => 'active',
        ]);
    }

    public function test_approved_owner_can_login_and_access_dashboard(): void
    {
        $restaurant = $this->createRestaurant([
            'status'              => 'active',
            'registration_status' => 'approved',
            'is_active'           => true,
        ]);

        $response = $this->post(route('landing.owner-login'), [
            'restaurant_id' => $restaurant->id,
            'password'      => 'password123',
        ]);

        $response->assertRedirect(route('dashboard.orders', $restaurant->id));
        $this->get(route('dashboard.orders', $restaurant->id))->assertOk();
    }

    public function test_superadmin_can_reject_restaurant_and_owner_login_is_blocked(): void
    {
        $this->createAdminSession();

        $restaurant = $this->createRestaurant([
            'status'              => 'pending',
            'registration_status' => 'pending_review',
            'is_active'           => false,
        ]);

        $response = $this->post(route('admin.restaurant.reject', $restaurant->id), [
            'reason' => 'Invalid food license submitted.',
        ]);

        $restaurant->refresh();
        $this->assertSame('rejected', $restaurant->status);
        $this->assertSame('rejected', $restaurant->registration_status);
        $this->assertSame('Invalid food license submitted.', $restaurant->rejection_reason);

        // Attempting login blocks owner
        $loginRes = $this->post(route('landing.owner-login'), [
            'restaurant_id' => $restaurant->id,
            'password'      => 'password123',
        ]);

        $loginRes->assertSessionHasErrors('password', null, 'owner');
    }

    public function test_pending_owner_login_redirects_to_status_screen(): void
    {
        $restaurant = $this->createRestaurant([
            'status'              => 'pending',
            'registration_status' => 'pending_review',
            'is_active'           => false,
        ]);

        $response = $this->post(route('landing.owner-login'), [
            'restaurant_id' => $restaurant->id,
            'password'      => 'password123',
        ]);

        $response->assertRedirect(route('onboarding.status', $restaurant->id));
    }
}
