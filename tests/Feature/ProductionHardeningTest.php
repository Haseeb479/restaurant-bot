<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test B1: Webhook endpoint rejects requests without a valid EvolutionAPI key.
     */
    public function test_webhook_requires_valid_api_key(): void
    {
        config(['services.evolution.api_key' => 'valid_secret_key_123']);

        // 1. Missing header -> 403
        $responseNoKey = $this->postJson('/webhook/whatsapp', ['data' => 'test']);
        $responseNoKey->assertStatus(403);

        // 2. Wrong header -> 403
        $responseWrongKey = $this->withHeaders(['apikey' => 'wrong_key'])
            ->postJson('/webhook/whatsapp', ['data' => 'test']);
        $responseWrongKey->assertStatus(403);

        // 3. Correct header -> Accepted (or processed)
        $responseValidKey = $this->withHeaders(['apikey' => 'valid_secret_key_123'])
            ->postJson('/webhook/whatsapp', ['instance' => 'rest_99999', 'event' => 'connection_update']);
        $responseValidKey->assertStatus(200);
    }

    /**
     * Test G3: Production health diagnostic endpoint returns structured status.
     */
    public function test_health_endpoint_returns_diagnostics(): void
    {
        $response = $this->getJson('/health');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'groq',
                    'evolution',
                    'maps',
                ],
            ]);
    }

    /**
     * Test C3: Order state machine prevents invalid backwards/terminal status transitions.
     */
    public function test_order_status_state_machine_prevents_invalid_transitions(): void
    {
        $order = new Order(['status' => 'pending']);
        $this->assertTrue($order->canTransitionTo('confirmed'));
        $this->assertTrue($order->canTransitionTo('preparing'));
        $this->assertTrue($order->canTransitionTo('cancelled'));
        $this->assertFalse($order->canTransitionTo('delivered'));

        $order->status = 'delivered';
        $this->assertFalse($order->canTransitionTo('pending'));
        $this->assertFalse($order->canTransitionTo('preparing'));
        $this->assertFalse($order->canTransitionTo('cancelled'));
    }

    /**
     * Test H1: Trial plan expiration enforces 14-day limit.
     */
    public function test_trial_plan_expiration_enforces_14_days(): void
    {
        $activeTrial = new Restaurant([
            'plan'             => 'trial',
            'trial_started_at' => now()->subDays(5),
        ]);
        $this->assertTrue($activeTrial->isPlanActive());

        $expiredTrial = new Restaurant([
            'plan'             => 'trial',
            'trial_started_at' => now()->subDays(15),
        ]);
        $this->assertFalse($expiredTrial->isPlanActive());
    }

    /**
     * Test C5: Menu item limit calculation works for restaurant plan tier.
     */
    public function test_menu_item_limit_defaults(): void
    {
        $restaurant = new Restaurant(['plan' => 'trial']);
        $this->assertEquals(50, $restaurant->maxMenuItems());
        $this->assertEquals(500, $restaurant->maxMonthlyOrders());
    }
}
