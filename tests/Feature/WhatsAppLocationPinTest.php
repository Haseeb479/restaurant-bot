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

    public function test_native_location_pin_is_extracted_and_queued()
    {
        Queue::fake();

        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'phone' => '03001234567',
            'city' => 'Lahore',
            'evolution_instance_id' => 'rest_100',
            'is_active' => true,
        ]);

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

        $response = $this->postJson('/api/webhooks/evolution', $payload);
        $response->assertStatus(200);

        Queue::assertPushed(ProcessWhatsAppMessage::class, function ($job) {
            $coords = $job->locationCoords;
            return $coords !== null && $coords['lat'] === 31.5204 && $coords['lng'] === 74.3587;
        });
    }

    public function test_invalid_location_null_island()
    {
        Queue::fake();

        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'phone' => '03001234567',
            'evolution_instance_id' => 'rest_101',
            'is_active' => true,
        ]);

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

        $response = $this->postJson('/api/webhooks/evolution', $payload);
        $response->assertStatus(200);

        // It should NOT queue a job since it's an empty text with invalid coordinates
        Queue::assertNotPushed(ProcessWhatsAppMessage::class);
    }
}
