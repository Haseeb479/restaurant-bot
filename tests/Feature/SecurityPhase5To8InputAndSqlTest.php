<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityPhase5To8InputAndSqlTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveRestaurant(string $name = 'Alpha Food'): Restaurant
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

    // ── DB-001: SQL Injection Resistance ─────────────────────────────

    public function test_db_001_login_is_immune_to_sql_injection_payloads(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->createActiveRestaurant('Target Restaurant');

        $sqliPayloads = [
            "' OR '1'='1",
            "Target Restaurant' --",
            "Target Restaurant' #",
            "' UNION SELECT * FROM users --",
            "admin' OR 1=1--",
            "' OR 1=1 /*",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->from('/login')->post('/login', [
                'restaurant_name' => $payload,
                'password'        => 'WrongPassword',
            ]);

            // Must reject and not grant any session
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors(['password' => 'Wrong restaurant name or password. Please check and try again.'], null, 'owner');
            $this->assertNull(session('restaurant_1'));
        }
    }

    // ── MASS-001: Mass Assignment Immunity ───────────────────────────

    public function test_mass_001_registration_cannot_escalate_status_or_is_active(): void
    {
        $response = $this->post('/register', [
            'name'                => 'Hacker Cafe',
            'owner_name'          => 'Evil Attacker',
            'email'               => 'evil@attacker.com',
            'whatsapp_number'     => '923009998877',
            'owner_phone'         => '923009998877',
            'owner_password'      => 'Password123!',
            // Attacker attempts to bypass approval & activate directly:
            'is_active'           => true,
            'status'              => 'active',
            'registration_status' => 'approved',
            'role'                => 'super_admin',
        ]);

        $restaurant = Restaurant::where('email', 'evil@attacker.com')->firstOrFail();

        // Must remain pending and inactive regardless of payload
        $this->assertSame('pending', $restaurant->status);
        $this->assertSame('pending_plan', $restaurant->registration_status);
        $this->assertFalse($restaurant->is_active);
    }

    public function test_mass_001_settings_update_cannot_alter_protected_columns(): void
    {
        $r = $this->createActiveRestaurant('Safe Kitchen');
        $this->withSession(["restaurant_{$r->id}" => true]);

        $this->post(route('dashboard.update-settings', $r->id), [
            'name'            => 'Safe Kitchen Renamed',
            'whatsapp_number' => $r->whatsapp_number,
            // Malicious payload attempts:
            'status'          => 'rejected',
            'is_active'       => false,
            'owner_password'  => Hash::make('NewInjectedPw'),
            'api_key'         => 'sk_live_hacked',
        ]);

        $fresh = $r->fresh();
        $this->assertSame('Safe Kitchen Renamed', $fresh->name);
        $this->assertSame('active', $fresh->status);
        $this->assertTrue($fresh->is_active);
        $this->assertTrue(Hash::check('Secret123', $fresh->owner_password));
        $this->assertNotSame('sk_live_hacked', $fresh->api_key);
    }

    // ── PAY-001: Server-Side Pricing Integrity ───────────────────────

    public function test_pay_001_payment_amount_is_calculated_strictly_server_side(): void
    {
        $plan = SubscriptionPlan::create([
            'name'          => 'Pro Hardened',
            'slug'          => 'pro-hardened',
            'price_monthly' => 7000,
            'price_yearly'  => 70000,
            'is_active'     => true,
        ]);

        $r = $this->createActiveRestaurant('SaaS Prospect');
        $r->plan_id = $plan->id;
        $r->save();

        // Client attempts to pay Rs. 1 instead of Rs. 7,000
        $response = $this->post(route('onboarding.payment.submit', $r->id), [
            'payment_method' => 'easypaisa',
            'amount'         => 1, // Tampered price
        ]);

        $response->assertRedirect(route('onboarding.status', $r->id));

        // Database payment and invoice MUST record the server-defined 7,000 PKR
        $payment = $r->payments()->latest()->firstOrFail();
        $this->assertEquals(7000, (float) $payment->amount);

        $invoice = \App\Models\Invoice::where('restaurant_id', $r->id)->latest()->firstOrFail();
        $this->assertEquals(7000, (float) $invoice->amount);
    }

    // ── API-001: Input Validation Bounds & Enums ─────────────────────

    public function test_api_001_order_status_rejects_unallowed_arbitrary_strings(): void
    {
        $r = $this->createActiveRestaurant('Status Test Restaurant');
        $order = $r->orders()->create([
            'tracking_code'    => 'TRK-STAT-001',
            'customer_name'    => 'Test Customer',
            'customer_phone'   => '923000000001',
            'delivery_address' => 'Test Street',
            'subtotal'         => 500,
            'delivery_charge'  => 50,
            'total'            => 550,
            'status'           => 'pending',
            'payment_method'   => 'cash_on_delivery',
        ]);

        $this->withSession(["restaurant_{$r->id}" => true]);

        // Attempt invalid status
        $response = $this->post(route('dashboard.update-status', [$r->id, $order->id]), [
            'status' => 'INJECTED_STATUS_ENUM_VALUE',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);
    }
}
