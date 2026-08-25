<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Support\BotControlClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the Phase 3 bot control-server lockdown (C-02).
 *
 * Before this, the connect-WhatsApp page had the *browser* fetch
 * `http://<hostname>:3000/qr-status` directly. That forced the Node control
 * server to listen on 0.0.0.0 with `Access-Control-Allow-Origin: *` and no
 * authentication, and it returns the WhatsApp pairing QR — scanning it links your
 * device to the restaurant's WhatsApp account. `POST /restart` was equally open.
 *
 * The QR now only leaves the server through these authenticated, per-restaurant
 * routes, and the control server itself binds to loopback behind a shared secret.
 */
class BotControlTest extends TestCase
{
    use RefreshDatabase;

    private const BOT_URL = 'http://127.0.0.1:3000';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.bot_internal_api'   => self::BOT_URL,
            'app.bot_internal_token' => 'test-bot-token',
        ]);
    }

    private function restaurant(string $name = 'Bot Test Kitchen'): Restaurant
    {
        $r = new Restaurant([
            'name'            => $name,
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

    private function owner(?Restaurant $r = null): Restaurant
    {
        $r ??= $this->restaurant();
        $this->withSession(["restaurant_{$r->id}" => true]);

        return $r;
    }

    /** @param array<string, mixed> $payload */
    private function fakeBot(array $payload, int $status = 200): void
    {
        Http::fake([self::BOT_URL . '/*' => Http::response($payload, $status)]);
    }

    // ── Authorization ─────────────────────────────────────────

    public function test_status_and_restart_require_authentication(): void
    {
        Http::fake();
        $r = $this->restaurant('Unauthed Bot Kitchen');

        $this->getJson(route('dashboard.bot-status', $r->id))->assertStatus(302);
        $this->postJson(route('dashboard.bot-restart', $r->id))->assertStatus(302);

        // The control server must never even be contacted for a rejected caller.
        Http::assertNothingSent();
    }

    public function test_owner_cannot_read_another_restaurants_bot_status(): void
    {
        Http::fake();
        $this->owner();
        $theirs = $this->restaurant('Other Bot Kitchen');

        $this->getJson(route('dashboard.bot-status', $theirs->id))->assertStatus(302);
        Http::assertNothingSent();
    }

    // ── The QR itself ─────────────────────────────────────────

    public function test_qr_is_returned_to_the_owner_of_the_paired_restaurant(): void
    {
        $r = $this->owner();

        $this->fakeBot([
            'success'       => true,
            'status'        => 'qr_pending',
            'qr'            => 'data:image/png;base64,AAAA',
            'bot_number'    => null,
            'restaurant_id' => $r->id,
        ]);

        $this->getJson(route('dashboard.bot-status', $r->id))
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'qr_pending', 'qr' => 'data:image/png;base64,AAAA']);
    }

    public function test_qr_is_withheld_when_the_bot_is_paired_to_a_different_restaurant(): void
    {
        $mine = $this->owner();

        // One bot process serves one WhatsApp account. If it belongs to someone
        // else, handing over its QR would let this owner seize that account.
        $this->fakeBot([
            'success'       => true,
            'status'        => 'qr_pending',
            'qr'            => 'data:image/png;base64,SECRET',
            'restaurant_id' => $mine->id + 999,
        ]);

        $response = $this->getJson(route('dashboard.bot-status', $mine->id))
            ->assertStatus(409)
            ->assertJson(['success' => false, 'status' => 'linked_elsewhere']);

        $this->assertStringNotContainsString('SECRET', $response->getContent());
    }

    public function test_an_unpaired_bot_may_be_claimed_by_any_authenticated_owner(): void
    {
        $r = $this->owner();

        $this->fakeBot(['success' => true, 'status' => 'qr_pending', 'qr' => 'data:x', 'restaurant_id' => null]);

        $this->getJson(route('dashboard.bot-status', $r->id))->assertOk();
    }

    public function test_restart_is_refused_while_the_bot_belongs_to_another_restaurant(): void
    {
        $mine = $this->owner();

        $this->fakeBot(['success' => true, 'status' => 'connected', 'restaurant_id' => $mine->id + 999]);

        $this->postJson(route('dashboard.bot-restart', $mine->id))
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        // Nothing may have reached /restart — that would drop the other tenant.
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/restart'));
    }

    public function test_restart_reaches_the_bot_for_its_own_restaurant(): void
    {
        $r = $this->owner();

        $this->fakeBot(['success' => true, 'status' => 'connected', 'restaurant_id' => $r->id]);

        $this->postJson(route('dashboard.bot-restart', $r->id))->assertOk()->assertJson(['success' => true]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/restart') && $request->method() === 'POST');
    }

    // ── Transport ─────────────────────────────────────────────

    public function test_every_call_carries_the_shared_secret(): void
    {
        $r = $this->owner();
        $this->fakeBot(['success' => true, 'status' => 'connected', 'restaurant_id' => $r->id]);

        $this->getJson(route('dashboard.bot-status', $r->id))->assertOk();

        Http::assertSent(fn (Request $request) => $request->header('X-Bot-Token') === ['test-bot-token']);
    }

    public function test_an_unreachable_bot_is_reported_not_crashed(): void
    {
        $r = $this->owner();

        Http::fake([self::BOT_URL . '/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('refused')]);

        $this->getJson(route('dashboard.bot-status', $r->id))
            ->assertStatus(503)
            ->assertJson(['success' => false, 'status' => 'unreachable']);

        $this->postJson(route('dashboard.bot-restart', $r->id))
            ->assertStatus(503)
            ->assertJson(['success' => false]);
    }

    public function test_send_message_reports_failure_instead_of_throwing(): void
    {
        Http::fake([self::BOT_URL . '/*' => Http::response(['success' => false, 'error' => 'Unauthorized'], 401)]);

        $this->assertFalse(BotControlClient::sendMessage('923001234567', 'hello'));
    }

    public function test_send_message_refuses_empty_input_without_a_round_trip(): void
    {
        Http::fake();

        $this->assertFalse(BotControlClient::sendMessage('', 'hello'));
        $this->assertFalse(BotControlClient::sendMessage('923001234567', ''));

        Http::assertNothingSent();
    }
}
