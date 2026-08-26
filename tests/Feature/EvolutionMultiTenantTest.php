<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EvolutionMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name, string $phone): Restaurant
    {
        $r = new Restaurant([
            'name'                => $name,
            'whatsapp_number'     => $phone,
            'owner_phone'         => $phone,
            'status'              => 'active',
            'registration_status' => 'approved',
            'is_open'             => true,
            'plan'                => 'trial',
        ]);
        $r->is_active             = true;
        $r->owner_password        = Hash::make('Secret123');
        $r->evolution_instance_id = 'rest_' . random_int(1000, 9999);
        $r->save();

        return $r;
    }

    public function test_evolution_instance_naming_is_strictly_scoped(): void
    {
        $r1 = $this->createRestaurant('Pizza Palace', '923001111111');
        $r2 = $this->createRestaurant('Burger Hub', '923002222222');

        $this->assertSame('rest_' . $r1->id, BotEvolutionClient::instanceName($r1));
        $this->assertSame('rest_' . $r2->id, BotEvolutionClient::instanceName($r2));
        $this->assertNotSame(BotEvolutionClient::instanceName($r1), BotEvolutionClient::instanceName($r2));
    }

    public function test_evolution_webhook_updates_connection_status_per_restaurant(): void
    {
        $r = $this->createRestaurant('Chai Corner', '923003333333');
        $r->evolution_instance_id = 'rest_' . $r->id;
        $r->save();

        // Simulate Evolution connection.update event for this restaurant
        $response = $this->postJson(route('webhook.whatsapp'), [
            'event'    => 'connection.update',
            'instance' => 'rest_' . $r->id,
            'data'     => [
                'state' => 'open',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'processed', 'event' => 'connection.update']);

        $this->assertSame('connected', $r->fresh()->bot_status);
        $this->assertSame('connected', $r->fresh()->evolution_status);
    }

    public function test_evolution_webhook_tracking_inquiry_isolation(): void
    {
        $r1 = $this->createRestaurant('Restaurant One', '923004444444');
        $r2 = $this->createRestaurant('Restaurant Two', '923005555555');

        $r1->evolution_instance_id = 'rest_' . $r1->id;
        $r1->save();
        $r2->evolution_instance_id = 'rest_' . $r2->id;
        $r2->save();

        $order1 = $r1->orders()->create([
            'tracking_code'    => 'TRK1001',
            'customer_name'    => 'Customer One',
            'customer_phone'   => '923000000001',
            'delivery_address' => 'Street 1',
            'subtotal'         => 500,
            'delivery_charge'  => 50,
            'total'            => 550,
            'status'           => 'preparing',
            'payment_method'   => 'cash_on_delivery',
        ]);

        // Customer inquires for TRK1001 on Restaurant 1's instance
        $response = $this->postJson(route('webhook.whatsapp'), [
            'event'    => 'messages.upsert',
            'instance' => 'rest_' . $r1->id,
            'data'     => [
                'key' => [
                    'remoteJid' => '923000000001@s.whatsapp.net',
                    'fromMe'    => false,
                ],
                'message' => [
                    'conversation' => 'TRK1001',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'processed', 'event' => 'messages.upsert']);
    }

    public function test_evolution_webhook_ignores_unknown_instances_safely(): void
    {
        $response = $this->postJson(route('webhook.whatsapp'), [
            'event'    => 'messages.upsert',
            'instance' => 'nonexistent_instance_9999',
            'data'     => [],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ignored', 'reason' => 'restaurant_not_found']);
    }
}
