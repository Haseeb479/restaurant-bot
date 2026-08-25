<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the Phase 3 redaction of the public tracking page.
 *
 * /track has no login. The tracking code is the only thing gating the order, and
 * those links get forwarded, pasted into group chats and left in browser
 * history — so the page must show the least that still lets a customer
 * recognise their own order.
 */
class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const FULL_ADDRESS = 'House 12, Street 4, Block B, Gulberg, Lahore';
    private const RIDER_PHONE  = '923451112223';

    private function restaurant(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Tracking Test Kitchen',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'is_active'       => true,
            'is_open'         => true,
            'plan'            => 'trial',
        ]);
        $r->owner_password = Hash::make('owner-secret-password');
        $r->save();

        return $r;
    }

    /** @param array<string, mixed> $attributes */
    private function order(array $attributes = []): Order
    {
        $restaurant = $this->restaurant();

        return Order::create([
            'restaurant_id'    => $restaurant->id,
            'customer_phone'   => '923008888888',
            'customer_name'    => 'Ayesha Siddiqui',
            'delivery_address' => self::FULL_ADDRESS,
            'tracking_code'    => Order::generateTrackingCode($restaurant),
            'status'           => 'preparing',
            'subtotal'         => 900,
            'delivery_charge'  => 100,
            'total'            => 1000,
            'payment_method'   => 'cash_on_delivery',
            // The bot fills `notes` with a copy of its own last two chat
            // messages, which restate the address in full.
            'notes'            => "Order Summary\n1x Zinger Burger — Rs. 900\n📍 Address: " . self::FULL_ADDRESS,
            ...$attributes,
        ]);
    }

    // ── The redaction accessors ───────────────────────────────

    public function test_address_masking_keeps_only_the_broadest_segments(): void
    {
        $order = new Order(['delivery_address' => self::FULL_ADDRESS]);
        $this->assertSame('••• Gulberg, Lahore', $order->masked_delivery_address);

        // Two segments: only the last survives.
        $order = new Order(['delivery_address' => 'House 12, Gulberg']);
        $this->assertSame('••• Gulberg', $order->masked_delivery_address);

        // One run-on line has no structure to trim safely, so none of it shows.
        $order = new Order(['delivery_address' => 'House 12 Street 4 Block B Gulberg Lahore']);
        $this->assertSame('••• hidden', $order->masked_delivery_address);

        $order = new Order(['delivery_address' => 'Flat 2B']);
        $this->assertSame('••• hidden', $order->masked_delivery_address);

        $order = new Order(['delivery_address' => null]);
        $this->assertSame('', $order->masked_delivery_address);
    }

    public function test_rider_is_identified_by_first_name_only(): void
    {
        $order = new Order(['rider_name' => 'Bilal Ahmed Khan']);
        $this->assertSame('Bilal', $order->rider_display_name);

        $order = new Order(['rider_name' => '  ']);
        $this->assertNull($order->rider_display_name);
    }

    public function test_rider_contact_is_published_only_while_in_transit(): void
    {
        foreach (['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'] as $status) {
            $order = new Order(['status' => $status, 'rider_phone' => self::RIDER_PHONE]);
            $this->assertFalse($order->showsRiderContact(), "rider phone exposed while {$status}");
        }

        $order = new Order(['status' => 'out_for_delivery', 'rider_phone' => self::RIDER_PHONE]);
        $this->assertTrue($order->showsRiderContact());

        $order = new Order(['status' => 'out_for_delivery', 'rider_phone' => null]);
        $this->assertFalse($order->showsRiderContact());
    }

    // ── The rendered page ─────────────────────────────────────

    public function test_the_page_does_not_publish_the_full_address(): void
    {
        $order = $this->order();

        $html = $this->get(route('order.track.live', $order->tracking_code))
            ->assertOk()
            ->getContent();

        // The customer still sees enough to recognise the order...
        $this->assertStringContainsString('Gulberg, Lahore', $html);
        // ...but not the doorstep, and not via the chat summary either.
        $this->assertStringNotContainsString('House 12', $html);
        $this->assertStringNotContainsString('Street 4', $html);
    }

    public function test_the_page_lists_items_from_the_order_rows_not_the_chat_summary(): void
    {
        $order = $this->order();

        OrderItem::create([
            'order_id'   => $order->id,
            'name'       => 'Zinger Burger',
            'size'       => 'L',
            'unit_price' => 450,
            'quantity'   => 2,
            'subtotal'   => 900,
        ]);

        $html = $this->get(route('order.track.live', $order->tracking_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Zinger Burger (L) x2', $html);
        // `notes` must not be rendered — it carries the full address.
        $this->assertStringNotContainsString('Order Summary\n', $html);
        $this->assertStringNotContainsString(self::FULL_ADDRESS, $html);
    }

    public function test_the_rider_phone_is_absent_before_dispatch_and_present_after(): void
    {
        $order = $this->order(['status' => 'preparing', 'rider_name' => 'Bilal Ahmed', 'rider_phone' => self::RIDER_PHONE]);

        $html = $this->get(route('order.track.live', $order->tracking_code))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::RIDER_PHONE, $html);
        $this->assertStringNotContainsString('Ahmed', $html);

        $order->update(['status' => 'out_for_delivery']);

        $html = $this->get(route('order.track.live', $order->tracking_code))->assertOk()->getContent();
        $this->assertStringContainsString(self::RIDER_PHONE, $html);
        $this->assertStringContainsString('Bilal', $html);
        $this->assertStringNotContainsString('Ahmed', $html);
    }

    public function test_the_page_is_not_cached_or_indexed(): void
    {
        $order = $this->order();

        $response = $this->get(route('order.track.live', $order->tracking_code))->assertOk();

        // A shared cache holding this would serve one customer's order to the next.
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);
        // The tracking code is in the URL, so it must not ride along in Referer.
        $response->assertSee('name="referrer" content="no-referrer"', false);
    }

    public function test_an_unknown_code_reveals_nothing(): void
    {
        $this->order();

        $this->get(route('order.track.live', 'ORD-NOPENOPENOPENOPE'))
            ->assertOk()
            ->assertSee('No Order Found');
    }

    // ── The status poll endpoint ──────────────────────────────

    public function test_the_status_endpoint_returns_status_only(): void
    {
        $order = $this->order(['rider_name' => 'Bilal Ahmed', 'rider_phone' => self::RIDER_PHONE]);

        $response = $this->getJson(route('order.track.status', $order->tracking_code))
            ->assertOk()
            ->assertJson(['status' => 'preparing'])
            ->assertJsonStructure(['status', 'status_label', 'status_message', 'has_live_gps', 'rider_lat', 'rider_lng', 'rider_updated']);

        // This response is fetched every 8 seconds — it must carry no PII.
        $body = $response->getContent();
        foreach ([self::FULL_ADDRESS, self::RIDER_PHONE, '923008888888', 'Ayesha'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
        $this->assertSame(7, count($response->json()));
    }

    public function test_the_status_endpoint_404s_for_an_unknown_code(): void
    {
        $this->getJson(route('order.track.status', 'ORD-NOPENOPENOPENOPE'))->assertNotFound();
    }

    // ── Enumeration ───────────────────────────────────────────

    public function test_tracking_lookups_are_rate_limited(): void
    {
        $order = $this->order();
        $url   = route('order.track.live', $order->tracking_code);

        for ($i = 0; $i < 20; $i++) {
            $this->get($url)->assertOk();
        }

        // 80 bits of entropy plus this limit is what makes guessing impractical.
        $this->get($url)->assertStatus(429);
    }
}
