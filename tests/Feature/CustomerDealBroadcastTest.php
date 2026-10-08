<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerDealBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name = 'Deal Bistro'): Restaurant
    {
        $r = new Restaurant([
            'name'            => $name,
            'whatsapp_number' => '92300' . random_int(1000000, 9999999),
            'owner_phone'     => '92300' . random_int(1000000, 9999999),
            'is_open'         => true,
        ]);
        $r->status              = 'active';
        $r->registration_status = 'approved';
        $r->is_active           = true;
        $r->email_verified_at   = now();
        $r->plan                = 'trial';
        $r->owner_password      = Hash::make('Secret123456!');
        $r->save();

        return $r;
    }

    public function test_customer_page_displays_deal_image_upload_field(): void
    {
        $r = $this->createRestaurant('Image Promo Kitchen');
        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.customers', $r->id));
        $response->assertOk();
        $response->assertSee('deal_image');
        $response->assertSee('dealImageInput');
        $response->assertSee('multipart/form-data');
    }

    public function test_deal_broadcast_accepts_and_processes_deal_image(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['status' => 'SUCCESS', 'id' => 'EVO-123'], 200),
        ]);

        $r = $this->createRestaurant('Promo Hub');
        $this->withSession(["restaurant_{$r->id}" => true]);

        // Create an opted-in customer
        Customer::create([
            'restaurant_id'    => $r->id,
            'name'             => 'Kashif',
            'phone'            => '923001234567',
            'opt_in_marketing' => true,
            'tag'              => 'VIP',
        ]);

        $jpegBytes = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRof'
            . 'Hh0aHBwcJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPDs0NDL/wAALCAABAAEBAREA/8QAFAABAQAAAAAAAAAAAAAAAAAA'
            . 'AAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AmgA//9k=',
            true
        );
        $file = UploadedFile::fake()->createWithContent('special_deal.jpg', $jpegBytes);

        $response = $this->post(route('dashboard.broadcast-deal', $r->id), [
            'target'     => 'all',
            'message'    => 'Get 30% off on all items today only!',
            'deal_image' => $file,
        ]);

        $response->assertSessionHas('success');
        $this->assertStringContainsString('with promo image', session('success'));
    }

    public function test_deal_broadcast_rejects_non_image_files(): void
    {
        $r = $this->createRestaurant('Security Kitchen');
        $this->withSession(["restaurant_{$r->id}" => true]);

        $file = UploadedFile::fake()->create('malicious.pdf', 100);

        $response = $this->post(route('dashboard.broadcast-deal', $r->id), [
            'target'     => 'all',
            'message'    => 'Special weekend package deal!',
            'deal_image' => $file,
        ]);

        $response->assertSessionHasErrors(['deal_image']);
    }
}
