<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\WhatsAppAiBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Jobs\ProcessWhatsAppMessage;

class WhatsAppLocationPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.evolution.api_key' => 'test_evo_secret_key']);
    }

    private function makeRestaurant(string $instanceId): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Location Test Rest',
            'whatsapp_number' => '9230' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'delivery_charge' => 100.00,
            'is_open'         => true,
            'city'            => 'Lahore',
            'address'         => 'Mall Road',
        ]);
        $r->status                = 'active';
        $r->registration_status   = 'approved';
        $r->is_active             = true;
        $r->email_verified_at     = now();
        $r->plan                  = 'trial';
        $r->evolution_instance_id = $instanceId;
        $r->owner_password        = \Illuminate\Support\Facades\Hash::make('owner-secret-password');
        $r->save();
        return $r;
    }

    public function test_native_location_pin_is_extracted_and_queued()
    {
        Queue::fake();

        $restaurant = $this->makeRestaurant('rest_100');

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'rest_100',
            'data' => [
                'message' => [
                    'key' => [
                        'remoteJid' => '923001234567@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => '123456'
                    ],
                    'locationMessage' => [
                        'degreesLatitude' => 31.5204,
                        'degreesLongitude' => 74.3587,
                        'name' => 'Lahore Pin'
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders(['apikey' => 'test_evo_secret_key'])
            ->postJson(route('webhook.whatsapp'), $payload);
        $response->assertStatus(200);

        Queue::assertPushed(ProcessWhatsAppMessage::class, function ($job) {
            $coords = $job->locationCoords;
            return $coords !== null && $coords['lat'] === 31.5204 && $coords['lng'] === 74.3587;
        });
    }

    public function test_invalid_location_null_island()
    {
        Queue::fake();

        $restaurant = $this->makeRestaurant('rest_101');

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'rest_101',
            'data' => [
                'message' => [
                    'key' => [
                        'remoteJid' => '923001234567@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => '654321'
                    ],
                    'locationMessage' => [
                        'degreesLatitude' => 0.0,
                        'degreesLongitude' => 0.0,
                        'name' => 'Null Island'
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders(['apikey' => 'test_evo_secret_key'])
            ->postJson(route('webhook.whatsapp'), $payload);
        $response->assertStatus(200);

        // It should NOT queue a job since it's an empty text with invalid coordinates
        Queue::assertNotPushed(ProcessWhatsAppMessage::class);
    }
}
